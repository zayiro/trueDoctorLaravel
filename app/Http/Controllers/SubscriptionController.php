<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Wompi\WompiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class SubscriptionController extends Controller
{
    public function __construct(protected WompiService $wompi) {}
  
    /**
     * Muestra la página del plan con botón de pagar.
     */
    public function show(Plan $plan)
    {
        if (Auth::guest()) {
            // Guardamos el ID del plan para recuperarlo después del registro
            session(['selected_plan_id' => $plan->id]);
            
            return redirect()->route('register.options')
                ->with('info', 'Primero crea tu cuenta para activar el ' . $plan->name);
        }

        return view('plans.subscribe', compact('plan'));
    }

    /**
     * Redirige al checkout de Wompi.
     */
    public function checkout(Plan $plan)
    {
        $result = $this->wompi->buildCheckoutUrl(Auth::id(), $plan);        

        return redirect($result['checkout_url']);
    }

    /**
     * Wompi redirige aquí al terminar el pago.
     * GET /plans/payment/result?id=wompi_transaction_id
     */
    public function result(Request $request)
    {
        $transactionId = $request->query('id');

        if (!$transactionId) {
            return redirect()->route('plans.index')->with('error', 'No se recibió información del pago.');
        }

        // Consultar la transacción en Wompi
        $response = Http::withToken(config('services.wompi.private_key'))
            ->get("https://production.wompi.co/v1/transactions/{$transactionId}");

        \Log::info('Wompi result response', [
            'status'  => $response->status(),
            'body'    => $response->json(),
        ]);

        if (!$response->successful()) {
            return redirect()->route('plans.index')->with('error', 'No se pudo verificar el pago.');
        }

        $transaction = $response->json('data');
        $reference   = $transaction['reference'];
        $status      = strtolower($transaction['status']); // APPROVED, DECLINED, etc.

        $subscription = Subscription::where('wompi_reference', $reference)
            ->where('doctor_id', Auth::id())
            ->firstOrFail();

        $this->applyTransactionResult($subscription, $transaction);

        return view('plans.payment.result', compact('status'));
    }

    /**
     * Webhook de Wompi — confirma pagos de forma asíncrona.
     * POST /webhooks/wompi
     */
    public function webhook(Request $request)
    {
        $payload   = $request->all();
        $signature = $request->header('X-Event-Checksum', '');

        if (!$this->wompi->validateWebhookSignature($payload, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $transaction = $payload['data']['transaction'] ?? null;

        if (!$transaction) {
            return response()->json(['ok' => true]);
        }

        $subscription = Subscription::where('wompi_reference', $transaction['reference'])->first();

        if ($subscription) {
            $this->applyTransactionResult($subscription, $transaction);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Aplica el resultado de la transacción al modelo.
     */
    private function applyTransactionResult(Subscription $subscription, array $transaction): void
    {
        $status = strtolower($transaction['status']); // approved, declined, voided, error

        $subscription->update([
            'wompi_transaction_id' => $transaction['id'],
            'status'               => $status,
            'paid_at'              => $status === 'approved' ? now() : null,
            'ends_at'              => $status === 'approved' ? now()->addDays(30) : $subscription->ends_at,
        ]);

        // Actualizar el plan del doctor si fue aprobado
        if ($status === 'approved') {
            $subscription->doctor->doctorSetting()->update([
                'plan_id' => $subscription->plan_id,
            ]);
        }
    }
}