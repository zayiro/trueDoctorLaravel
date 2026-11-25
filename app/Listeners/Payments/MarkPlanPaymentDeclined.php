<?php

declare(strict_types=1);

namespace App\Listeners\Payments;

use App\Enums\PaymentKind;
use App\Models\Subscription;
use App\Payments\Events\PaymentDeclined;

/**
 * Un pago de plan fue rechazado (o dio error): la suscripción que lo esperaba queda marcada.
 * Solo toca filas todavía `pending`; si Wompi luego aprueba la misma referencia, ConfirmPlanOnPaymentApproved la activa.
 */
class MarkPlanPaymentDeclined
{
    public function handle(PaymentDeclined $event): void
    {
        $payment = $event->payment;

        if ($payment->kind !== PaymentKind::Subscription) {
            return;
        }

        Subscription::whereKey($payment->payable_id)
            ->where('status', 'pending')
            ->update(['status' => $payment->status->value === 'error' ? 'error' : 'declined', 'updated_at' => now()]);
    }
}
