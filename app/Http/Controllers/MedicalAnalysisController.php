<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessMedicalAnalysisAI;
use App\Mail\ExamAnalysisReady;
use App\Models\MedicalAnalysis;
use App\Models\PromoCode;
use App\Models\Setting;
use App\Services\AI\AIVisionManager;
use App\Services\MedicalAnalysisFilePurger;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\PdfToImage\Enums\OutputFormat;
use Spatie\PdfToImage\Pdf;

class MedicalAnalysisController extends Controller
{
    const SUPPORTED_LANGUAGES = ['es', 'en', 'fr', 'pt', 'de'];
    const DEFAULT_LANGUAGE = 'es';

    private const EXAM_TYPES = ['lab', 'xray', 'ultrasound', 'ct', 'mri', 'mammography', 'dicom'];
    private const IMAGING_TYPES = ['xray', 'ct', 'mri', 'ultrasound', 'mammography', 'dicom'];

    /** Precios por defecto si el panel de configuración aún no tiene el ajuste. */
    private const DEFAULT_PRICES = [
        'lab' => 12000,
        'xray' => 18000,
        'ultrasound' => 16000,
        'ct' => 30000,
        'mri' => 35000,
        'dicom' => 30000,
        'mammography' => 22000,
    ];

    /** Decimación por defecto (cada N-ésima página/imagen) si el panel no define otra. */
    private const DEFAULT_DECIMATION = [
        'ct' => 3,
        'mri' => 3,
        'xray' => 1,
        'ultrasound' => 1,
        'mammography' => 1,
        'dicom' => 1,
        'lab' => 2,
    ];

    private const PRIVATE_DISK = 'private';
    private const EXAM_DIR = 'medical-exams';

    private const SESSION_KEYS = [
        'medical_analysis_files',
        'medical_analysis_total_size',
        'medical_analysis_data',
        'medical_analysis_price',
    ];

    public function __construct() {}

    // =====================================================================
    // Páginas públicas y carga
    // =====================================================================

    /**
     * Mostrar página principal de análisis médicos
     */
    public function index()
    {
        $price = number_format($this->getPrices()['lab'], 0, ',', '.');

        return view('medical-analysis.index', ['price' => $price, ...$this->meta()]);
    }

