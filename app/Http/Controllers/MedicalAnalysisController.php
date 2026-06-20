<?php

namespace App\Http\Controllers;

use App\Models\MedicalAnalysis;
use App\Models\Setting;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;

use Illuminate\Support\Facades\Http;
use App\Services\AI\AIVisionManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Spatie\PdfToImage\Pdf;

class MedicalAnalysisController extends Controller
{
    public function __construct(){}

    public function index()
    {
        return view('medical-analysis.index');
    }

    /**
     * Renders the upload view.
     */
    public function showUploadForm()
    {
        return view('medical-analysis.upload');
    }

    /**
     * Processes multiple medical PDFs and anonymizes them.
     * EL MÉTODO PÚBLICO (Recibe el AJAX del upload)
     */
    public function processDocuments(Request $request)
    {
        // 1. Validaciones estrictas del formulario
        $request->validate([
            'pdf_files' => 'required|array|min:1|max:5',
            'pdf_files.*' => 'required|mimes:pdf|max:10000',
            'customer_email' => 'required|email',
            'reason_type' => 'required|string',
            'reason_custom' => 'nullable|string'
        ]);

        // Guardar físicamente los archivos en el almacenamiento privado (OBLIGATORIO para el método de visión)
        $storedPaths = [];
        foreach ($request->file('pdf_files') as $file) {
            $storedPaths[] = $file->store('medical-exams', 'private');
        }

        $price = Setting::get('medical_analysis_price', 19000); 

        // CREAR EL REGISTRO (Aquí queda tu respaldo seguro en la DB con estado 'pending')
        $analysis = MedicalAnalysis::create([
            'file_paths'     => json_encode($storedPaths),
            'customer_email' => strtolower(trim($request->input('customer_email'))),
            'reason_type'    => $request->input('reason_type'),
            'reason_custom'  => trim($request->input('reason_custom')),
            'price'          => $price, 
            'status'         => 'pending',
            'payment_status' => 'pending'
        ]);

        // Intentar la consulta inmediata a la IA pasándole el modelo completo
        $this->analyzeWithAI($analysis);

        // Responder al JavaScript para la redirección fluida en el navegador
        return response()->json([
            'status' => 'success',
            'redirect_url' => route('medical-analysis.show', $analysis->access_token)
        ]);
    }

