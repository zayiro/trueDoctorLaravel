<?php

declare(strict_types=1);

namespace App\Listeners\Payments;

use App\Enums\PaymentKind;
use App\Models\MedicalAnalysis;
use App\Models\SkinAnalysis;
use App\Payments\Events\PaymentDeclined;

/** Pago de examen o de piel rechazado: la orden queda como "pago fallido" para poder reintentar. */
class MarkAnalysisPaymentDeclined
{
    public function handle(PaymentDeclined $event): void
    {
        $payment = $event->payment;

        if ($payment->kind === PaymentKind::Exam) {
            MedicalAnalysis::whereKey($payment->payable_id)
                ->where('payment_status', '!=', 'completed')
                ->update(['payment_status' => 'failed']);

            return;
        }

        if ($payment->kind === PaymentKind::SkinAnalysis) {
            SkinAnalysis::whereKey($payment->payable_id)
                ->whereIn('status', SkinAnalysis::PAYABLE_STATUSES)
                ->update(['status' => SkinAnalysis::STATUS_PAYMENT_FAILED]);
        }
    }
}