    /**
     * Mostrar formulario de upload con precios dinámicos
     */
    public function showUploadForm()
    {
        return view('medical-analysis.upload', ['prices' => $this->getPrices(), ...$this->meta()]);
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
            'selected_language' => ['required', Rule::in(self::SUPPORTED_LANGUAGES)],
            'detected_exam_type' => ['required', Rule::in(self::EXAM_TYPES)],
            'reason_type' => 'nullable|in:routine,monitoring,symptoms,other',
            'reason_custom' => 'nullable|string|max:500',
        ]);

        // Si el usuario ya había subido archivos antes, se descartan: no deben quedar huérfanos.
        $this->discardTempUpload();

        $files = [];
        $totalSize = 0;

        foreach ($request->file('medical_files') as $file) {
            $files[] = [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'path' => $file->store('temp-uploads', 'local'),
            ];
            $totalSize += $file->getSize();
        }

        $price = $this->basePriceFor($validated['detected_exam_type']);

        session([
            'medical_analysis_files' => $files,
            'medical_analysis_total_size' => $totalSize,
            'medical_analysis_data' => $validated,
            'medical_analysis_price' => $price,
        ]);

        return response()->json([
            'status' => 'success',
            'price' => $price,
            'redirect_url' => route('medical-analysis.preview'),
        ]);
    }

    /**
     * ✅ PASO 2: Mostrar preview de la orden
     */
    public function preview()
    {
        $validated = session('medical_analysis_data', []);

        if (empty($validated)) {
            return redirect()->route('medical-analysis.upload')
                ->with('error', 'La sesión ha expirado. Por favor, carga tus archivos nuevamente.');
        }

        return view('medical-analysis.preview', [
            'files' => session('medical_analysis_files', []),
            'totalSize' => session('medical_analysis_total_size', 0),
            'examType' => $validated['detected_exam_type'],
            'price' => $this->basePriceFor($validated['detected_exam_type']),
            'email' => $validated['customer_email'],
            'language' => $validated['selected_language'],
            'reasonType' => $validated['reason_type'] ?? null,
            'reasonCustom' => $validated['reason_custom'] ?? null,
        ]);
    }

    // =====================================================================
    // Orden y pago
    // =====================================================================

    /**
     * ✅ PASO 3: Crear registro en BD
     *
     * Guarda archivos permanentemente y recalcula el precio EN EL SERVIDOR
     * (nunca se confía en el descuento que muestre el navegador).
     * - Total > 0: crea el análisis pendiente de pago y redirige a la pasarela (Wompi).
     * - Total = 0 (código del 100%): consume el cupo, crea el análisis ya pagado,
     *   dispara la IA y redirige al resultado sin pasar por Wompi.
     */
    public function processDocuments(Request $request)
    {
        $files = session('medical_analysis_files', []);
        $data = session('medical_analysis_data', []);

        if (empty($files) || empty($data)) {
            return $this->jsonError('Sesión expirada. Por favor, intenta de nuevo.', 400);
        }

        $detectedType = strtolower(trim($data['detected_exam_type']));
        $email = trim(strtolower($data['customer_email']));
        $language = strtolower($data['selected_language']);
        $reasonType = $data['reason_type'] ?? null;
        $reasonCustom = trim($data['reason_custom'] ?? '');

        if (!in_array($detectedType, self::EXAM_TYPES, true)) {
            return $this->jsonError("Tipo de examen no válido: {$detectedType}", 422);
        }

        // Precio base y descuento, calculados en el servidor
        $basePrice = $this->basePriceFor($detectedType);
        $promoInput = trim((string) $request->input('promotional_code', ''));

        [$promo, $discount, $price] = $this->resolvePricing($promoInput, $basePrice);

        // Si enviaron un código que ya no es válido, no se cobra un precio distinto al que vieron.
        if ($promoInput !== '' && !$promo) {
            return $this->jsonError('El código promocional ya no es válido. Quítalo o prueba con otro.', 422);
        }

        $isFree = $price === 0;

        // Guardar archivos en almacenamiento privado
        $storedPaths = $this->moveTempFilesToPrivate($files);

        if (empty($storedPaths)) {
            return $this->jsonError('Error al guardar los archivos.', 500);
        }

        // Orden gratis: el cupo se consume de forma atómica antes de crear el análisis.
        if ($isFree && $promo && !$this->consumePromo($promo)) {
            $this->deletePrivateFiles($storedPaths);

            return $this->jsonError('Este código ya no está disponible.', 422);
        }

        try {
            // El access_token lo genera el modelo (hook creating).
            $analysis = MedicalAnalysis::create([
                'file_paths' => json_encode($storedPaths),
                'exam_type' => $detectedType,
                'customer_email' => $email,
                'reason_type' => $reasonType,
                'reason_custom' => $reasonCustom,
                'promo_code' => $promo?->code,
                'price' => $price, // lo que realmente se cobra, ya con descuento
                'status' => $isFree ? 'processing' : 'pending',
                'payment_status' => $isFree ? 'completed' : 'pending',
                'analysis_language' => $language,
            ]);
        } catch (\Throwable $e) {
            Log::error('Orden de examen: no se pudo crear el registro', ['message' => $e->getMessage()]);

            // Se deshace todo: sin registro no deben quedar archivos y el cupo se devuelve.
            $this->deletePrivateFiles($storedPaths);
            if ($isFree && $promo) {
                PromoCode::whereKey($promo->id)->decrement('uses');
            }

            return $this->jsonError('No pudimos crear tu orden. Intenta nuevamente.', 500);
        }

        // Limpia la sesión y los archivos temporales
        $this->discardTempUpload();

        // Camino gratuito: dispara la IA y va directo al resultado
        if ($isFree) {
            ProcessMedicalAnalysisAI::dispatch($analysis);
            $this->notifyCustomer($analysis);

            return response()->json([
                'status' => 'success',
                'access_token' => $analysis->access_token,
                'redirect_url' => route('medical-analysis.show', $analysis->access_token),
            ]);
        }

        // Camino de pago
        return response()->json([
            'status' => 'success',
            'access_token' => $analysis->access_token,
            'redirect_url' => route('medical-analysis.payment-gateway', $analysis->access_token),
        ]);
    }

    /**
     * ✅ PASO 4: Mostrar página de pago con widget Wompi
     *
     * El precio de la orden ya incluye el descuento (se calculó en processDocuments),
     * así que aquí NO se vuelve a aplicar ningún código.
     */
    public function paymentGateway($token)
    {
        $analysis = MedicalAnalysis::where('access_token', $token)->firstOrFail();

        if ($analysis->payment_status === 'completed') {
            return redirect()->route('medical-analysis.show', $token)
                ->with('info', 'El pago ya fue procesado.');
        }

        $finalAmount = (int) round((float) $analysis->price);

        if ($finalAmount <= 0) {
            return redirect()->route('medical-analysis.show', $token)
                ->with('error', 'Esta orden no requiere pago.');
        }

        // Referencia de pago: "{id}-{ymdH}-{aleatorio}" (processPaymentResult valida el prefijo "{id}-")
        $paymentReference = $analysis->id . '-' . Carbon::now()->format('ymdH') . '-' . strtoupper(Str::random(5));
        $analysis->update(['payment_id' => $paymentReference]);

        $amountInCents = $finalAmount * 100;
        $currency = 'COP';

        $signatureIntegrity = hash(
            'sha256',
            $paymentReference . $amountInCents . $currency . config('services.wompi.integrity_secret')
        );

        return view('medical-analysis.payment-gateway', [
            'analysis' => $analysis,
            'price' => $finalAmount,
            'wompi' => [
                'public_key' => config('services.wompi.public_key'),
                'currency' => $currency,
                'amount_in_cents' => $amountInCents,
                'reference' => $paymentReference,
                'signature_integrity' => $signatureIntegrity,
                'redirect_url' => route('medical-analysis.payment-result', $token),
            ],
        ]);
    }

    /**
     * ✅ PASO 5: Procesar resultado del pago (Callback de Wompi)
     *
     * Valida la transacción con Wompi (estado, referencia y monto).
     * Si APROBADO → marca pagado de forma atómica, dispara la IA y avisa al cliente.
     * Si RECHAZADO → permite reintentar el pago.
     */
    public function processPaymentResult(Request $request, $token)
    {
        $analysis = MedicalAnalysis::where('access_token', $token)->firstOrFail();
        $transactionId = $request->query('id');

        if (!$transactionId) {
            return redirect()->route('home')->with('error', 'Falta el identificador del pago.');
        }

        $paymentStatus = 'ERROR';

        try {
            $response = Http::timeout(15)
                ->withToken(config('services.wompi.public_key'))
                ->get(config('services.wompi.endpoint') . "/transactions/{$transactionId}");

            if ($response->successful()) {
                $transaction = $response->json('data') ?? [];
                $paymentStatus = $transaction['status'] ?? 'ERROR';

                if ($paymentStatus === 'APPROVED') {
                    if ($this->transactionMatches($transaction, $analysis)) {
                        $this->markAsPaid($analysis, (string) $transactionId);
                    } else {
                        // Aprobada, pero de otra orden u otro monto: no se acredita.
                        Log::warning('Pago aprobado que no coincide con la orden', [
                            'analysis_id' => $analysis->id,
                            'transaction_id' => $transactionId,
                        ]);
                        $paymentStatus = 'ERROR';
                    }
                } elseif ($paymentStatus === 'PENDING') {
                    $this->setPaymentStatus($analysis, 'pending');
                } else {
                    $this->setPaymentStatus($analysis, 'failed');
                }
            }
        } catch (\Throwable $e) {
            Log::error('Error en Callback de Wompi: ' . $e->getMessage());
            $paymentStatus = 'ERROR';
            $this->setPaymentStatus($analysis, 'error');
        }

        return view('medical-analysis.payment-result', [
            'analysis' => $analysis->fresh(),
            'paymentStatus' => $paymentStatus,
        ]);
    }

    /**
     * ✅ PASO 6: Ver informe completo (después de pagar y procesar con IA)
     */
    public function showResult($token)
    {
        $analysis = MedicalAnalysis::where('access_token', $token)->firstOrFail();

        if ($analysis->payment_status !== 'completed') {
            return redirect()->route('medical-analysis.payment-gateway', $token)
                ->with('error', 'Debes procesar el pago primero.');
        }

        return view('medical-analysis.show', [
            'analysis' => $analysis,
            'price' => $analysis->price,
        ]);
    }

    // =====================================================================
    // Códigos promocionales
    // =====================================================================

    /**
     * Validar código promocional (solo para mostrar el descuento: el precio real
     * se recalcula en processDocuments).
     */
    public function validatePromoCode(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $basePrice = $this->basePriceFromSession();

        if ($basePrice === null) {
            return response()->json(['valid' => false, 'message' => 'La sesión expiró. Carga tus archivos nuevamente.']);
        }

        $code = PromoCode::where('code', strtoupper(trim($validated['code'])))->first();

        if (!$code) {
            return response()->json(['valid' => false, 'message' => 'Código no encontrado.']);
        }

        if (!$code->is_active) {
            return response()->json(['valid' => false, 'message' => 'Código inactivo.']);
        }

        if ($code->starts_at && now()->lt(Carbon::parse($code->starts_at))) {
            return response()->json(['valid' => false, 'message' => 'Código aún no está disponible.']);
        }

        if ($code->expires_at && now()->gt(Carbon::parse($code->expires_at))) {
            return response()->json(['valid' => false, 'message' => 'Código expirado.']);
        }

        if ($code->max_uses !== null && $code->uses >= $code->max_uses) {
            return response()->json(['valid' => false, 'message' => 'Código agotado.']);
        }

        $discountAmount = $code->discountFor($basePrice);
        $finalPrice = $basePrice - $discountAmount;

        return response()->json([
            'valid' => true,
            'discount_type' => $code->type,
            'discount_value' => (float) $code->reward,
            'discount_amount' => $discountAmount,
            'final_price' => $finalPrice,
            'is_free' => $finalPrice === 0,
            'message' => 'Código aplicado correctamente.',
        ]);
    }

    // =====================================================================
    // Eliminación
    // =====================================================================

    /**
     * Elimina un examen y TODOS sus archivos.
     *
     * Borra primero los archivos del disco privado y solo después el registro: si algún archivo
     * no se puede borrar, el registro se conserva para poder reintentar y no quedan archivos huérfanos.
     *
     * IMPORTANTE: la ruta debe ir protegida (autenticación y permiso de administrador).
     * El examen se resuelve por access_token, no por id numérico.
     */
    public function destroy(MedicalAnalysis $analysis): RedirectResponse
    {
        if ($analysis->status === 'processing') {
            return redirect()->back()->with('error', 'El examen se está procesando. Inténtalo cuando termine.');
        }

        $result = $this->purger()->purge($analysis);

        if (!empty($result['failed'])) {
            return redirect()->back()->with('error', 'No se pudieron eliminar todos los archivos del examen. No se eliminó el registro; inténtalo de nuevo.');
        }

        $analysis->delete();

        Log::info('Examen eliminado', [
            'analysis_id' => $analysis->id,
            'files_deleted' => $result['deleted'],
        ]);

        return redirect()->back()->with('success', 'El examen y sus archivos han sido eliminados correctamente.');
    }

    // =====================================================================
    // Análisis con IA (lo ejecuta el Job ProcessMedicalAnalysisAI)
    // =====================================================================

    /**
     * Procesar archivos, generar imágenes, enviar a la IA y guardar el resultado.
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

        $filePaths = $this->purger()->paths($analysis);

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
                : $this->providerOrder($analysis->exam_type);

            $outcome = AIVisionManager::analyzeWithFallback($systemPrompt, $userText, $images, $order);

            $analysis->update([
                'ai_response' => $outcome['result'],
                'ai_provider' => $outcome['provider_used'],
                'analysis_language' => $selectedLanguage,
                'status' => 'completed',
            ]);

            $this->deleteSourceFiles($analysis);
        } catch (\Throwable $e) {
            Log::error("Análisis #{$analysis->id}: fallo IA: " . $e->getMessage());
            $analysis->update(['status' => 'failed']);
        }
    }

    // =====================================================================
    // Helpers privados
    // =====================================================================

    private function meta(): array
    {
        return [
            'meta_title_medicalAnalysis' => 'OpenDoctorOnline | Interpreta tus exámenes médicos con Inteligencia Artificial',
            'meta_description_medicalAnalysis' => 'Análisis médico online con IA. Laboratorio e imagenología (radiografías, tomografías, resonancias). Diagnóstico instantáneo, cita médica virtual y presencial disponible.',
        ];
    }

    private function jsonError(string $message, int $status)
    {
        return response()->json(['status' => 'error', 'message' => $message], $status);
    }

    private function purger(): MedicalAnalysisFilePurger
    {
        return app(MedicalAnalysisFilePurger::class);
    }

    /**
     * Precios por tipo: valores por defecto, reemplazados por los del panel de configuración.
     */
    private function getPrices(): array
    {
        $stored = Setting::where('group', 'pricing')
            ->pluck('value', 'key')
            ->mapWithKeys(fn ($value, $key) => [str_replace(['exam_type_', '_price'], '', $key) => (int) $value])
            ->filter(fn (int $value) => $value > 0)
            ->all();

        return array_merge(self::DEFAULT_PRICES, $stored);
    }

    private function basePriceFor(string $examType): int
    {
        return (int) ($this->getPrices()[$examType] ?? self::DEFAULT_PRICES['lab']);
    }

    /** Precio base del examen que el usuario tiene en sesión, o null si la sesión expiró. */
    private function basePriceFromSession(): ?int
    {
        $examType = session('medical_analysis_data.detected_exam_type');

        return $examType ? $this->basePriceFor($examType) : null;
    }

    /**
     * Recalcula el precio en el servidor.
     * Devuelve [?PromoCode $promo, int $descuento, int $precioFinal].
     */
    private function resolvePricing(string $code, int $basePrice): array
    {
        $promo = null;
        $discount = 0;

        if ($code !== '') {
            $candidate = PromoCode::where('code', strtoupper($code))->first();

            if ($candidate && $candidate->isRedeemable()) {
                $promo = $candidate;
                $discount = $candidate->discountFor($basePrice);
            }
        }

        return [$promo, $discount, $basePrice - $discount];
    }

    /**
     * Consume un uso del código de forma atómica.
     * Devuelve false si el cupo se agotó entre la validación y el consumo.
     */
    private function consumePromo(PromoCode $promo): bool
    {
        return PromoCode::whereKey($promo->id)
            ->where(fn ($q) => $q->whereNull('max_uses')->orWhereColumn('uses', '<', 'max_uses'))
            ->increment('uses') > 0;
    }

    /**
     * Evita acreditar una transacción aprobada de otra orden o de otro monto.
     */
    private function transactionMatches(array $transaction, MedicalAnalysis $analysis): bool
    {
        $reference = (string) ($transaction['reference'] ?? '');

        if ($reference === '' || !str_starts_with($reference, $analysis->id . '-')) {
            return false;
        }

        $expectedCents = (int) round((float) $analysis->price * 100);

        return (int) ($transaction['amount_in_cents'] ?? -1) === $expectedCents;
    }

    /**
     * Marca el pago como completado de forma atómica (si dos requests llegan a la vez,
     * solo el primero avanza), consume el código promocional y dispara el análisis.
     */
    private function markAsPaid(MedicalAnalysis $analysis, string $transactionId): void
    {
        $updated = MedicalAnalysis::whereKey($analysis->id)
            ->where('payment_status', '!=', 'completed')
            ->update([
                'payment_status' => 'completed',
                'status' => 'processing',
                'wompi_transaction_id' => $transactionId,
            ]);

        if (!$updated) {
            return;
        }

        if ($analysis->promo_code) {
            $promo = PromoCode::where('code', strtoupper($analysis->promo_code))->first();

            // El pago ya se aprobó: si el cupo se agotó justo ahora, no se anula nada.
            if ($promo) {
                $this->consumePromo($promo);
            }
        }

        $analysis->refresh();

        ProcessMedicalAnalysisAI::dispatch($analysis);
        $this->notifyCustomer($analysis);
    }

    /** Cambia el estado de pago salvo que el pago ya esté completado. */
    private function setPaymentStatus(MedicalAnalysis $analysis, string $status): void
    {
        MedicalAnalysis::whereKey($analysis->id)
            ->where('payment_status', '!=', 'completed')
            ->update(['payment_status' => $status]);
    }

    /** Un fallo del correo nunca debe afectar al pago ni al análisis. */
    private function notifyCustomer(MedicalAnalysis $analysis): void
    {
        if (!$analysis->customer_email) {
            return;
        }

        try {
            Mail::to($analysis->customer_email)->send(new ExamAnalysisReady($analysis));
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar el correo del análisis', [
                'analysis_id' => $analysis->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Copia los archivos temporales (disco local) al disco privado definitivo, sin cargarlos en memoria.
     * Devuelve las rutas finales.
     */
    private function moveTempFilesToPrivate(array $files): array
    {
        $local = Storage::disk('local');
        $private = Storage::disk(self::PRIVATE_DISK);
        $stored = [];

        foreach ($files as $fileData) {
            $tempPath = $fileData['path'] ?? null;

            if (!$tempPath || !$local->exists($tempPath)) {
                continue;
            }

            $stream = $local->readStream($tempPath);

            if (!is_resource($stream)) {
                continue;
            }

            $finalPath = self::EXAM_DIR . '/' . basename($tempPath);
            $private->writeStream($finalPath, $stream);
            fclose($stream);

            $stored[] = $finalPath;
        }

        return $stored;
    }

    private function deletePrivateFiles(array $paths): void
    {
        foreach ($paths as $path) {
            Storage::disk(self::PRIVATE_DISK)->delete($path);
        }
    }

    /**
     * Descarta la subida temporal en curso: borra los archivos temporales y limpia la sesión.
     */
    private function discardTempUpload(): void
    {
        foreach (session('medical_analysis_files', []) as $file) {
            if (!empty($file['path'])) {
                Storage::disk('local')->delete($file['path']);
            }
        }

        session()->forget(self::SESSION_KEYS);
    }

    /**
     * Orden de proveedores de IA según los ajustes del panel (primario y fallback).
     * Laboratorio e imagenología tienen cada uno el suyo.
     */
    private function providerOrder(string $examType): array
    {
        $group = in_array($examType, self::IMAGING_TYPES, true) ? 'imaging' : 'lab';
        $providers = ['gpt-4o' => 'openai', 'claude-sonnet-4-5' => 'claude'];

        $primaryModel = Setting::where('key', "{$group}_ai_primary_model")->value('value');
        $fallbackModel = Setting::where('key', "{$group}_ai_fallback_model")->value('value');

        $primary = $providers[$primaryModel] ?? 'claude';
        $fallback = $providers[$fallbackModel] ?? 'openai';

        return array_values(array_unique([$primary, $fallback]));
    }

    /**
     * Generar prompts para IA: prompt en español más la instrucción de idioma.
     */
    private function generatePrompts(string $language, string $clinicalReason, string $additionalDetails, ?string $examType = null): array
    {
        [$systemPrompt, $userTemplate] = $this->getPromptAI($clinicalReason, $additionalDetails, $examType);

        $languageInstructions = match ($language) {
            'en' => 'Responde SOLO en Ingles.',
            'fr' => 'Responde SOLO en Frances.',
            'pt' => 'Responde SOLO en Portugues.',
            'de' => 'Responde SOLO en Alemán.',
            default => 'Responde SOLO en Español.'
        };

        $userText = "CONTEXTO DEL PACIENTE:\n- Motivo: {$clinicalReason}\n- Detalles: {$additionalDetails}\n\n" . $userTemplate . "\n\n{$languageInstructions}";

        return [$systemPrompt, $userText];
    }

    /**
     * Prompts en español (diferenciados por tipo de examen)
     */
    private function getPromptAI(string $clinicalReason, string $additionalDetails, ?string $examType = null): array
    {
        $patientContext = "CONTEXTO DEL PACIENTE:\n- Motivo: {$clinicalReason}\n- Detalles: {$additionalDetails}";

        if (in_array($examType, self::IMAGING_TYPES, true)) {
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
     * Elimina los archivos de origen después de procesar y limpia la referencia en la BD.
     */
    protected function deleteSourceFiles(MedicalAnalysis $analysis): void
    {
        $result = $this->purger()->purge($analysis);

        $analysis->update(['file_paths' => null]);

        Log::info("Análisis #{$analysis->id}: limpieza completada. Eliminados: {$result['deleted']}, fallidos: " . count($result['failed']));
    }

    /**
     * Procesar archivos en imágenes para IA. Las imágenes temporales de PDF se borran siempre.
     */
    private function processFilesIntoImages(array $filePaths, int $analysisId, string $examType): array
    {
        $images = [];
        $tempFilesToCleanup = [];
        $decimationFactor = $this->getDecimationFactor($examType);

        try {
            foreach ($filePaths as $index => $path) {
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

                if ($extension === 'pdf') {
                    $filePath = Storage::disk(self::PRIVATE_DISK)->path($path);
                    $images = array_merge($images, $this->convertPdfToImages($filePath, $index, $analysisId, $tempFilesToCleanup, $decimationFactor));
                } else {
                    $content = Storage::disk(self::PRIVATE_DISK)->get($path);

                    if (!empty($content)) {
                        $images[] = [
                            'base64' => base64_encode($content),
                            'mime' => $this->getMimeType($extension),
                        ];
                    }
                }
            }
        } finally {
            foreach ($tempFilesToCleanup as $tempFile) {
                @unlink($tempFile);
            }
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
     * Factor de decimación: el del panel de configuración, o el valor por defecto del tipo.
     */
    private function getDecimationFactor(string $examType): int
    {
        $stored = Setting::where('key', "{$examType}_auto_decimation")->value('value');

        $factor = $stored !== null
            ? (int) $stored
            : (self::DEFAULT_DECIMATION[$examType] ?? 2);

        return max(1, $factor);
    }

    /**
     * Obtener MIME type según extensión
     */
    private function getMimeType(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'dcm', 'dicom' => 'application/dicom',
            default => 'application/octet-stream'
        };
    }
}