    /**
     * Analiza los PDFs/imágenes del análisis médico usando el proveedor de IA configurado.
     *
     * @param  string|null  $provider  Override manual: 'openai' | 'claude' | null (usa default de config)
     * @param  bool  $withFallback     Si true, intenta con el otro proveedor si el primario falla
     */
    public function analyzeWithAI(MedicalAnalysis $analysis, ?string $provider = null, bool $withFallback = true)
    {
        $reasons = [
            'rutina'   => 'Control de rutina anual o chequeo preventivo.',
            'control'  => 'Seguimiento continuo de una patología médica existente.',
            'sintomas' => 'Evaluación motivada por sintomatología reciente del paciente.',
            'otros'    => 'Motivos complementarios.'
        ];

        $motivoClinico = $reasons[$analysis->reason_type] ?? $analysis->reason_type;
        $detallesAdicionales = $analysis->reason_custom ?? 'No se proporcionaron detalles adicionales.';
        $contextoPaciente = "CONTEXTO DEL PACIENTE:\n- Motivo: {$motivoClinico}\n- Detalles: {$detallesAdicionales}";

        $userText = "{$contextoPaciente}\n\nAnaliza visualmente las imágenes médicas adjuntas correspondientes a los informes provistos. Extrae los biomarcadores transversales, conclusiones claras y próximos pasos comunes. REGLA CRÍTICA: En el campo 'especialidad_slug' debes colocar OBLIGATORIAMENTE uno de estos términos en minúsculas y sin acentos según corresponda: 'medicina-general', 'neurologia', 'cardiologia', 'ginecologia', 'endocrinologia', 'pediatria', 'urologia', 'dermatologia'. Si tienes dudas o el caso es mixto, usa 'medicina-general'.";

        $systemPrompt = "Eres un asistente médico experto de un SAAS de salud. Tu tarea es extraer la información consolidada de los exámenes provistos (pueden ser múltiples documentos), correlacionar los biomarcadores y obligatoriamente clasificar el caso en una especialidad médica.";

        $filePaths = json_decode($analysis->file_paths, true);

        if (! is_array($filePaths)) {
            $filePaths = ! empty($analysis->file_path) ? [$analysis->file_path] : [];
        }

        if (empty($filePaths)) {
            Log::error("Análisis #{$analysis->id} no tiene archivos asociados (file_paths vacío).");
            $analysis->update(['status' => 'failed']);
            return;
        }

        // --- Construcción de imágenes (independiente del proveedor) ---
        $images = [];
        $tempFilesToCleanup = [];

        foreach ($filePaths as $index => $path) {
            if (! Storage::disk('private')->exists($path)) {
                Log::warning("Análisis #{$analysis->id}: el archivo '{$path}' no existe en el disco 'private'.");
                continue;
            }

            $filePath = Storage::disk('private')->path($path);
            $mimeType = mime_content_type($filePath);

            if ($mimeType === false) {
                Log::warning("Análisis #{$analysis->id}: no se pudo determinar el mime type de '{$path}'.");
                continue;
            }

            try {
                if ($mimeType === 'application/pdf') {
                    $tempImagePath = storage_path('app/temp/med_batch_' . uniqid() . '_' . $index . '.jpg');

                    if (! is_dir(dirname($tempImagePath))) {
                        mkdir(dirname($tempImagePath), 0755, true);
                    }

                    $pdf = new \Spatie\PdfToImage\Pdf($filePath); // ajusta el namespace según tu librería real
                    $pdf->saveImage($tempImagePath);

                    if (file_exists($tempImagePath) && filesize($tempImagePath) > 0) {
                        $images[] = [
                            'base64' => base64_encode(file_get_contents($tempImagePath)),
                            'mime' => 'image/jpeg',
                        ];
                        $tempFilesToCleanup[] = $tempImagePath;
                    } else {
                        Log::error("Análisis #{$analysis->id}: la conversión PDF->JPG de '{$path}' no generó archivo.");
                    }
                } elseif (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
                    $images[] = [
                        'base64' => base64_encode(file_get_contents($filePath)),
                        'mime' => $mimeType,
                    ];
                } else {
                    Log::warning("Análisis #{$analysis->id}: tipo de archivo no soportado '{$mimeType}' para '{$path}'.");
                }
            } catch (\Throwable $e) {
                Log::error("Fallo la conversión visual para el archivo {$path} en el Análisis #{$analysis->id}: " . $e->getMessage());
                continue;
            }
        }

        foreach ($tempFilesToCleanup as $tempFile) {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }

        if (empty($images)) {
            Log::error("Cancelada la llamada a la IA para el Análisis #{$analysis->id} porque no se generaron imágenes multimedia.");
            $analysis->update(['status' => 'failed']);
            return;
        }

        Log::info("Análisis #{$analysis->id}: enviando " . count($images) . " imagen(es). Proveedor solicitado: " . ($provider ?? 'default de config'));

        // --- Llamada al proveedor (switch transparente) ---
        try {
            if ($withFallback) {
                // Intenta con $provider primero (o el orden default) y si falla, prueba el otro.
                $order = $provider
                    ? array_unique([$provider, $provider === 'claude' ? 'openai' : 'claude'])
                    : ['openai', 'claude'];

                $outcome = AIVisionManager::analyzeWithFallback($systemPrompt, $userText, $images, $order);
                $aiResult = $outcome['result'];
                $providerUsed = $outcome['provider_used'];
            } else {
                $driver = AIVisionManager::driver($provider);
                $aiResult = $driver->analyzeImages($systemPrompt, $userText, $images);
                $providerUsed = $driver->name();
            }

            $analysis->update([
                'ai_response' => $aiResult,
                'ai_provider' => $providerUsed, // opcional: agrega esta columna si quieres trazabilidad
                'status' => 'completed',
            ]);

            Log::info("Análisis #{$analysis->id} completado correctamente con proveedor '{$providerUsed}'.");
        } catch (\Throwable $e) {
            Log::error("Fallo el procesamiento automático de la IA para el Análisis #{$analysis->id}: " . $e->getMessage());

            $analysis->update(['status' => 'failed']);
        }
    }

    /**
     * Muestra el análisis médico identificado por su access_token público.
     * Laravel ya resolvió $analysis automáticamente buscando por access_token;
     * si no existe, lanza 404 antes de que este método se ejecute.
     */
    public function show(MedicalAnalysis $medicalAnalysis)
    {
        $analysis = $medicalAnalysis;
    
        // Si el registro quedó inconsistente (status=completed pero sin ai_response
        // por alguna falla silenciosa), lo tratamos igual que failed para no romper la vista.
        if ($analysis->status === 'completed' && empty($analysis->ai_response)) {
            Log::warning("Análisis #{$analysis->id}: status=completed pero ai_response está vacío. Se trata como inconsistente.");
            $analysis->status = 'failed'; // solo en memoria, no se persiste
        }
    
        return view('medical-analysis.show', compact('analysis'));
    }
}
