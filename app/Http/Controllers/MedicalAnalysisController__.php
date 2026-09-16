<?php

namespace App\Http\Controllers;

use App\Models\MedicalAnalysis;
use App\Models\Setting;
use App\Models\PromoCode;

use App\Services\AnalysisPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\AI\AIVisionManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Spatie\PdfToImage\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\ExamAnalysisReady;
use App\Mail\ExamPaymentPendingAlert; 
use Illuminate\Support\Str;
use Carbon\Carbon;
use Spatie\PdfToImage\Enums\OutputFormat;

class MedicalAnalysisController extends Controller
{
    const SUPPORTED_LANGUAGES = ['es', 'en'];
    const DEFAULT_LANGUAGE = 'es';

    public function __construct(){}

    public function index()
    {
        $priceSetting = Setting::get('exam_type_lab_price', 12000); 
        $price = number_format($priceSetting, 0, ',', '.');
        $meta_title_medicalAnalysis = 'OpenDoctorOnline | Interpreta tus exámenes médicos con Inteligencia Artificial';
        $meta_description_medicalAnalysis = 'Análisis médico online con IA. Laboratorio e imagenología (radiografías, tomografías, resonancias). Diagnóstico instantáneo, cita médica virtual y presencial disponible.';
        
        return view('medical-analysis.index', compact('price', 'meta_title_medicalAnalysis', 'meta_description_medicalAnalysis'));
    }

    /**
     * 🆕 MOSTRAR FORMULARIO CON PRECIOS DINÁMICOS
     */
    public function showUploadForm()
    {
        // Obtener precios desde settings
        $prices = [
            'lab' => (int)Setting::get('exam_type_lab_price', 12000),
            'xray' => (int)Setting::get('exam_type_xray_price', 18000),
            'ultrasound' => (int)Setting::get('exam_type_ultrasound_price', 16000),
            'ct' => (int)Setting::get('exam_type_ct_price', 30000),
            'mri' => (int)Setting::get('exam_type_mri_price', 35000),
            'dicom' => (int)Setting::get('exam_type_dicom_price', 30000),
            'mammography' => (int)Setting::get('exam_type_mammography_price', 22000),
        ];

        $meta_title_medicalAnalysis = 'OpenDoctorOnline | Interpreta tus exámenes médicos con Inteligencia Artificial';
        $meta_description_medicalAnalysis = 'Análisis médico online con IA. Laboratorio e imagenología (radiografías, tomografías, resonancias). Diagnóstico instantáneo, cita médica virtual y presencial disponible.';

        return view('medical-analysis.upload', compact('prices', 'meta_title_medicalAnalysis', 'meta_description_medicalAnalysis'));
    }

    public function beforePreview(Request $request)
    {
        $validated = $request->validate([
            'medical_files' => 'required|array|max:5',
            'medical_files.*' => 'file|mimes:pdf,jpg,jpeg,png,dcm|max:10240',
            'customer_email' => 'required|email',
            'selected_language' => 'required|in:es,en',
            'detected_exam_type' => 'required|in:lab,xray,ultrasound,ct,mri,mammography,dicom',
            'reason_type' => 'nullable|in:rutina,control,sintomas,otros',
            'reason_custom' => 'nullable|string|max:500',
        ]);

        // Guardar archivos temporalmente
        $files = [];
        $totalSize = 0;
        
        foreach ($request->file('medical_files') as $file) {
            $path = $file->store('temp-uploads', 'local');
            $files[] = [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'path' => $path,
            ];
            $totalSize += $file->getSize();
        }

        // Obtener precio
        $examType = $validated['detected_exam_type'];
        $prices = config('services.medical_analysis.prices', []);
        $price = $prices[$examType] ?? 30000;

        // Guardar TODO en sesión (incluyendo precio y cálculos)
        session([
            'medical_analysis_files' => $files,
            'medical_analysis_total_size' => $totalSize,
            'medical_analysis_data' => $validated,
            'medical_analysis_price' => $price,
        ]);

        // RESPUESTA CRÍTICA: Devolvemos JSON con el status y la URL a donde JavaScript debe redirigir
        return response()->json([
            'status' => 'success',
            'redirect_url' => route('medical-analysis.preview')
        ]);
    }

