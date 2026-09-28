<?php

namespace App\Http\Controllers;

use App\Models\MedicalAnalysis;
use App\Services\SkinAnalysisService;
use App\Services\Wompi\WompiService;
use App\Jobs\SkinAnalysisJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SkinAnalysisController extends Controller
{
    protected $skinAnalysisService;
    protected $wompiService;

    public function index()
    {
        $meta_title_medicalAnalysis= '';
        $meta_description_medicalAnalysis = '';

        return view('skin-analysis.index',
            [
                'meta_title_medicalAnalysis' => $meta_title_medicalAnalysis,
                'meta_description_medicalAnalysis' => $meta_description_medicalAnalysis
            ]
        );
    }

    public function __construct(SkinAnalysisService $skinAnalysisService, WompiService $wompiService)
    {
        $this->skinAnalysisService = $skinAnalysisService;
        $this->wompiService = $wompiService;
    }

    // GET /skin-analysis/upload
    public function upload()
    {
        return view('skin-analysis.upload');
    }

    // POST /skin-analysis/before-preview
    public function beforePreview(Request $request)
    {
        $validated = $request->validate([
            'files' => 'required|array|min:1|max:3',
            'files.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'email' => 'required|email|max:255',
            'language' => 'required|in:es,en',
            'body_location' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        // Guardar en sesión
        $sessionId = Str::uuid();
        session(['skin_analysis_' . $sessionId => [
            'email' => $validated['email'],
            'language' => $validated['language'],
            'body_location' => $validated['body_location'] ?? null,
            'description' => $validated['description'] ?? null,
            'file_count' => count($validated['files']),
        ]]);

        // Guardar archivos temporalmente
        $tempDir = 'temp/skin-analysis/' . $sessionId;
        foreach ($validated['files'] as $index => $file) {
            $filename = 'file_' . $index . '.' . $file->getClientOriginalExtension();
            $file->storeAs($tempDir, $filename, 'public');
        }

        return redirect()->route('skin-analysis.preview', ['session_id' => $sessionId]);
    }

    // GET /skin-analysis/preview/{session_id}
    public function preview($sessionId)
    {
        $sessionData = session('skin_analysis_' . $sessionId);
        if (!$sessionData) {
            return redirect()->route('skin-analysis.upload')->with('error', 'Sesión expirada. Intenta de nuevo.');
        }

        $price = 9000; // $9.000 COP fijo
        $wompiCommission = round($price * 0.03, 0); // 3% Wompi
        $total = $price + $wompiCommission;

        return view('skin-analysis.preview', [
            'session_id' => $sessionId,
            'email' => $sessionData['email'],
            'language' => $sessionData['language'],
            'body_location' => $sessionData['body_location'],
            'description' => $sessionData['description'],
            'file_count' => $sessionData['file_count'],
            'price' => $price,
            'wompi_commission' => $wompiCommission,
            'total' => $total,
        ]);
    }

    // POST /skin-analysis/process-documents
    public function processDocuments(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'promo_code' => 'nullable|string|max:50',
        ]);

        $sessionData = session('skin_analysis_' . $validated['session_id']);
        if (!$sessionData) {
            return redirect()->route('skin-analysis.upload')->with('error', 'Sesión expirada.');
        }

        $basePrice = 9000;
        $discount = 0;
        $wompiCommission = round($basePrice * 0.03, 0);

        // Validar promo code (reutilizar lógica de medical-analysis)
        if ($validated['promo_code']) {
            $promoData = $this->validatePromoCode($validated['promo_code'], $basePrice);
            if ($promoData['valid']) {
                $discount = $promoData['discount'];
            }
        }

        $discountedPrice = max(0, $basePrice - $discount);
        $wompiCommission = round($discountedPrice * 0.03, 0);
        $total = $discountedPrice + $wompiCommission;

        // Crear registro en BD
        $analysis = MedicalAnalysis::create([
            'token' => Str::uuid(),
            'email' => $sessionData['email'],
            'type' => 'skin',
            'exam_type' => 'skin_lesion',
            'language' => $sessionData['language'],
            'body_location' => $sessionData['body_location'],
            'description' => $sessionData['description'],
            'status' => 'pending_payment',
            'total_images_uploaded' => $sessionData['file_count'],
            'price' => $basePrice,
            'discount_applied' => $discount,
            'final_price' => $discountedPrice,
            'wompi_commission' => $wompiCommission,
            'promo_code' => $validated['promo_code'] ?? null,
        ]);

        // Mover archivos del temp a análisis
        $tempDir = 'temp/skin-analysis/' . $validated['session_id'];
        $analysisDir = 'medical-analyses/skin/' . $analysis->id;

        $files = Storage::disk('public')->files($tempDir);
        foreach ($files as $file) {
            $filename = basename($file);
            Storage::disk('public')->copy($file, $analysisDir . '/' . $filename);
        }

        // Limpiar temp
        Storage::disk('public')->deleteDirectory($tempDir);
        session()->forget('skin_analysis_' . $validated['session_id']);

        return redirect()->route('skin-analysis.payment', ['token' => $analysis->token]);
    }

    // GET /skin-analysis/payment/{token}
    public function paymentGateway($token)
    {
        $analysis = MedicalAnalysis::where('token', $token)
            ->where('status', 'pending_payment')
            ->firstOrFail();

        // Generar firma Wompi
        $integrityData = [
            'amount_in_cents' => $analysis->final_price * 100,
            'currency' => 'COP',
            'reference' => 'SKIN-' . $analysis->id,
        ];

        sort($integrityData);
        $integrityString = implode('', array_values($integrityData)) . env('WOMPI_PRIVATE_KEY');
        $signature = hash('sha256', $integrityString);

        return view('skin-analysis.payment', [
            'analysis' => $analysis,
            'wompi_public_key' => env('WOMPI_PUBLIC_KEY'),
            'signature' => $signature,
            'reference' => 'SKIN-' . $analysis->id,
        ]);
    }

    // GET /skin-analysis/payment/result/{token}
    public function processPaymentResult($token)
    {
        $analysis = MedicalAnalysis::where('token', $token)
            ->where('status', 'pending_payment')
            ->firstOrFail();

        $transactionId = request('transaction_id');
        if (!$transactionId) {
            return redirect()->route('skin-analysis.payment', ['token' => $token])
                ->with('error', 'No transaction ID received.');
        }

        // Validar transacción con Wompi
        $response = $this->wompiService->validateTransaction($transactionId);

        if ($response['status'] === 'APPROVED') {
            $analysis->update([
                'status' => 'processing',
                'wompi_transaction_id' => $transactionId,
                'payment_approved_at' => now(),
            ]);

            // Disparar job asincrónico
            SkinAnalysisJob::dispatch($analysis);

            // Enviar email de confirmación
            \Mail::to($analysis->email)->queue(new \App\Mail\SkinAnalysisReceived($analysis));

            return redirect()->route('skin-analysis.result', ['token' => $token]);
        } else {
            $analysis->update(['status' => 'payment_failed']);
            return redirect()->route('skin-analysis.payment', ['token' => $token])
                ->with('error', 'Pago rechazado. Intenta nuevamente.');
        }
    }

    // GET /skin-analysis/result/{token}
    public function showResult($token)
    {
        $analysis = MedicalAnalysis::where('token', $token)
            ->whereIn('status', ['processing', 'completed', 'error'])
            ->firstOrFail();

        // Verificar que el pago fue aprobado
        if ($analysis->status === 'pending_payment') {
            return redirect()->route('skin-analysis.upload')
                ->with('error', 'Análisis no pagado.');
        }

        return view('skin-analysis.result', [
            'analysis' => $analysis,
            'is_processing' => $analysis->status === 'processing',
        ]);
    }

    // Validar promo code (reutilizar de MedicalAnalysisController)
    private function validatePromoCode($code, $basePrice)
    {
        $promoCode = \App\Models\PromoCode::where('code', strtoupper($code))->first();

        if (!$promoCode) {
            return ['valid' => false];
        }

        if (!$promoCode->is_active) {
            return ['valid' => false];
        }

        if ($promoCode->usage_limit && $promoCode->times_used >= $promoCode->usage_limit) {
            return ['valid' => false];
        }

        if ($promoCode->valid_from && now() < $promoCode->valid_from) {
            return ['valid' => false];
        }

        if ($promoCode->valid_until && now() > $promoCode->valid_until) {
            return ['valid' => false];
        }

        if ($promoCode->discount_type === 'percentage') {
            $discount = round(($basePrice * $promoCode->discount_value) / 100, 0);
        } else {
            $discount = $promoCode->discount_value;
        }

        $discount = min($discount, $basePrice);

        $promoCode->increment('times_used');

        return [
            'valid' => true,
            'discount' => $discount,
        ];
    }
}