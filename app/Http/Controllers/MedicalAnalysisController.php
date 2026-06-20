<?php

namespace App\Http\Controllers;

use App\Models\MedicalAnalysis;
use App\Models\Setting;
use App\Services\AnonymizerService;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Spatie\PdfToImage\Pdf;

class MedicalAnalysisController extends Controller
{
    protected $anonymizer;

    public function __construct(AnonymizerService $anonymizer)
    {
        $this->anonymizer = $anonymizer;
    }

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

        // 1. Instanciar el Parser usando su Namespace completo (Evita el error Class 'Parser' not found)
        $parser = new Parser();
        $rawExtractedText = "";

        // 2. Validación de seguridad preventiva antes de iterar
        if ($request->hasFile('pdf_files')) {
            foreach ($request->file('pdf_files') as $index => $pdfFile) {
                // Optimización: Validar que el archivo subido sea realmente válido y no esté corrupto en la subida HTTP
                if (! $pdfFile->isValid()) {
                    continue;
                }

                try {
                    $pdf = $parser->parseFile($pdfFile->getPathname());
                    
                    // Reajuste estético: Limpiar espacios en blanco innecesarios antes de concatenar
                    $documentText = trim($pdf->getText());
                    
                    if (! empty($documentText)) {
                        $rawExtractedText .= "\n\n--- DOCUMENTO CLÍNICO #" . ($index + 1) . " ---\n";
                        $rawExtractedText .= $documentText;
                    }
                } catch (\Exception $e) {
                    // Registra el fallo en el log para auditoría técnica, pero permite que el flujo continúe
                    Log::warning("No se pudo extraer texto del PDF índice {$index}: " . $e->getMessage());
                    continue; 
                }
            }
        }

        // 3. Sanitizar y anonimizar el texto extraído mediante tu servicio existente
        $cleaned_text = $this->anonymizer->cleanMedicalText($rawExtractedText);

        $price = Setting::get('medical_analysis_price', 19000); 

        // 4. CREAR EL REGISTRO (Aquí queda tu respaldo seguro en la DB con estado 'pending')
        $analysis = MedicalAnalysis::create([
            'cleaned_text'   => $cleaned_text, // Guardado y respaldado con éxito
            'customer_email' => strtolower(trim($request->input('customer_email'))),
            'reason_type'    => $request->input('reason_type'),
            'reason_custom'  => trim($request->input('reason_custom')),
            'price'          => $price, 
            'status'         => 'pending',
            'payment_status' => 'pending'
        ]);

        // 5. Intentar la consulta inmediata a la IA pasándole el modelo completo
        $this->analyzeWithAI($analysis);

