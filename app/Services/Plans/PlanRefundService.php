<?php

declare(strict_types=1);

namespace App\Services\Plans;

use App\Enums\PaymentKind;
use App\Enums\PaymentTransactionStatus;
use App\Mail\PlanRefundedMail;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlanEvent;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Retira un plan cuando su pago se devuelve o se anula.
 *
 *  - La suscripción pasa a `voided`.
 *  - Si el usuario conserva otra suscripción vigente (p. ej. se reembolsó solo una renovación), se queda con ese plan
 *    hasta que termine; si no, baja al plan gratuito (médico: sedes propias sobrantes desactivadas; clínica: sedes
 *    físicas desactivadas y especialistas sobrantes pausados). Nada se borra.
 *  - Queda un evento `refunded` en plan_events y el usuario recibe un correo.
 * Es idempotente: una suscripción que ya no está `approved` no se vuelve a procesar.
 */
final class PlanRefundService
{
    public function __construct(private PlanRules $rules) {}

    /**
     * @param  array<string,mixed> $meta
     * @return array{changed:bool, user:?User, plan_name:?string, amount_in_cents:int, kept_until:?\Carbon\CarbonInterface, to_plan:?Plan, addresses_off:int, doctors_off:int}
     */
    public function withdraw(Subscription $sub, string $reason, ?int $paymentId = null, array $meta = []): array
    {
        $out = ['changed' => false, 'user' => null, 'plan_name' => null, 'amount_in_cents' => 0, 'kept_until' => null, 'to_plan' => null, 'addresses_off' => 0, 'doctors_off' => 0];

        DB::transaction(function () use ($sub, $reason, $paymentId, $meta, &$out) {
            $s = Subscription::whereKey($sub->id)->lockForUpdate()->first();
            if (!$s || $s->status !== 'approved') {
                return; // ya procesada o nunca estuvo aprobada
            }

            $s->update(['status' => 'voided']);

            $user = User::find($s->doctor_id);
            $doctor = $user ? $this->rules->doctorOf($user) : null;
            $clinicId = (!$doctor && $user) ? $this->rules->clinicIdOf((int) $user->id) : null;
            $current = $doctor ? $this->rules->currentPlan($doctor) : ($clinicId ? $this->rules->currentClinicPlan($clinicId) : null);
            $refundedPlan = Plan::find($s->plan_id);

            $other = $this->rules->activeSubscription((int) $s->doctor_id, (int) $s->id);
            $toPlan = $current;

            if ($other) {
                // Otro periodo pagado sigue vigente: conserva ese plan hasta que termine.
                $this->rules->activatePlan($other);
                $toPlan = Plan::find($other->plan_id);
                $out['kept_until'] = $other->ends_at;
            } elseif ($current && (float) $current->price > 0) {
                if ($doctor) {
                    $free = Plan::where('active', 1)->where('applicable_role', 'doctor')->where('price', '<=', 0)->orderBy('id')->first();
                    if ($free) {
                        $sum = $this->rules->downgradeToFree($doctor, $free, 'reembolso del pago', 'downgraded');
                        $out['addresses_off'] = (int) ($sum['addresses_off'] ?? 0);
                        $toPlan = $free;
                    }
                } elseif ($clinicId) {
                    $free = Plan::where('slug', 'clinic_free')->first();
                    if ($free) {
                        $sum = $this->rules->downgradeClinicToFree($clinicId, (int) $s->doctor_id, $free, 'reembolso del pago', 'downgraded');
                        $out['addresses_off'] = (int) ($sum['addresses_off'] ?? 0);
                        $out['doctors_off'] = (int) ($sum['doctors_off'] ?? 0);
                        $toPlan = $free;
                    }
                }
            }

            PlanEvent::record('refunded', (int) $s->doctor_id, [
                'from_plan_id'    => $current?->id,
                'to_plan_id'      => $toPlan?->id,
                'subscription_id' => (int) $s->id,
                'payment_id'      => $paymentId,
                'reason'          => $reason,
                'meta'            => array_merge($meta, ['kept_plan' => (bool) $other, 'amount_in_cents' => (int) $s->amount_in_cents]),
            ]);

            Log::info('plans.refund_withdrawn', ['subscription_id' => $s->id, 'user_id' => $s->doctor_id, 'kept_plan' => (bool) $other, 'reason' => $reason]);

            $out['changed'] = true;
            $out['user'] = $user;
            $out['plan_name'] = $refundedPlan?->name;
            $out['amount_in_cents'] = (int) $s->amount_in_cents;
            $out['to_plan'] = $toPlan;
        });

        return $out;
    }

