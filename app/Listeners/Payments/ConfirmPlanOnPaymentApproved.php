<?php

declare(strict_types=1);

namespace App\Listeners\Payments;

use App\Enums\PaymentKind;
use App\Mail\PlanReceiptMail;
use App\Models\Plan;
use App\Models\PlanEvent;
use App\Models\Subscription;
use App\Models\User;
use App\Payments\Events\PaymentApproved;
use App\Services\Plans\PlanRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Un pago de plan quedó aprobado → la suscripción pasa a `approved`, se calcula el periodo (si renueva el mismo plan
 * vigente se suma al vencimiento), el médico queda con el plan, se registra el evento y se envía el comprobante.
 * Es idempotente: bajo bloqueo de fila, solo el primero avanza.
 */
class ConfirmPlanOnPaymentApproved
{
    public function handle(PaymentApproved $event): void
    {
        $payment = $event->payment;

        if ($payment->kind !== PaymentKind::Subscription) {
            return;
        }

        $rules = app(PlanRules::class);

        $applied = DB::transaction(function () use ($payment, $rules) {
            $sub = Subscription::whereKey($payment->payable_id)->lockForUpdate()->first();

            if (!$sub) {
                Log::critical('plans.payment_approved_without_subscription', ['payment' => $payment->reference, 'subscription_id' => $payment->payable_id]);

                return null;
            }

            if ($sub->status === 'approved') {
                return null; // ya aplicado
            }

            $user = User::find($sub->doctor_id);
            $doctor = $user ? $rules->doctorOf($user) : null;
            $beforePlanId = $doctor
                ? (int) DB::table('doctor_settings')->where('doctor_id', $doctor->id)->value('plan_id')
                : ($user ? (int) DB::table('clinic_settings')->join('clinics', 'clinics.id', '=', 'clinic_settings.clinic_id')->where('clinics.user_id', $user->id)->value('clinic_settings.plan_id') : 0);

            [$starts, $ends] = $rules->periodFor($sub);

            $sub->update([
                'wompi_transaction_id' => $payment->provider_transaction_id ?: $sub->wompi_transaction_id,
                'status'               => 'approved',
                'amount_in_cents'      => (int) $payment->total_in_cents,
                'paid_at'              => $payment->approved_at ?? now(),
                'starts_at'            => $starts,
                'ends_at'              => $ends,
            ]);

            $rules->activatePlan($sub);

            return ['sub' => $sub->fresh(), 'before' => $beforePlanId, 'user' => $user];
        });

        if ($applied === null) {
            return;
        }

        /** @var Subscription $sub */
        $sub = $applied['sub'];
        $user = $applied['user'];
        $plan = Plan::find($sub->plan_id);
        $beforePlan = $applied['before'] ? Plan::find($applied['before']) : null;

        // Qué fue esta compra: primera vez, renovación del mismo plan, subida o cambio.
        if (!$beforePlan || (float) $beforePlan->price <= 0) {
            $type = 'purchased';
        } elseif ((int) $beforePlan->id === (int) $sub->plan_id) {
            $type = 'renewed';
        } else {
            $type = (float) $plan->price > (float) $beforePlan->price ? 'upgraded' : 'changed';
        }

        PlanEvent::record($type, (int) $sub->doctor_id, [
            'from_plan_id'    => $beforePlan?->id,
            'to_plan_id'      => (int) $sub->plan_id,
            'subscription_id' => (int) $sub->id,
            'payment_id'      => (int) $payment->id,
            'reason'          => 'pago aprobado',
            'meta'            => ['reference' => $payment->reference, 'total_in_cents' => (int) $payment->total_in_cents, 'ends_at' => $sub->ends_at?->toDateTimeString()],
        ]);

        if (!$user || !$user->email || !$plan) {
            return;
        }

        try {
            Mail::to($user->email)->send(new PlanReceiptMail(
                (string) $user->name,
                (string) $plan->name,
                '$' . number_format($payment->total_in_cents / 100, 0, ',', '.') . ' COP',
                (string) $payment->reference,
                $sub->paid_at->copy()->timezone('America/Bogota')->format('d/m/Y H:i'),
                $sub->ends_at->copy()->timezone('America/Bogota')->format('d/m/Y'),
                $rules->homeFor($user),
                $plan->durationLabel(),
                $rules->renewWindowDays($plan),
            ));
        } catch (Throwable $e) {
            // Un fallo del correo no debe deshacer nada: el plan ya quedó activo.
            Log::warning('plans.receipt_mail_failed', ['subscription_id' => $sub->id, 'message' => $e->getMessage()]);
        }
    }
}