        // 6. Responder al JavaScript para la redirección fluida en el navegador
        return response()->json([
            'status' => 'success',
            'redirect_url' => route('medical-analysis.show', $analysis->id)
        ]);
    }

    /**
     * Ejecuta el análisis de Inteligencia Artificial multidocumento utilizando el cliente HTTP nativo de Laravel.
     * Procesa hasta 5 archivos (PDFs o imágenes) y retorna una estructura JSON médica estricta.
     *
     * @param  MedicalAnalysis  $analysis
     * @return void
     */
    public function analyzeWithAI(MedicalAnalysis $analysis)
    {
        $apiKey = config('services.openai.key');

        // Mapear motivos clínicos semánticos para el prompt de la IA
        $reasons = [
            'rutina'   => 'Control de rutina anual o chequeo preventivo.',
            'control'  => 'Seguimiento continuo de una patología médica existente.',
            'sintomas' => 'Evaluación motivada por sintomatología reciente del paciente.',
            'otros'    => 'Motivos complementarios.'
        ];

        $motivoClinico = $reasons[$analysis->reason_type] ?? $analysis->reason_type;
        $detallesAdicionales = $analysis->reason_custom ?? 'No se proporcionaron detalles adicionales.';

        try {
            if ($analysis->cleaned_text) {
                // Consumo de OpenAI con el cliente HTTP oficial de Laravel
                $response = Http::withToken($apiKey)
                    ->timeout(60) 
                    ->retry(3, 2000) 
                    ->post('https://openai.com', [
                        'model' => 'gpt-4o', // Modelo de procesamiento de lenguaje natural
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => "Eres un asistente médico experto de un SAAS de salud. Tu tarea es extraer la información consolidada de los exámenes provistos (pueden ser múltiples documentos), correlacionar los biomarcadores y obligatoriamente clasificar el caso en una especialidad médica."
                            ],
                            [
                                'role' => 'user',
                                'content' => "CONTEXTO DEL PACIENTE:\n- Motivo: {$motivoClinico}\n- Detalles: {$detallesAdicionales}\n\nTEXTO EXTRAÍDO DE LOS EXÁMENES:\n{$analysis->cleaned_text}\n\n Extrae los biomarcadores, conclusiones claras y próximos pasos. REGLA CRÍTICA: En el campo 'especialidad_slug' debes colocar OBLIGATORIAMENTE uno de estos términos en minúsculas y sin acentos según corresponda: 'medicina-general', 'neurologia', 'cardiologia', 'ginecologia', 'endocrinologia', 'pediatria', 'urologia', 'dermatologia'. Si tienes dudas, usa 'medicina-general'."
                            ]
                        ],
                        'response_format' => [
                            'type' => 'json_schema',
                            'json_schema' => [
                                'name' => 'analisis_clinico_pago',
                                'strict' => true,
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'nombre_examen' => ['type' => 'string'],
                                        'especialidad_slug' => ['type' => 'string'],
                                        'hallazgos_clave' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'parametro' => ['type' => 'string'],
                                                    'valor_detectado' => ['type' => 'string'],
                                                    'estado' => ['type' => 'string', 'enum' => ['Normal', 'Elevado', 'Bajo', 'Crítico']],
                                                ],
                                                'required' => ['parametro', 'valor_detectado', 'estado'],
                                                'additionalProperties' => false
                                            ]
                                        ],
                                        'conclusion_paciente' => ['type' => 'string'],
                                        'recomendaciones' => ['type' => 'string']
                                    ],
                                    'required' => ['nombre_examen', 'especialidad_slug', 'hallazgos_clave', 'conclusion_paciente', 'recomendaciones'],
                                    'additionalProperties' => false
                                ]
                            ]
                        ],
                        'temperature' => 0.2
                    ]);
    
                if ($response->failed()) {
                    throw new \Exception('La API de OpenAI falló o devolvió un código de error.');
                }
    
                // Si todo sale bien, guardamos el JSON estructurado y cambiamos el estado a completed
                $analysis->update([
                    'ai_result' => json_decode($response->json('choices.0.message.content'), true),
                    'status' => 'completed'
                ]);

            } else {
                Log::error("El campo cleaned_text es null para el Analisis #{$analysis->id}: ");
            }
        } catch (\Exception $e) {
            // ¡Aquí está la magia de tu estrategia!
            // Si hay un error con OpenAI, NO borramos el registro. Lo dejamos marcado como 'failed'
            // pero conservamos intacto el 'cleaned_text' en la base de datos para reintentarlo después.
            Log::error("Fallo el procesamiento automático de la IA para el Análisis #{$analysis->id}: " . $e->getMessage());
            
            $analysis->update([
                'status' => 'failed' // El estado cambia a fallido pero mantiene toda la información útil
            ]);
        }
    }

    /**
     * Busca el análisis y lo pasa a la vista si existe; de lo contrario, redirige.
     * 
     * @param  mixed  $id
     */
    public function show($id)
    {
        // 1. Intentar buscar el registro en la base de datos sin lanzar excepciones drásticas
        $analysis = MedicalAnalysis::find($id);

        // 2. Control de contingencia: Si no existe el registro, redirige con un mensaje de alerta
        if (! $analysis) {
            return redirect()->route('medical-analysis.index')
                ->with('error', 'El número de valoración médica solicitado no existe o caducó.');
        }
        
        // 3. Si existe, se despacha el objeto de forma transparente a tu vista por bloques
        return view('medical-analysis.show', compact('analysis'));
    }
}
