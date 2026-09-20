<?php

namespace App\Http\Controllers;

use App\Models\MedicalAnalysis;
use App\Models\Setting;
use App\Models\PromoCode;
use App\Services\AnalysisPricingService;
use App\Services\AI\AIVisionManager;
use App\Jobs\ProcessMedicalAnalysisAI;
use App\Mail\ExamAnalysisReady;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\PdfToImage\Pdf;
use Spatie\PdfToImage\Enums\OutputFormat;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MedicalAnalysisController extends Controller
{
    const SUPPORTED_LANGUAGES = ['es', 'en', 'fr', 'pt', 'de'];
    const DEFAULT_LANGUAGE = 'es';

    public function __construct() {}

    /**
     * Mostrar página principal de análisis médicos
     */
    public function index()
    {
        $priceSetting = Setting::get('exam_type_lab_price', 12000);
        $price = number_format($priceSetting, 0, ',', '.');
        $meta_title_medicalAnalysis = 'OpenDoctorOnline | Interpreta tus exámenes médicos con Inteligencia Artificial';
        $meta_description_medicalAnalysis = 'Análisis médico online con IA. Laboratorio e imagenología (radiografías, tomografías, resonancias). Diagnóstico instantáneo, cita médica virtual y presencial disponible.';

        return view('medical-analysis.index', compact('price', 'meta_title_medicalAnalysis', 'meta_description_medicalAnalysis'));
    }

    /**
     * Mostrar formulario de upload con precios dinámicos
     */
    public function showUploadForm()
    {
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

    /**
     * ✅ PASO 1: Guardar archivos temporales en sesión
     * 
     * Valida archivos, calcula precio, guarda datos en sesión
     * Redirige a preview para que usuario revise su orden
     */
    public function beforePreview(Request $request)
    {
        $validated = $request->validate([
            'medical_files' => 'required|array|max:5',
            'medical_files.*' => 'file|mimes:pdf,jpg,jpeg,png,dcm|max:10240',
            'customer_email' => 'required|email',
            'selected_language' => 'required|in:es,en,fr,pt,de',
            'detected_exam_type' => 'required|in:lab,xray,ultrasound,ct,mri,mammography,dicom',
            'reason_type' => 'nullable|in:routine,monitoring,symptoms,other',
            'reason_custom' => 'nullable|string|max:500',
        ]);

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

        // Obtener precio según tipo de examen
        $examType = $validated['detected_exam_type'];
        
        $prices = $this->getPrices();
        $price = $prices[$examType] ?? 12000;

        // Guardar TODO en sesión
        session([
            'medical_analysis_files' => $files,
            'medical_analysis_total_size' => $totalSize,
            'medical_analysis_data' => $validated,
            'medical_analysis_price' => $price,
        ]);

        return response()->json([
            'status' => 'success',
            'price'  => $price,
            'redirect_url' => route('medical-analysis.preview')
        ]);
    }

    /**
     * ✅ PASO 2: Mostrar preview de la orden
     * 
     * Lee sesión, muestra archivos, precio y permite aplicar código promo
     * Usuario decide si procede o vuelve atrás
     */
    public function preview()
    {
        $files = session('medical_analysis_files', []);
        $totalSize = session('medical_analysis_total_size', 0);
        $validated = session('medical_analysis_data', []);
        $price = session('medical_analysis_price', 12000);

        if (empty($validated)) {
            return redirect()->route('medical-analysis.upload')
                ->with('error', 'La sesión ha expirado. Por favor, carga tus archivos nuevamente.');
        }

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
     * ✅ PASO 3: Crear registro en BD con status "pending_payment"
     * 
     * Guarda archivos permanentemente, crea análisis SIN procesar IA aún
     * Redirige a página de pago (Wompi)
     * NO dispara IA hasta que el pago sea confirmado
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

        // Validar tipo de examen
        if (!in_array($detectedType, ['lab', 'xray', 'ultrasound', 'ct', 'mri', 'mammography', 'dicom'])) {
            return response()->json([
                'status' => 'error',
                'message' => "Tipo de examen no válido: {$detectedType}"
            ], 422);
        }

        // Obtener precio
        $prices = $this->getPrices();
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

        // ✅ CREAR REGISTRO CON STATUS "pending_payment"
        $analysis = MedicalAnalysis::create([
            'file_paths' => json_encode($storedPaths),
            'exam_type' => $detectedType,
            'customer_email' => $email,
            'reason_type' => $reasonType,
            'reason_custom' => $reasonCustom,
            'promo_code' => $promoCode,
            'price' => $price,
            'status' => 'pending',
            'payment_status' => 'pending',
            'access_token' => Str::random(32),
            'analysis_language' => $language
        ]);

        // Limpiar sesión
        session()->forget(['medical_analysis_files', 'medical_analysis_total_size', 'medical_analysis_data', 'medical_analysis_price']);

        // Limpiar archivos temporales
        foreach ($files as $fileData) {
            Storage::disk('local')->delete($fileData['path']);
        }

        // ✅ REDIRIGE A PÁGINA DE PAGO
        return response()->json([
            'status' => 'success',
            'access_token' => $analysis->access_token,
            'redirect_url' => route('medical-analysis.payment-gateway', $analysis->access_token)
        ]);
    }

    /**
     * ✅ PASO 4: Mostrar página de pago con widget Wompi
     * 
     * Renderiza vista con widget de Wompi para que usuario pague
     * Genera firma de integridad y datos para la transacción
     */
    public function paymentGateway($token)
    {
        $analysis = MedicalAnalysis::where('access_token', $token)->firstOrFail();

        if ($analysis->payment_status === 'completed') {
            return redirect()->route('medical-analysis.show', $token)
                ->with('info', 'El pago ya fue procesado.');
        }

        // Calcular monto final con descuento si aplica
        $finalAmount = $analysis->price;
        if ($analysis->promo_code) {
            $promoCode = PromoCode::where('code', $analysis->promo_code)->first();
            if ($promoCode) {
                if ($promoCode->discount_type === 'percentage') {
                    $discount = ceil($finalAmount * ($promoCode->discount_value / 100));
                } else {
                    $discount = $promoCode->discount_value;
                }
                $finalAmount = max(0, $finalAmount - $discount);
            }
        }

        // Generar referencia de pago dinámica (igual que el método antiguo)
        $prefix = Carbon::now()->format('ymdH');                
        $random = strtoupper(Str::random(5));                                
        $paymentReference = $analysis->id . "-" . $prefix . "-" . $random;

        // Guardar payment_id en BD
        $analysis->update(['payment_id' => $paymentReference]);

        // Generar firma Wompi (igual que el método antiguo)
        $amountInCents = (int) ($finalAmount * 100);
        $currency = "COP";
        $publicKey = config('services.wompi.public_key');
        $integritySecret = config('services.wompi.integrity_secret');
        
        $stringPayload = $paymentReference . $amountInCents . $currency . $integritySecret;
        $signatureIntegrity = hash('sha256', $stringPayload);

        return view('medical-analysis.payment-gateway', [
            'analysis' => $analysis,
            'price' => $finalAmount,
            'wompi' => [
                'public_key' => $publicKey,
                'currency' => $currency,
                'amount_in_cents' => $amountInCents,
                'reference' => $paymentReference,
                'signature_integrity' => $signatureIntegrity,
                'redirect_url' => route('medical-analysis.payment-result', $token)
            ]
        ]);
    }

    /**
     * ✅ PASO 5: Procesar resultado del pago (Callback de Wompi)
     * 
     * Valida transacción con Wompi API
     * Si APROBADO → Dispara Job ProcessMedicalAnalysisAI, envía email
     * Si RECHAZADO → Permite reintentar pago
     * Renderiza vista con resultado del pago
     */
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

                // ✅ GUARDAR TRANSACTION ID
                $analysis->update(['wompi_transaction_id' => $transactionId]);

                if ($paymentStatus === 'APPROVED') {
                    if ($analysis->payment_status !== 'completed') {
                        // ✅ ACTUALIZAR STATUS A "processing"
                        $analysis->update([
                            'payment_status' => 'completed',
                            'status' => 'processing'
                        ]);

                        // ✅ DISPARAR JOB ASINCRÓNICO CON IA
                        ProcessMedicalAnalysisAI::dispatch($analysis);

                        if ($analysis->customer_email) {
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
     * ✅ PASO 6: Ver informe completo (después de pagar y procesar con IA)
     * 
     * Verifica que el pago fue aprobado
     * Muestra pantalla de espera si aún se procesa con IA
     * Muestra informe completo cuando IA termina
     */
    public function showResult($token)
    {
        $analysis = MedicalAnalysis::where('access_token', $token)->firstOrFail();

        // Verificar que el pago fue aprobado
        if ($analysis->payment_status !== 'completed') {
            return redirect()->route('medical-analysis.payment-gateway', $token)
                ->with('error', 'Debes procesar el pago primero.');
        }

        $price = $analysis->price;

        return view('medical-analysis.show', [
            'analysis' => $analysis,
            'price' => $price
        ]);
    }

    /**
     * Validar código promocional
     * 
     * Verifica código: estado, fechas, límite de uso
     * Calcula descuento según tipo (porcentaje o cantidad fija)
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
     * Procesar análisis con IA (se ejecuta desde Job asincrónico)
     * 
     * Llamado por ProcessMedicalAnalysisAI Job después de pago confirmado
     * Procesa archivos, genera imágenes, envía a IA y guarda resultado
     */
    public function analyzeWithAI(MedicalAnalysis $analysis, ?string $provider = null, bool $withFallback = true, string $selectedLanguage = self::DEFAULT_LANGUAGE)
    {
        $selectedLanguage = trim(strtolower($selectedLanguage));
        if (!in_array($selectedLanguage, self::SUPPORTED_LANGUAGES)) {
            Log::warning("Análisis #{$analysis->id}: idioma no soportado, usando default.");
            $selectedLanguage = self::DEFAULT_LANGUAGE;
        }

        //modificar mas adelante esto en ingles
        $reasons = [
            'routine'    => 'Control de rutina anual o chequeo preventivo.',
            'monitoring' => 'Seguimiento continuo de una patología médica existente.',
            'symptoms'   => 'Evaluación motivada por sintomatología reciente del paciente.',
            'other'      => 'Motivos complementarios.'
        ];

        $clinicalReason = $reasons[$analysis->reason_type] ?? $analysis->reason_type;
        $additionalDetails = $analysis->reason_custom ?? 'No se proporcionaron detalles adicionales.';

        [$systemPrompt, $userText] = $this->generatePrompts($selectedLanguage, $clinicalReason, $additionalDetails, $analysis->exam_type);

        $filePaths = json_decode($analysis->file_paths, true) ?? [];

        if (empty($filePaths)) {
            Log::error("Análisis #{$analysis->id}: no tiene archivos asociados.");
            $analysis->update(['status' => 'failed']);
            return;
        }

        // Procesar archivos a imágenes
        $images = $this->processFilesIntoImages($filePaths, $analysis->id, $analysis->exam_type);

        if (empty($images)) {
            Log::error("Análisis #{$analysis->id}: no se generaron imágenes.");
            $analysis->update(['status' => 'failed']);
            return;
        }

        // Límite máximo de imágenes
        if (count($images) > 40) {
            Log::warning("Análisis #{$analysis->id}: " . count($images) . " imágenes, truncadas a 40.");
            $images = array_slice($images, 0, 40);
        }

        try {
            $order = $provider
                ? array_unique([$provider, $provider === 'claude' ? 'openai' : 'claude'])
                : ['claude', 'openai'];

            $outcome = AIVisionManager::analyzeWithFallback($systemPrompt, $userText, $images, $order);

            $aiResult = $outcome['result'];
            $providerUsed = $outcome['provider_used'];

            $analysis->update([
                'ai_response' => $aiResult,
                'ai_provider' => $providerUsed,
                'analysis_language' => $selectedLanguage,
                'status' => 'completed',
            ]);

            $this->deleteSourceFiles($analysis, $filePaths);
        } catch (\Throwable $e) {
            Log::error("Análisis #{$analysis->id}: fallo IA: " . $e->getMessage());
            $analysis->update(['status' => 'failed']);
        }
    }

    /**
     * Generar prompts para IA (SIMPLIFICADO)
     * 
     * Prompt en español y agrega instrucción de idioma
     * Claude entrega el resultado en el idioma solicitado
     */
    private function generatePrompts(string $language, string $clinicalReason, string $additionalDetails, ?string $examType = null): array
    {
        // ✅ Cargar prompts en español (única fuente de verdad)
        [$systemPrompt, $userTemplate] = $this->getPromptAI($clinicalReason, $additionalDetails, $examType);

        // ✅ Agregar instrucción de idioma al final
        $languageInstructions = match($language) {
            'en' => 'Responde SOLO en Ingles.',
            'fr' => 'Responde SOLO en Frances.',
            'pt' => 'Responde SOLO en Portugues.',
            'de' => 'Responde SOLO en Alemán.',
            default => 'Responde SOLO en Español.'
        };

        $patientContext = "CONTEXTO DEL PACIENTE";
        $userText = "{$patientContext}:\n- Motivo: {$clinicalReason}\n- Detalles: {$additionalDetails}\n\n" . $userTemplate . "\n\n{$languageInstructions}";

        return [$systemPrompt, $userText];
    }

    /**
     * Prompts en español (diferenciados por tipo de examen)
     */
    private function getPromptAI(string $clinicalReason, string $additionalDetails, ?string $examType = null): array
    {
        $patientContext = "CONTEXTO DEL PACIENTE:\n- Motivo: {$clinicalReason}\n- Detalles: {$additionalDetails}";

        if (in_array($examType, ['xray', 'ct', 'mri', 'ultrasound', 'mammography', 'dicom'])) {
            $userText = "{$patientContext}\n\nAnaliza este estudio de imagenología como un experto radiólogo. Proporciona:\n\n1. HALLAZGOS CLAVE: Las observaciones más importantes\n2. ÁREAS DE INTERÉS CLÍNICO: Qué requiere seguimiento\n3. IMPRESIÓN RADIOLÓGICA: Posibles diagnósticos\n4. RECOMENDACIONES: Estudios de seguimiento y vigilancia\n\nSé preciso pero accesible para el paciente. Indica claramente si algo requiere atención urgente.";

            $systemPrompt = "Actúa como un radiólogo clínico experto con excelentes habilidades de comunicación.

            Tu tarea: analizar imagenología, identificar hallazgos relevantes,
            explicar al paciente qué significan, dar recomendaciones claras.

            IMPORTANTE:
            - Si la imagen no es legible, indícalo sin inventar hallazgos
            - Sé específico: localización, tamaño, características de los hallazgos
            - Explica de forma sencilla qué es cada hallazgo
            - Siempre incluye descargo: 'Este análisis requiere validación por radiólogo certificado'

            TONO: Amable, profesional, sin tecnicismos innecesarios.";
        } else {
            $userText = "{$patientContext}\n\nAnaliza visualmente estos resultados de laboratorio como un médico especialista. Proporciona:\n\n1. PARÁMETROS ANORMALES: Cuáles están fuera de rango\n2. INTERPRETACIÓN: Qué significan estos resultados\n3. CORRELACIONES: Patrones entre valores\n4. RECOMENDACIONES: Próximos pasos y seguimiento\n\nExplica en lenguaje natural de paciente. Indica si hay algo que requiera atención urgente.";

            $systemPrompt = "Actúa como un médico patólogo clínico experto con excelente comunicación humana.

            Tu tarea: analizar resultados de laboratorio, identificar anormalidades,
            explicar al paciente qué significan, dar recomendaciones claras.

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
     * Eliminar archivos de origen después de procesar
     */
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
            'file_path'  => null,
        ]);

        Log::info("Análisis #{$analysis->id}: limpieza completada. Eliminados: {$eliminados}/" . count($filePaths));
    }

    /**
     * Procesar archivos en imágenes para IA
     */
    private function processFilesIntoImages(array $filePaths, int $analysisId, string $examType): array
    {
        $images = [];
        $tempFilesToCleanup = [];
        $decimationFactor = $this->getDecimationFactor($examType);

        foreach ($filePaths as $index => $path) {
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if ($extension === 'pdf') {
                $filePath = Storage::disk('private')->path($path);
                $images = array_merge($images, $this->convertPdfToImages($filePath, $index, $analysisId, $tempFilesToCleanup, $decimationFactor));
            } else {
                $content = Storage::disk('private')->get($path);
                if (!empty($content)) {
                    $images[] = [
                        'base64' => base64_encode($content),
                        'mime' => $this->getMimeType($extension),
                    ];
                }
            }
        }

        foreach ($tempFilesToCleanup as $tempFile) {
            @unlink($tempFile);
        }

        return $images;
    }

    /**
     * Convertir PDF a imágenes
     */
    private function convertPdfToImages(string $filePath, int $index, int $analysisId, &$tempFilesToCleanup, int $decimationFactor = 1): array
    {
        $images = [];
        
        try {
            $pdf = new Pdf($filePath);
            $totalPages = $pdf->pageCount();

            Log::info("Análisis #{$analysisId}: PDF tiene {$totalPages} página(s), decimación: {$decimationFactor}");

            for ($page = 1; $page <= $totalPages; $page++) {
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

    /**
     * Obtener factor de decimación según tipo de examen
     */
    private function getDecimationFactor(string $examType): int
    {
        return match($examType) {
            'ct', 'mri' => 3,
            'xray', 'ultrasound', 'mammography', 'dicom' => 1,
            default => 2
        };
    }

    /**
     * Obtener MIME type según extensión
     */
    private function getMimeType(string $extension): string
    {
        return match(strtolower($extension)) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'dcm', 'dicom' => 'application/dicom',
            default => 'application/octet-stream'
        };
    }

    /**
     * Obtener precios dinámicos desde settings
     */
    private function getPrices(): array
    {
        return Setting::where('group', 'pricing')
            ->pluck('value', 'key')
            ->mapWithKeys(function($value, $key) {
                // Convierte: exam_type_lab_price → lab
                $examType = str_replace(['exam_type_', '_price'], '', $key);
                return [$examType => (int)$value];
            })
            ->toArray();
    }
}