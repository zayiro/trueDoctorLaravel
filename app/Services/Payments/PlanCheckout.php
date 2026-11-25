<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Enums\PaymentKind;
use App\Enums\PaymentTransactionStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Payments\PaymentService;
use App\Services\Plans\PlanRules;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Cobro de los planes de suscripción sobre la capa de pagos (tabla `payments`, tipo `subscription`).
 *
 * Todo el dinero queda en `payments` (monto, estado, referencia, conciliación). La fila de `subscriptions`
 * es el periodo comprado: se crea al iniciar el pago (es el `payable` de ese pago), nace `pending` y solo pasa a
 * `approved` cuando el pago se aprueba (listener ConfirmPlanOnPaymentApproved). Cada intento de cobro tiene su
 * propia fila, así una renovación nunca se confunde con "pago duplicado".
 */
final class PlanCheckout
{
    public function __construct(private readonly PaymentService $payments)
    {
    }

    /**
     * Crea un intento de cobro nuevo.
     *
     * @throws DomainException si el plan no se puede cobrar.
     */
    public function start(User $user, Plan $plan): Payment
    {
        if ((float) $plan->price <= 0) {
            throw new DomainException('El plan gratuito no requiere pago.');
        }

        return DB::transaction(function () use ($user, $plan) {
            // Intentos anteriores sin resultado: ya no se van a usar (si alguno se pagó igual, el webhook lo acredita).
            Payment::where('kind', PaymentKind::Subscription->value)
                ->where('payer_id', $user->id)
                ->where('status', PaymentTransactionStatus::Pending->value)
                ->get()
                ->each(fn (Payment $old) => $this->payments->markExpired($old));

            $cents = (int) round(((float) $plan->price) * 100);

            $subscription = Subscription::create([
                'doctor_id'       => $user->id,
                'plan_id'         => $plan->id,
                'wompi_reference' => 'TMP-' . strtoupper((string) Str::ulid()), // se reemplaza por la referencia del pago
                'status'          => 'pending',
                'amount_in_cents' => $cents,
                'currency'        => 'COP',
                'starts_at'       => now(),
                'ends_at'         => now()->addDays(PlanRules::PERIOD_DAYS),
            ]);

            $payment = $this->payments->start(
                payable: $subscription,
                payer: $user,
                kind: PaymentKind::Subscription,
                baseInCents: $cents,
                expiresAt: now()->addDay(),
                payload: ['plan_id' => (int) $plan->id, 'plan_name' => (string) $plan->name],
            );

            $subscription->update(['wompi_reference' => $payment->reference]);

            return $payment;
        });
    }

    /** URL de la pasarela para un pago creado con start(). */
    public function checkoutUrl(Payment $payment): string
    {
        return $this->payments->checkoutUrl($payment, route('payments.return', $payment->reference));
    }
}
