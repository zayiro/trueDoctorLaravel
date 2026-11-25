<?php

declare(strict_types=1);

namespace App\Listeners\Payments;

use App\Enums\PaymentKind;
use App\Models\PlanEvent;
use App\Models\Subscription;
use App\Payments\Events\PaymentNeedsReview;
use App\Payments\Events\PaymentVoided;
use Illuminate\Support\Facades\Log;

/**
 * Situaciones de un pago de plan que requieren una persona: monto que no coincide (queda en revisión) y pagos aprobados
 * que luego se anulan. No cambia el plan del médico por su cuenta: deja el rastro en el historial y en el log.
 * (Laravel descubre cada método `handle…` por el tipo de su parámetro.)
 */
class FlagPlanPaymentIssue
{
    public function handleReview(PaymentNeedsReview $event): void
    {
        $payment = $event->payment;

        if ($payment->kind !== PaymentKind::Subscription) {
            return;
        }

        $sub = Subscription::find($payment->payable_id);

        PlanEvent::record('payment_review', (int) ($sub->doctor_id ?? $payment->payer_id ?? 0), [
            'to_plan_id'      => $sub?->plan_id,
            'subscription_id' => $sub?->id,
            'payment_id'      => (int) $payment->id,
            'reason'          => (string) $payment->review_reason,
            'meta'            => ['reference' => $payment->reference, 'total_in_cents' => (int) $payment->total_in_cents],
        ]);

        Log::warning('plans.payment_needs_review', ['payment' => $payment->reference, 'reason' => $payment->review_reason]);
    }

    public function handleVoided(PaymentVoided $event): void
    {
        $payment = $event->payment;

        if ($payment->kind !== PaymentKind::Subscription) {
            return;
        }

        $sub = Subscription::find($payment->payable_id);

        PlanEvent::record('payment_voided', (int) ($sub->doctor_id ?? $payment->payer_id ?? 0), [
            'to_plan_id'      => $sub?->plan_id,
            'subscription_id' => $sub?->id,
            'payment_id'      => (int) $payment->id,
            'reason'          => 'pago anulado',
            'meta'            => ['reference' => $payment->reference, 'subscription_status' => $sub?->status],
        ]);

        // Si el plan ya estaba activo, decidir qué hacer (reembolso, quitar plan) es de una persona.
        Log::critical('plans.payment_voided', ['payment' => $payment->reference, 'subscription_id' => $payment->payable_id]);
    }
}
