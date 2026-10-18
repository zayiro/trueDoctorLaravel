<?php

namespace App\Http\Controllers;

use Anthropic\Client;
use App\Jobs\SkinAnalysisJob;
use App\Mail\SkinAnalysisReceived;
use App\Models\PromoCode;
use App\Models\SkinAnalysis;
use App\Services\Wompi\WompiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SkinAnalysisController extends Controller
{
    // Disco privado: las fotos son datos sensibles y NUNCA deben ir al disco público.
    private const DISK = 'private';
    private const TEMP_DIR = 'skin-analysis/temp';

    private const BASE_PRICE = 9000;
    private const WOMPI_COMMISSION_RATE = 0.03;
    private const CURRENCY = 'COP';

    private const AI_MODEL = 'claude-sonnet-5-5';

    private const BODY_LOCATIONS = [
        'Cabeza y cuello' => ['Cara', 'Cuero cabelludo', 'Orejas', 'Labios y boca', 'Cuello'],
        'Tronco' => ['Pecho', 'Abdomen', 'Espalda alta', 'Espalda baja', 'Axilas', 'Zona íntima', 'Glúteos'],
        'Brazos' => ['Hombro', 'Brazo', 'Codo', 'Antebrazo', 'Muñeca', 'Mano', 'Dedos de la mano'],
        'Piernas' => ['Cadera o ingle', 'Muslo', 'Rodilla', 'Pierna', 'Tobillo', 'Pie', 'Dedos del pie'],
        'Otro' => ['Varias zonas', 'Otra zona'],
    ];

    private const IMAGE_VALIDATION_SYSTEM_PROMPT = <<<'PROMPT'
Eres un verificador de calidad de fotografías para una plataforma de dermatología. Tu única tarea es decidir si la imagen es técnicamente adecuada para que un profesional evalúe la piel. NO diagnostiques ni describas condiciones médicas.

Evalúa solo: que la imagen muestre piel humana, que esté enfocada, que tenga iluminación suficiente, que no esté demasiado lejos o cortada, y que no tenga filtros o sobreexposición fuertes.

No rechaces una imagen por la zona del cuerpo que muestre. Rechaza únicamente por calidad técnica o porque no muestra piel.

Responde ÚNICAMENTE con un JSON válido, sin markdown ni texto adicional:
{"is_viable": true|false, "reason": "Si no es viable, explica en una frase amable en español qué mejorar. Si es viable, cadena vacía."}
PROMPT;

    public function __construct(protected WompiService $wompiService)
    {
    }

    // =====================================================================
    // 1. Carga y validación de la foto
    // =====================================================================

    /**
     * GET /skin-analysis
     */
    public function index(): View
    {
        return view('skin-analysis.index', $this->meta());
    }

    /**
     * GET /skin-analysis/upload
     * Si hay una foto validada pendiente en la sesión, la vista retoma el paso 2.
     */
    public function upload(): View
    {
        $token = session('skin_last_token');
        $resumeToken = null;

        if ($token
            && session()->has('skin_validated_' . $token)
            && !empty(Storage::disk(self::DISK)->files(self::TEMP_DIR . '/' . $token))) {
            $resumeToken = $token;
        }

        return view('skin-analysis.upload', [
            'resumeToken' => $resumeToken,
            'bodyLocations' => self::BODY_LOCATIONS,
            ...$this->meta(),
        ]);
    }

    /**
     * POST /skin-analysis/validate-image
     * Valida la calidad de la foto con IA y, si es viable, la guarda en el disco privado.
     */
    public function validateImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'file.required' => 'Selecciona una imagen.',
            'file.mimes' => 'El formato debe ser JPG, PNG o WebP.',
            'file.max' => 'La imagen supera los 5 MB.',
            'file.uploaded' => 'No se pudo subir la imagen. Intenta con una más liviana.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $file = $request->file('file');
            $verdict = $this->checkImageQuality(file_get_contents($file->getRealPath()), $file->getMimeType());

            if ($verdict === null) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se pudo validar la imagen. Intenta con otra toma.',
                ], 422);
            }

            if (!$verdict['is_viable']) {
                return response()->json([
                    'success' => true,
                    'is_viable' => false,
                    'reason' => $verdict['reason'] ?: 'La imagen no cumple con la calidad requerida.',
                ]);
            }

            $tempToken = Str::uuid()->toString();
            $extension = $file->extension() ?: 'jpg'; // deducida del contenido, no del nombre del cliente

            Storage::disk(self::DISK)->putFileAs(
                self::TEMP_DIR . '/' . $tempToken,
                $file,
                'file_0.' . $extension
            );

            // Si el usuario ya había validado otra foto, se descarta la anterior.
            $previous = session('skin_last_token');
            if ($previous && $previous !== $tempToken) {
                Storage::disk(self::DISK)->deleteDirectory(self::TEMP_DIR . '/' . $previous);
                session()->forget('skin_validated_' . $previous);
            }

            // Amarra el token a esta sesión para que nadie pueda usar un UUID ajeno.
            session([
                'skin_validated_' . $tempToken => true,
                'skin_last_token' => $tempToken,
            ]);

            return response()->json([
                'success' => true,
                'is_viable' => true,
                'temp_token' => $tempToken,
            ]);
        } catch (\Throwable $e) {
            Log::error('Skin validate-image failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al validar la imagen. Intenta nuevamente.',
            ], 500);
        }
    }

    /**
     * POST /skin-analysis/reset-image
     * Descarta la foto validada pendiente (y la borra del disco privado).
     */
    public function resetImage(): JsonResponse
    {
        $token = session('skin_last_token');

        if ($token) {
            Storage::disk(self::DISK)->deleteDirectory(self::TEMP_DIR . '/' . $token);
            session()->forget(['skin_last_token', 'skin_validated_' . $token]);
        }

        return response()->json(['success' => true]);
    }

    // =====================================================================
    // 2. Datos del paciente y resumen
    // =====================================================================

    /**
     * POST /skin-analysis/before-preview
     * Une los datos del formulario con la imagen ya validada.
     */
    public function beforePreview(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'validated_token' => ['required', 'uuid'],
            'email' => ['required', 'email', 'max:255'],
            'language' => ['required', 'in:es,en,fr,pt,de'],
            'body_location' => ['nullable', 'string', 'max:255', Rule::in(Arr::flatten(self::BODY_LOCATIONS))],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $token = $validated['validated_token'];
        $files = Storage::disk(self::DISK)->files(self::TEMP_DIR . '/' . $token);

        if (!session()->has('skin_validated_' . $token) || empty($files)) {
            return redirect()->route('skin-analysis.upload')
                ->with('error', 'No se encontró la imagen validada o la sesión expiró. Por favor, vuelve a intentarlo.');
        }

        session(['skin_analysis_' . $token => [
            'email' => $validated['email'],
            'language' => $validated['language'],
            'body_location' => $validated['body_location'] ?? null,
            'description' => $validated['description'] ?? null,
            'file_count' => count($files),
        ]]);

        return redirect()->route('skin-analysis.preview', ['session_id' => $token]);
    }

    /**
     * GET /skin-analysis/preview/{session_id}
     */
    public function preview(string $sessionId): View|RedirectResponse
    {
        $sessionData = session('skin_analysis_' . $sessionId);

        if (!$sessionData) {
            return redirect()->route('skin-analysis.upload')
                ->with('error', 'Sesión expirada. Intenta de nuevo.');
        }

        $breakdown = $this->priceBreakdown(0);

        return view('skin-analysis.preview', [
            'sessionId' => $sessionId,
            'sessionData' => $sessionData,
            'price' => self::BASE_PRICE,
            'wompiCommission' => $breakdown['fee'],
            'total' => $breakdown['total'],
            ...$this->meta(' - Preview'),
        ]);
    }

    /**
     * POST /skin-analysis/promo-codes/validate
     * Valida un código y devuelve el desglose de la orden con el descuento (solo para mostrarlo:
     * el precio real se recalcula en processDocuments()).
     */
    public function validatePromo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        $code = PromoCode::where('code', strtoupper(trim($validated['code'])))->first();

        if (!$code) {
            return response()->json(['valid' => false, 'message' => 'Código no encontrado.']);
        }

        if (!$code->isRedeemable()) {
            return response()->json(['valid' => false, 'message' => 'El código no está disponible (inactivo, vencido o agotado).']);
        }

        $discount = $code->discountFor(self::BASE_PRICE);
        $price = $this->priceBreakdown($discount);

        return response()->json([
            'valid' => true,
            'message' => 'Código aplicado correctamente.',
            'discount_type' => $code->type,
            'discount_value' => (float) $code->reward,
            'discount_amount' => $discount,
            'fee' => $price['fee'],
            'total' => $price['total'],
            'is_free' => $price['total'] === 0,
        ]);
    }

    // =====================================================================
    // 3. Creación de la orden y pago
    // =====================================================================

    /**
     * POST /skin-analysis/process-documents
     * Recalcula el precio en el servidor, crea la orden y mueve la foto al almacenamiento definitivo.
     * - Total > 0: redirige a la pasarela de pago.
     * - Total = 0 (código del 100%): consume el cupo y procesa directamente.
     */
    public function processDocuments(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'uuid'],
            'promo_code' => ['nullable', 'string', 'max:50'],
        ]);

        $sessionId = $validated['session_id'];
        $sessionData = session('skin_analysis_' . $sessionId);

        if (!$sessionData) {
            return redirect()->route('skin-analysis.upload')->with('error', 'Sesión expirada.');
        }

        $backToPreview = fn (string $message) => redirect()
            ->route('skin-analysis.preview', ['session_id' => $sessionId])
            ->with('error', $message);

        // Código promocional: se valida y se recalcula SIEMPRE en el servidor.
        $promo = null;
        $discount = 0;
        $promoInput = strtoupper(trim((string) ($validated['promo_code'] ?? '')));

        if ($promoInput !== '') {
            $candidate = PromoCode::where('code', $promoInput)->first();

            if (!$candidate || !$candidate->isRedeemable()) {
                return $backToPreview('El código promocional no es válido o ya no está disponible.');
            }

            $promo = $candidate;
            $discount = $candidate->discountFor(self::BASE_PRICE);
        }

        $price = $this->priceBreakdown($discount);
        $isFree = $price['total'] === 0;

        // Orden gratis: el cupo se consume de forma atómica ANTES de crear nada.
        if ($isFree && $promo && !$this->consumePromo($promo)) {
            return $backToPreview('Este código ya no está disponible.');
        }

        // access_token y el estado inicial (pending_payment) los fija el modelo/la tabla.
        $analysis = SkinAnalysis::create([
            'customer_email' => $sessionData['email'],
            'analysis_language' => $sessionData['language'],
            'body_location' => $sessionData['body_location'],
            'description' => $sessionData['description'],
            'price' => self::BASE_PRICE,
            'discount_amount' => $discount,
            'wompi_fee' => $price['fee'],
            'total_amount' => $price['total'],
            'promo_code' => $promo?->code,
            'ai_model' => self::AI_MODEL,
        ]);

        // Mover la foto: temporal privado -> definitivo privado
        try {
            $disk = Storage::disk(self::DISK);
            $tempDir = self::TEMP_DIR . '/' . $sessionId;

            foreach ($disk->files($tempDir) as $path) {
                $disk->move($path, $analysis->imageDirectory() . '/' . basename($path));
            }

            $disk->deleteDirectory($tempDir);
        } catch (\Throwable $e) {
            Log::error('Skin: no se pudo mover la foto de la orden', [
                'analysis_id' => $analysis->id,
                'message' => $e->getMessage(),
            ]);

            // Se deshace todo: la orden no existe sin su foto y el cupo se devuelve.
            $analysis->delete();
            if ($isFree && $promo) {
                PromoCode::whereKey($promo->id)->decrement('uses');
            }

            return $backToPreview('No pudimos guardar tu fotografía. Intenta nuevamente.');
        }

        session()->forget([
            'skin_analysis_' . $sessionId,
            'skin_validated_' . $sessionId,
            'skin_last_token',
        ]);

        if ($isFree) {
            $this->approveAnalysis($analysis, null, false); // el cupo ya se consumió arriba

            return redirect()->route('skin-analysis.result', ['token' => $analysis->access_token]);
        }

        return redirect()->route('skin-analysis.payment', ['token' => $analysis->access_token]);
    }

    /**
     * GET /skin-analysis/payment/{token}
     */
    public function paymentGateway(string $token): View
    {
        $analysis = $this->findPayableAnalysis($token);

        $reference = $this->paymentReference($analysis);
        $amountInCents = $this->payableAmount($analysis) * 100;

        // Orden exigido por Wompi: referencia + monto en centavos + moneda + secreto de integridad
        $signature = hash(
            'sha256',
            $reference . $amountInCents . self::CURRENCY . config('services.wompi.integrity_secret')
        );

        return view('skin-analysis.payment', [
            'analysis' => $analysis,
            'wompi_public_key' => config('services.wompi.public_key'),
            'signature' => $signature,
            'reference' => $reference,
            'amount_in_cents' => $amountInCents,
            'currency' => self::CURRENCY,
            ...$this->meta(' - Payment'),
        ]);
    }

    /**
     * GET /skin-analysis/payment/result/{token}
     * Wompi devuelve aquí al paciente con ?id=<transacción>.
     */
    public function processPaymentResult(string $token): RedirectResponse
    {
        $analysis = $this->findPayableAnalysis($token);

        $transactionId = request('id') ?? request('transaction_id');
        if (!$transactionId) {
            return redirect()->route('skin-analysis.payment', ['token' => $token])
                ->with('error', 'No se recibió un ID de transacción válido de Wompi.');
        }

        try {
            $response = $this->wompiService->validateTransaction($transactionId);
        } catch (\Throwable $e) {
            Log::error('Skin payment: error validando transacción', [
                'analysis_id' => $analysis->id,
                'transaction_id' => $transactionId,
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('skin-analysis.payment', ['token' => $token])
                ->with('error', 'No pudimos verificar el pago. Intenta nuevamente en unos minutos.');
        }

        $status = $response['status'] ?? null;

        if ($status === 'APPROVED') {
            if ($this->transactionMatchesAnalysis($response, $analysis)) {
                $this->approveAnalysis($analysis, $transactionId);

                return redirect()->route('skin-analysis.result', ['token' => $token]);
            }

            // Aprobada pero de otra orden u otro monto: no se acredita.
            Log::warning('Skin payment: transacción aprobada que no coincide con la orden', [
                'analysis_id' => $analysis->id,
                'transaction_id' => $transactionId,
            ]);
        }

        if ($status === 'PENDING') {
            return redirect()->route('skin-analysis.payment', ['token' => $token])
                ->with('error', 'Tu pago aún está pendiente de confirmación. Si ya pagaste, espera unos minutos y recarga.');
        }

        $analysis->update(['status' => SkinAnalysis::STATUS_PAYMENT_FAILED]);

        return redirect()->route('skin-analysis.payment', ['token' => $token])
            ->with('error', 'El pago fue rechazado. Intenta nuevamente.');
    }

    // =====================================================================
    // 4. Resultado
    // =====================================================================

    /**
     * GET /skin-analysis/result/{token}
     */
    public function showResult(string $token): View
    {
        $analysis = SkinAnalysis::where('access_token', $token)
            ->whereIn('status', [
                SkinAnalysis::STATUS_PROCESSING,
                SkinAnalysis::STATUS_COMPLETED,
                SkinAnalysis::STATUS_ERROR,
            ])
            ->firstOrFail();

        return view('skin-analysis.result', [
            'analysis' => $analysis,
            'is_processing' => $analysis->status === SkinAnalysis::STATUS_PROCESSING,
            ...$this->meta(' - Result'),
        ]);
    }

    /**
     * GET /skin-analysis/status/{token}
     * Lo consulta la pantalla de resultado (sondeo) mientras la IA procesa.
     */
    public function status(string $token): JsonResponse
    {
        $status = SkinAnalysis::where('access_token', $token)->value('status');

        abort_if($status === null, 404);

        return response()->json(['status' => $status]);
    }

    // =====================================================================
    // Helpers privados
    // =====================================================================

    /**
     * Variables de SEO que exige el layout.
     */
    private function meta(string $suffix = ''): array
    {
        return [
            'meta_title_medicalAnalysis' => 'Análisis Avanzado de Piel con IA' . $suffix,
            'meta_description_medicalAnalysis' => 'Obtén un informe preliminar de tus lesiones cutáneas en minutos.',
        ];
    }

    /**
     * Desglose de la orden en pesos a partir del descuento.
     * La comisión de la pasarela se calcula sobre el precio ya con descuento.
     *
     * @return array{discount:int, final:int, fee:int, total:int}
     */
    private function priceBreakdown(int $discount): array
    {
        $final = max(0, self::BASE_PRICE - $discount);
        $fee = $this->commissionFor($final);

        return [
            'discount' => $discount,
            'final' => $final,
            'fee' => $fee,
            'total' => $final + $fee,
        ];
    }

    private function commissionFor(int|float $amount): int
    {
        return (int) round($amount * self::WOMPI_COMMISSION_RATE);
    }

    /**
     * Consulta a la IA si la foto es técnicamente adecuada.
     * Devuelve ['is_viable' => bool, 'reason' => string] o null si la respuesta no es interpretable.
     */
    private function checkImageQuality(string $contents, string $mimeType): ?array
    {
        $client = new Client(apiKey: config('services.anthropic.key'));

        $message = $client->messages->create(
            maxTokens: 300,
            model: self::AI_MODEL,
            system: self::IMAGE_VALIDATION_SYSTEM_PROMPT,
            messages: [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => $mimeType,
                                'data' => base64_encode($contents),
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => 'Verifica la calidad de esta imagen y responde solo con el JSON indicado.',
                        ],
                    ],
                ],
            ],
        );

        $raw = $message->content[0]->text ?? '';
        $clean = trim(preg_replace('/^```(?:json)?|```$/m', '', $raw));
        $result = json_decode($clean, true);

        if (!is_array($result) || !array_key_exists('is_viable', $result)) {
            Log::warning('Skin validate-image: respuesta no interpretable', ['raw' => $raw]);

            return null;
        }

        return [
            'is_viable' => (bool) $result['is_viable'],
            'reason' => (string) ($result['reason'] ?? ''),
        ];
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
     * Pasa la orden a "processing", consume el código (si aplica) y dispara el análisis.
     * Es idempotente: si dos requests llegan a la vez, solo el primero avanza.
     */
    private function approveAnalysis(SkinAnalysis $analysis, ?string $transactionId, bool $consumePromo = true): void
    {
        $updated = SkinAnalysis::whereKey($analysis->id)
            ->whereIn('status', SkinAnalysis::PAYABLE_STATUSES)
            ->update([
                'status' => SkinAnalysis::STATUS_PROCESSING,
                'wompi_transaction_id' => $transactionId,
                'paid_at' => now(),
            ]);

        if (!$updated) {
            return;
        }

        if ($consumePromo && $analysis->promo_code) {
            $promo = PromoCode::where('code', strtoupper($analysis->promo_code))->first();

            // El pago ya se aprobó: si el cupo se agotó justo ahora, solo se deja constancia.
            if ($promo && !$this->consumePromo($promo)) {
                Log::warning('Skin: cupo del código agotado al aprobar el pago', [
                    'analysis_id' => $analysis->id,
                    'promo_code' => $analysis->promo_code,
                ]);
            }
        }

        $analysis = $analysis->fresh();

        SkinAnalysisJob::dispatch($analysis);

        try {
            Mail::to($analysis->customer_email)->queue(new SkinAnalysisReceived($analysis));
        } catch (\Throwable $e) {
            // Un fallo del correo no debe romper el flujo: el análisis ya está en marcha.
            Log::warning('Skin: no se pudo encolar el correo de recepción', [
                'analysis_id' => $analysis->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Evita que se use una transacción aprobada de otra orden o de otro monto.
     */
    private function transactionMatchesAnalysis(array $response, SkinAnalysis $analysis): bool
    {
        if (isset($response['reference']) && $response['reference'] !== $this->paymentReference($analysis)) {
            return false;
        }

        if (isset($response['amount_in_cents'])
            && (int) $response['amount_in_cents'] !== $this->payableAmount($analysis) * 100) {
            return false;
        }

        return true;
    }

    private function findPayableAnalysis(string $token): SkinAnalysis
    {
        return SkinAnalysis::where('access_token', $token)
            ->whereIn('status', SkinAnalysis::PAYABLE_STATUSES)
            ->firstOrFail();
    }

    private function paymentReference(SkinAnalysis $analysis): string
    {
        return 'SKIN-' . $analysis->id;
    }

    /**
     * Monto que realmente se cobra en Wompi (precio con descuento + comisión), en pesos.
     */
    private function payableAmount(SkinAnalysis $analysis): int
    {
        return (int) $analysis->total_amount;
    }
}