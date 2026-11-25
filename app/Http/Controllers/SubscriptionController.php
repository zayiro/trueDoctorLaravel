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

        // Reglas de plan (planreglas): subir siempre; bajar solo cuando termina el tiempo pagado; sin pagar dos veces el mismo plan vigente.
        $rules = app(\App\Services\Plans\PlanRules::class);
        $decision = $rules->evaluate(Auth::user(), $plan);
        if (!$decision['ok']) {
            return redirect($rules->homeFor(Auth::user()))->with('error', $decision['message']);
        }

        // Plan gratuito permitido: pantalla de confirmación (nunca se cobra ni se cambia con un solo clic).
        if ((float) $plan->price <= 0) {
            $doctor = $rules->doctorOf(Auth::user());

            return view('plans.downgrade', [
                'plan'    => $plan,
                'preview' => $rules->downgradePreview($doctor, $plan),
            ]);
        }

        return view('plans.subscribe', compact('plan'));
    }

    /**
     * Redirige al checkout de Wompi.
     */
    public function checkout(Plan $plan)
    {
        if (Auth::guest()) {
            session(['selected_plan_id' => $plan->id]);

            return redirect()->route('register.options')->with('info', 'Primero crea tu cuenta para activar el ' . $plan->name);
        }

        // Mismas reglas que al mostrar el plan: el servidor decide, no la pantalla.
        $rules = app(\App\Services\Plans\PlanRules::class);
        $decision = $rules->evaluate(Auth::user(), $plan);
        if (!$decision['ok']) {
            return redirect($rules->homeFor(Auth::user()))->with('error', $decision['message']);
        }
        if ((float) $plan->price <= 0) {
            return redirect($rules->homeFor(Auth::user()))->with('error', 'El plan gratuito no requiere pago.');
        }

        // planpagos: el cobro queda en `payments` (tipo suscripción) y la pasarela devuelve al médico a /payments/return/{referencia}.
        try {
            $checkout = app(\App\Services\Payments\PlanCheckout::class);
            $payment  = $checkout->start(Auth::user(), $plan);

            return redirect()->away($checkout->checkoutUrl($payment));
        } catch (\Throwable $e) {
            \Log::error('plans.checkout_failed', ['user_id' => Auth::id(), 'plan_id' => $plan->id, 'error' => $e->getMessage()]);

            return redirect($rules->homeFor(Auth::user()))->with('error', 'No pudimos iniciar el pago. Inténtalo de nuevo en unos segundos.');
        }
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
        $status = strtolower((string) ($transaction['status'] ?? '')); // approved, declined, voided, error

        // Idempotencia: un pago ya aprobado no se vuelve a aplicar (la página de resultado y el webhook
        // llegan los dos, y Wompi puede reenviar). Antes cada llegada reiniciaba el periodo de 30 días.
        if ($subscription->status === 'approved') {
            return;
        }

        if ($status !== 'approved') {
            $subscription->update([
                'wompi_transaction_id' => $transaction['id'] ?? $subscription->wompi_transaction_id,
                'status'               => $status,
            ]);

            return;
        }

        // El monto confirmado por Wompi debe ser el de la suscripción. Si no coincide NO se activa nada:
        // la suscripción queda como estaba (pending), se guarda el id de la transacción y se deja el aviso en el log.
        if ((int) ($transaction['amount_in_cents'] ?? 0) !== (int) $subscription->amount_in_cents) {
            \Log::warning('Suscripción: monto de Wompi distinto al esperado; no se activó el plan', [
                'subscription_id' => $subscription->id,
                'esperado'        => $subscription->amount_in_cents,
                'recibido'        => $transaction['amount_in_cents'] ?? null,
                'transaction_id'  => $transaction['id'] ?? null,
            ]);
            $subscription->update([
                'wompi_transaction_id' => $transaction['id'] ?? $subscription->wompi_transaction_id,
            ]);

            return;
        }

        $rules = app(\App\Services\Plans\PlanRules::class);

        \Illuminate\Support\Facades\DB::transaction(function () use ($subscription, $transaction, $rules) {
            $locked = Subscription::lockForUpdate()->find($subscription->id);
            if (!$locked || $locked->status === 'approved') {
                return;
            }

            // Si renueva el mismo plan vigente, los 30 días nuevos se suman al vencimiento actual.
            [$starts, $ends] = $rules->periodFor($locked);

            $locked->update([
                'wompi_transaction_id' => $transaction['id'] ?? $locked->wompi_transaction_id,
                'status'               => 'approved',
                'paid_at'              => now(),
                'starts_at'            => $starts,
                'ends_at'              => $ends,
            ]);

            $rules->activatePlan($locked);
        });
    }
}