    /**
     * Mostrar preview de la orden
     */
    public function preview()
    {
        // Recuperamos los datos guardados en la sesión en el paso anterior
        $files = session('medical_analysis_files', []);
        $totalSize = session('medical_analysis_total_size', 0);
        $validated = session('medical_analysis_data', []);
        $price = session('medical_analysis_price', 30000);

        // Si por alguna razón la sesión está vacía, lo regresamos al formulario
        if (empty($validated)) {
            return redirect()->route('medical-analysis.upload')
            ->with('error', 'La sesión ha expirado. Por favor, carga tus archivos nuevamente.');
        }

        // Renderizamos la vista enviándole los datos guardados
        return view('medical-analysis.preview', [
            'files' => $files,
            'totalSize' => $totalSize,
            'examType' => $validated['detected_exam_type'],
            'price' => $price,
            'email' => $validated['customer_email'],
            'language' => $validated['selected_language'],
            'reasonType' => $validated['reason_type'] ?? null,
            'reasonCustom' => $validated['reason_custom'] ?? null,
        ]);
    }
    
    /**
     * Procesar documentos - Solo crear registro, NO procesar con IA aún
     */
    public function processDocuments(Request $request)
    {
        $files = session('medical_analysis_files', []);
        $data = session('medical_analysis_data', []);

        if (empty($files) || empty($data)) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Sesión expirada. Por favor, intenta de nuevo.'
            ], 400);
        }

        $detectedType = strtolower(trim($data['detected_exam_type']));
        $email = trim(strtolower($data['customer_email']));
        $language = strtolower($data['selected_language']);
        $reasonType = $data['reason_type'] ?? null;
        $reasonCustom = trim($data['reason_custom'] ?? '');
        $promoCode = $request->input('promotional_code');

        // Validar tipo detectado
        if (!in_array($detectedType, ['lab', 'xray', 'ultrasound', 'ct', 'mri', 'mammography', 'dicom'])) {
            return response()->json([
                'status' => 'error',
                'message' => "Tipo de examen no válido: {$detectedType}"
            ], 422);
        }

        // Obtener precio
        $prices = config('services.medical_analysis.prices', []);
        $price = $prices[$detectedType] ?? 30000;

        // Guardar archivos en almacenamiento privado
        $storedPaths = [];
        foreach ($files as $fileData) {
            $tempPath = $fileData['path'];
            $finalPath = 'medical-exams/' . basename($tempPath);
            
            if (Storage::disk('local')->exists($tempPath)) {
                Storage::disk('private')->put(
                    $finalPath,
                    Storage::disk('local')->get($tempPath)
                );
                $storedPaths[] = $finalPath;
            }
        }

        if (empty($storedPaths)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al guardar los archivos.'
            ], 500);
        }

        // ✅ CREAR REGISTRO SOLO CON STATUS "pending_payment" (sin procesar IA aún)
        $analysis = MedicalAnalysis::create([
            'file_paths' => json_encode($storedPaths),
            'exam_type' => $detectedType,
            'customer_email' => $email,
            'reason_type' => $reasonType,
            'reason_custom' => $reasonCustom,
            'promo_code' => $promoCode,
            'price' => $price,
            'status' => 'pending',  // ✅ Pendiente, no procesado
            'payment_status' => 'pending',
            'access_token' => Str::random(32),
            'language' => $language
        ]);

        Log::info("Análisis #{$analysis->id} creado - Estado: pending_payment - Email: {$email}");

        // Limpiar sesión
        session()->forget(['medical_analysis_files', 'medical_analysis_total_size', 'medical_analysis_data', 'medical_analysis_price']);

        // Limpiar archivos temporales
        foreach ($files as $fileData) {
            Storage::disk('local')->delete($fileData['path']);
        }

        return response()->json([
            'status' => 'success',
            'analysis_id' => $analysis->id,
            'access_token' => $analysis->access_token,
            'message' => 'Orden creada. Redirigiendo a pago...',
            'redirect_url' => route('medical-analysis.show', $analysis->access_token)
        ]);
    }

    public function processPaymentResult(Request $request, $token)
    {
        $analysis = MedicalAnalysis::where('access_token', $token)->firstOrFail();
        $transactionId = $request->query('id');

        if (!$transactionId) {
            return redirect()->route('home')->with('error', 'Falta el identificador del pago.');
        }

        $baseUrl = config('services.wompi.endpoint');
        $paymentStatus = 'ERROR';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.wompi.public_key')
            ])->get("{$baseUrl}/transactions/{$transactionId}");

            if ($response->successful()) {
                $paymentStatus = $response->json('data.status') ?? 'ERROR';

                // ✅ GUARDAR TRANSACTION ID DE WOMPI
                $analysis->update(['wompi_transaction_id' => $transactionId]);

                if ($paymentStatus === 'APPROVED') {
                    if ($analysis->payment_status !== 'completed') {
                        // ✅ CAMBIAR STATUS A "processing" PARA QUE INICIE LA IA
                        $analysis->update([
                            'payment_status' => 'completed',
                            'status' => 'processing'  // ← Agregado
                        ]);

                        // ✅ DISPARAR JOB DE IA ASINCRÓNICO
                        \App\Jobs\ProcessMedicalAnalysisAI::dispatch($analysis);

                        if ($analysis->customer_email) {
                            // ✅ ENVIAR EMAIL CON ENLACE AL INFORME
                            Mail::to($analysis->customer_email)->send(
                                new ExamAnalysisReady($analysis)
                            );
                        }
                    }
                } elseif ($paymentStatus === 'PENDING') {
                    $analysis->update(['payment_status' => 'pending']);
                } else {
                    $analysis->update(['payment_status' => 'failed']);
                }
            }
        } catch (\Exception $e) {
            logger()->error('Error en Callback de Wompi: ' . $e->getMessage());
            $paymentStatus = 'ERROR';
            $analysis->update(['payment_status' => 'error']);
        }

        return view('medical-analysis.payment-result', compact('analysis', 'paymentStatus'));
    }

    /**
     * Ver informe completo (muestra resultado de IA)
     */
    public function showResult($token)
    {
        $analysis = MedicalAnalysis::where('access_token', $token)->firstOrFail();

        // Si aún no está procesado, mostrar pantalla de espera
        if (!in_array($analysis->status, ['completed', 'success'])) {
            return view('medical-analysis.show', [
                'analysis' => $analysis,
                'price' => $analysis->price
            ]);
        }

        return view('medical-analysis.show', [
            'analysis' => $analysis,
            'price' => $analysis->price
        ]);
    } 

    /**
     * Validar código promocional
     */
    public function validatePromoCode(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50'
        ]);

        $code = PromoCode::where('code', strtoupper($validated['code']))->first();

        if (!$code) {
            return response()->json([
                'valid' => false,
                'message' => 'Código no encontrado.'
            ]);
        }

        if (!$code->is_active) {
            return response()->json([
                'valid' => false,
                'message' => 'Código inactivo.'
            ]);
        }

        $now = now();
        if ($code->valid_from && $code->valid_from > $now) {
            return response()->json([
                'valid' => false,
                'message' => 'Código aún no está disponible.'
            ]);
        }

        if ($code->valid_until && $code->valid_until < $now) {
            return response()->json([
                'valid' => false,
                'message' => 'Código expirado.'
            ]);
        }

        if ($code->max_uses && $code->uses_count >= $code->max_uses) {
            return response()->json([
                'valid' => false,
                'message' => 'Código agotado.'
            ]);
        }

        $price = session('medical_analysis_price', 30000);
        $discountAmount = 0;

        if ($code->discount_type === 'percentage') {
            $discountAmount = ceil($price * ($code->discount_value / 100));
        } else {
            $discountAmount = $code->discount_value;
        }

        return response()->json([
            'valid' => true,
            'discount_type' => $code->discount_type,
            'discount_value' => $code->discount_value,
            'discount_amount' => $discountAmount,
            'message' => 'Código aplicado correctamente.'
        ]);
    }

    /**
     * PROCESAR CON IA (ACTUALIZADO)
     */
    public function analyzeWithAI(MedicalAnalysis $analysis, ?string $provider = null, bool $withFallback = true, string $selectedLanguage = self::DEFAULT_LANGUAGE)
    {
        $selectedLanguage = trim(strtolower($selectedLanguage));
        if (!in_array($selectedLanguage, self::SUPPORTED_LANGUAGES)) {
            Log::warning("Análisis #{$analysis->id}: idioma no soportado, usando default.");
            $selectedLanguage = self::DEFAULT_LANGUAGE;
        }

        $reasons = [
            'rutina' => 'Control de rutina anual o chequeo preventivo.',
            'control' => 'Seguimiento continuo de una patología médica existente.',
            'sintomas' => 'Evaluación motivada por sintomatología reciente del paciente.',
            'otros' => 'Motivos complementarios.'
        ];

        $motivoClinico = $reasons[$analysis->reason_type] ?? $analysis->reason_type;
        $detallesAdicionales = $analysis->reason_custom ?? 'No se proporcionaron detalles adicionales.';

        [$systemPrompt, $userText] = $this->generatePrompts($selectedLanguage, $motivoClinico, $detallesAdicionales, $analysis->exam_type);

        $filePaths = json_decode($analysis->file_paths, true) ?? [];

        if (empty($filePaths)) {
            Log::error("Análisis #{$analysis->id}: no tiene archivos asociados.");
            $analysis->update(['status' => 'failed']);
            return;
        }

        // ✅ PROCESAR CON DECIMACIÓN SEGÚN TIPO DE EXAMEN
        $images = $this->processFilesIntoImages($filePaths, $analysis->id, $analysis->exam_type);

        if (empty($images)) {
            Log::error("Análisis #{$analysis->id}: no se generaron imágenes.");
            $analysis->update(['status' => 'failed']);
            return;
        }

        // Límite máximo de imágenes a enviar a Claude
        if (count($images) > 40) {
            Log::warning("Análisis #{$analysis->id}: " . count($images) . " imágenes, truncadas a 40.");
            $images = array_slice($images, 0, 40);
        }

        Log::info("Análisis #{$analysis->id}: enviando " . count($images) . " imagen(es) a IA.");

        try {
            // Obtener orden de proveedores según tipo de examen
            $order = $provider
                ? array_unique([$provider, $provider === 'claude' ? 'openai' : 'claude'])
                : AnalysisModelStrategy::getProviderOrder($analysis->exam_type);

            $outcome = AIVisionManager::analyzeWithFallback($systemPrompt, $userText, $images, $order);

            $aiResult = $outcome['result'];
            $providerUsed = $outcome['provider_used'];

            $analysis->update([
                'ai_response' => $aiResult,
                'ai_provider' => $providerUsed,
                'analysis_language' => $selectedLanguage,
                'status' => 'completed',
            ]);

            Log::info("Análisis #{$analysis->id} completado con '{$providerUsed}'.");

            $this->deleteSourceFiles($analysis, $filePaths);
        } catch (\Throwable $e) {
            Log::error("Análisis #{$analysis->id}: fallo IA: " . $e->getMessage());
            $analysis->update(['status' => 'failed']);
        }
    } 

    /**
     * 🆕 PROCESAR IMÁGENES CON DECIMACIÓN AUTOMÁTICA
     */
    private function processFilesIntoImages(array $filePaths, int $analysisId, string $examType = 'lab'): array
    {
        // Obtener factor de decimación automática
        $decimationFactor = AnalysisPricingService::getAutoDecimationFactor($examType);
        
        $images = [];
        $tempFilesToCleanup = [];
        $processedCount = 0;

        foreach ($filePaths as $index => $path) {
            // ✅ Procesar cada N-ésima imagen según decimación
            if ($index % $decimationFactor !== 0) {
                continue;
            }

            if (!Storage::disk('private')->exists($path)) {
                Log::warning("Análisis #{$analysisId}: archivo '{$path}' no existe.");
                continue;
            }

            $filePath = Storage::disk('private')->path($path);
            $mimeType = mime_content_type($filePath);

            if (!$mimeType) {
                Log::warning("Análisis #{$analysisId}: no se pudo determinar mime type de '{$path}'.");
                continue;
            }

            try {
                if ($mimeType === 'application/pdf') {
                    $images = array_merge($images, $this->convertPdfToImages($filePath, $index, $analysisId, $tempFilesToCleanup, $decimationFactor));
                } 
                elseif (in_array($mimeType, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'], true)) {
                    $images[] = [
                        'base64' => base64_encode(file_get_contents($filePath)),
                        'mime' => $mimeType,
                    ];
                    $processedCount++;
                    Log::info("Análisis #{$analysisId}: imagen '{$path}' procesada OK.");
                } 
                else {
                    Log::warning("Análisis #{$analysisId}: tipo de archivo no soportado '{$mimeType}'.");
                }
            } catch (\Throwable $e) {
                Log::error("Análisis #{$analysisId}: error procesando '{$path}': " . $e->getMessage());
                continue;
            }
        }

        // Limpiar temporales
        foreach ($tempFilesToCleanup as $tempFile) {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }

        // ✅ GUARDAR METADATA DE DECIMACIÓN
        $analysis = MedicalAnalysis::find($analysisId);
        if ($analysis) {
            $analysis->update([
                'total_images_uploaded' => count($filePaths),
                'processed_images_count' => count($images),
                'decimation_factor' => $decimationFactor
            ]);

            Log::info("Análisis #{$analysisId}: procesadas " . count($images) . " de " . count($filePaths) . " imágenes (factor: {$decimationFactor})");
        }

        return $images;
    }      

    /**
     * GENERAR PROMPTS (ACTUALIZADO CON TIPO DE EXAMEN)
     */
    private function generatePrompts(string $language, string $motivoClinico, string $detallesAdicionales, ?string $examType = null): array
    {
        if ($language === 'en') {
            return $this->getEnglishPrompts($motivoClinico, $detallesAdicionales, $examType);
        }

        return $this->getSpanishPrompts($motivoClinico, $detallesAdicionales, $examType);
    }

    /**
     * PROMPTS EN ESPAÑOL (DIFERENCIADOS POR TIPO)
     */
    private function getSpanishPrompts(string $motivoClinico, string $detallesAdicionales, ?string $examType = null): array
    {
        $contextoPaciente = "CONTEXTO DEL PACIENTE:\n- Motivo: {$motivoClinico}\n- Detalles: {$detallesAdicionales}";

        // ✅ PROMPT DIFERENCIADO SEGÚN TIPO
        if (in_array($examType, ['xray', 'ct', 'mri', 'ultrasound', 'mammography', 'dicom'])) {
            // PROMPT PARA IMAGENOLOGÍA
            $userText = "{$contextoPaciente}\n\nAnaliza esta imagen de estudio de imagenología como un radiólogo experto. Proporciona:\n\n1. HALLAZGOS PRINCIPALES: Lo más importante que observas\n2. ÁREAS DE INTERÉS CLÍNICO: Qué requiere seguimiento\n3. IMPRESIÓN RADIOLÓGICA: Posibles diagnósticos\n4. RECOMENDACIONES: Estudios complementarios y seguimiento\n\nSé preciso pero accesible al paciente. Indica claramente si hay algo que requiera atención urgente.";

            $systemPrompt = "Actúa como un radiólogo clínico experto con excelente comunicación humana.
            Responde SIEMPRE en español.

            Tu tarea: analizar imagen de imagenología, identificar hallazgos relevantes,
            explicar al paciente en lenguaje natural qué significan, y dar recomendaciones claras.

            IMPORTANTE:
            - Si la imagen no es legible o clara, indícalo sin inventar hallazgos
            - Sé específico: localización, tamaño, características de los hallazgos
            - Explica de forma sencilla qué es cada hallazgo
            - Siempre incluye descargo de responsabilidad: 'Este análisis requiere validación por radiólogo certificado'

            TONO: Amable, profesional, sin tecnicismos innecesarios.";

        } else {
            // PROMPT PARA LABORATORIO (por defecto)
            $userText = "{$contextoPaciente}\n\nAnaliza visualmente estos resultados de laboratorio como un médico especialista. Proporciona:\n\n1. PARÁMETROS ANORMALES: Cuáles están fuera de rango\n2. INTERPRETACIÓN: Qué significan estos resultados\n3. CORRELACIONES: Patrones entre valores\n4. RECOMENDACIONES: Próximos pasos y seguimiento\n\nExplica en lenguaje natural de paciente. Indica si hay algo que requiera atención urgente.";

            $systemPrompt = "Actúa como un médico patólogo clínico experto con excelente comunicación humana.
            Responde SIEMPRE en español.

            Tu tarea: analizar resultados de laboratorio, identificar anormalidades,
            explicar al paciente qué significan, y dar recomendaciones claras.

            IMPORTANTE:
            - Si algún valor no es legible, indícalo sin inventar datos
            - Sé específico con valores y rangos de referencia
            - Explica cada parámetro en lenguaje simple
            - Siempre incluye descargo: 'Este análisis requiere validación por médico certificado'

            TONO: Amable, profesional, sin tecnicismos innecesarios.";
        }

        return [$systemPrompt, $userText];
    }

    /**
     * PROMPTS EN INGLÉS (DIFERENCIADOS POR TIPO)
     */
    private function getEnglishPrompts(string $motivoClinico, string $detallesAdicionales, ?string $examType = null): array
    {
        $contextoPaciente = "PATIENT CONTEXT:\n- Reason: {$motivoClinico}\n- Details: {$detallesAdicionales}";

        if (in_array($examType, ['xray', 'ct', 'mri', 'ultrasound', 'mammography', 'dicom'])) {
            $userText = "{$contextoPaciente}\n\nAnalyze this imaging study as an expert radiologist. Provide:\n\n1. KEY FINDINGS: Most important observations\n2. AREAS OF CLINICAL INTEREST: What requires follow-up\n3. RADIOLOGICAL IMPRESSION: Possible diagnoses\n4. RECOMMENDATIONS: Follow-up studies and surveillance\n\nBe precise but accessible to the patient. Clearly indicate if anything requires urgent attention.";

            $systemPrompt = "Act as an expert clinical radiologist with excellent communication skills.
            Respond ALWAYS in English.

            Your task: analyze imaging, identify relevant findings,
            explain to the patient what they mean, give clear recommendations.

            IMPORTANT:
            - If image is not clear, state it without inventing findings
            - Be specific: location, size, characteristics
            - Explain each finding simply
            - Always include: 'This analysis requires validation by a certified radiologist'

            TONE: Friendly, professional, no unnecessary jargon.";
        } else {
            $userText = "{$contextoPaciente}\n\nAnalyze these lab results as an expert clinical physician. Provide:\n\n1. ABNORMAL PARAMETERS: Values outside normal range\n2. INTERPRETATION: What these results mean\n3. CORRELATIONS: Patterns between values\n4. RECOMMENDATIONS: Next steps and follow-up\n\nExplain in natural patient language. Indicate if anything requires urgent attention.";

            $systemPrompt = "Act as an expert clinical pathologist with excellent communication skills.
            Respond ALWAYS in English.

            Your task: analyze lab results, identify abnormalities,
            explain to the patient what they mean, give clear recommendations.

            IMPORTANT:
            - If any value is unclear, state it without inventing data
            - Be specific with values and reference ranges
            - Explain each parameter simply
            - Always include: 'This analysis requires validation by a certified physician'

            TONE: Friendly, professional, no unnecessary jargon.";
        }

        return [$systemPrompt, $userText];
    }

    protected function deleteSourceFiles(MedicalAnalysis $analysis, array $filePaths): void
    {
        $eliminados = 0;
        $fallidos = [];

        foreach ($filePaths as $path) {
            try {
                if (Storage::disk('private')->exists($path)) {
                    Storage::disk('private')->delete($path);
                    $eliminados++;
                }
            } catch (\Throwable $e) {
                $fallidos[] = $path;
                Log::warning("Análisis #{$analysis->id}: no se pudo borrar '{$path}': " . $e->getMessage());
            }
        }

        $analysis->update([
            'file_paths' => null,
            'file_path' => null,
        ]);

        Log::info("Análisis #{$analysis->id}: limpieza completada. Eliminados: {$eliminados}/" . count($filePaths));
    } 

    public function preparePayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:medical_analyses,id'
        ]);

        $id = strip_tags($request->order_id);
        $analysis = MedicalAnalysis::findOrFail($id);        

        $prefix = Carbon::now()->format('ymdH');                
        $random = strtoupper(Str::random(5));                                
        $paymentReference = $analysis->id . "-" . $prefix . "-" . $random;

        $analysis->update(['payment_id' => $paymentReference]);

        $amountInCents = (int) ($analysis->price * 100); 
        $currency = 'COP';

        $stringPayload = $paymentReference . $amountInCents . $currency . config('services.wompi.integrity_secret');
        $signatureIntegrity = hash('sha256', $stringPayload);

        return response()->json([
            'status' => 'success',
            'public_key' => config('services.wompi.public_key'),
            'currency' => $currency,
            'amount_in_cents' => $amountInCents,
            'reference' => $paymentReference,
            'signature_integrity' => $signatureIntegrity,
            'token' => $analysis->access_token,
            'redirect_url' => route('medical-analysis.payment.result', $analysis->access_token),
        ]);
    }

    private function convertPdfToImages(string $filePath, int $index, int $analysisId, &$tempFilesToCleanup, int $decimationFactor = 1): array
    {
        $images = [];
        
        try {
            $pdf = new Pdf($filePath);
            $totalPages = $pdf->pageCount();

            Log::info("Análisis #{$analysisId}: PDF tiene {$totalPages} página(s), decimación: {$decimationFactor}");

            for ($page = 1; $page <= $totalPages; $page++) {
                // ✅ Aplicar decimación
                if (($page - 1) % $decimationFactor !== 0) {
                    continue;
                }

                $tempImagePath = storage_path('app/temp/med_' . uniqid() . '_doc' . $index . '_p' . $page . '.jpg');

                if (!is_dir(dirname($tempImagePath))) {
                    mkdir(dirname($tempImagePath), 0755, true);
                }

                try {
                    $savedPaths = $pdf->selectPage($page)
                        ->format(OutputFormat::Jpg)
                        ->quality(90)
                        ->save($tempImagePath);

                    $savedPath = $savedPaths[0] ?? null;

                    if ($savedPath && file_exists($savedPath) && filesize($savedPath) > 0) {
                        $images[] = [
                            'base64' => base64_encode(file_get_contents($savedPath)),
                            'mime' => 'image/jpeg',
                        ];
                        $tempFilesToCleanup[] = $savedPath;
                    }
                } catch (\Throwable $e) {
                    Log::error("Análisis #{$analysisId}: fallo página {$page}: " . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            Log::error("Análisis #{$analysisId}: error procesando PDF: " . $e->getMessage());
        }

        return $images;
    }
}