    /**
     * Reembolso registrado por un administrador: el dinero ya se devolvió desde el panel de Wompi.
     * El pago pasa a `refunded` y se retira el plan. Lanza \DomainException con un mensaje claro si no se puede.
     */
    public function refundPayment(Payment $payment, User $admin, string $providerReference, ?string $note = null): array
    {
        return DB::transaction(function () use ($payment, $admin, $providerReference, $note) {
            $p = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($p->kind !== PaymentKind::Subscription) {
                throw new \DomainException('Este pago no es de un plan.');
            }
            if (!$p->status->canTransitionTo(PaymentTransactionStatus::Refunded)) {
                throw new \DomainException('Este pago ya no se puede reembolsar (estado: ' . $p->status->label() . ').');
            }

            $p->update(['status' => PaymentTransactionStatus::Refunded]);

            $sub = Subscription::find($p->payable_id);
            if (!$sub) {
                Log::warning('plans.refund_without_subscription', ['payment' => $p->reference]);

                return ['changed' => false, 'user' => null, 'plan_name' => null, 'amount_in_cents' => (int) $p->total_in_cents, 'kept_until' => null, 'to_plan' => null, 'addresses_off' => 0, 'doctors_off' => 0];
            }

            return $this->withdraw($sub, 'reembolso registrado por administración', (int) $p->id, [
                'admin_id'           => (int) $admin->id,
                'provider_reference' => $providerReference,
                'note'               => $note,
                'reference'          => $p->reference,
                'total_in_cents'     => (int) $p->total_in_cents,
            ]);
        });
    }

    /** Wompi anuló el pago por su cuenta: si el plan estaba aplicado se retira; si aún estaba pendiente, solo se marca. */
    public function handleVoidedSubscription(Subscription $sub, Payment $payment): void
    {
        if ($sub->status === 'approved') {
            $out = $this->withdraw($sub, 'pago anulado en la pasarela', (int) $payment->id, ['reference' => $payment->reference, 'automatic' => true]);
            $this->notify($out);

            return;
        }

        if ($sub->status === 'pending') {
            Subscription::whereKey($sub->id)->where('status', 'pending')->update(['status' => 'voided', 'updated_at' => now()]);
        }
    }

    /** Correo al usuario. Va aparte para enviarlo cuando la transacción ya terminó. Un fallo del correo no deshace nada. */
    public function notify(array $out): void
    {
        $user = $out['user'] ?? null;
        if (empty($out['changed']) || !$user || !$user->email) {
            return;
        }

        try {
            $kept = $out['kept_until'] ?? null;
            Mail::to($user->email)->send(new PlanRefundedMail(
                (string) $user->name,
                (string) ($out['plan_name'] ?? 'plan'),
                '$' . number_format(((int) $out['amount_in_cents']) / 100, 0, ',', '.') . ' COP',
                $kept ? $kept->copy()->timezone('America/Bogota')->format('d/m/Y') : null,
                $kept ? null : ($out['to_plan']?->name),
                (int) $out['addresses_off'],
                (int) $out['doctors_off'],
                $this->rules->homeFor($user),
            ));
        } catch (Throwable $e) {
            Log::warning('plans.refund_mail_failed', ['user_id' => $user->id, 'message' => $e->getMessage()]);
        }
    }
}
