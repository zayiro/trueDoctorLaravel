<?php

declare(strict_types=1);

namespace App\Listeners\Payments;

use App\Enums\PaymentKind;
use App\Jobs\ProcessMedicalAnalysisAI;
use App\Jobs\SkinAnalysisJob;
use App\Mail\SkinAnalysisReceived;
use App\Models\MedicalAnalysis;
use App\Models\Payment;
use App\Models\PromoCode;
use App\Models\SkinAnalysis;
use App\Payments\Events\PaymentApproved;
use App\Services\MedicalAnalysisFilePurger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Un pago de examen o de análisis de piel quedó aprobado → la orden pasa a "procesando", se consume el código
 * y se lanza la IA. Es idempotente: el cambio de estado es atómico y solo el primero avanza.
 */
class ConfirmAnalysisOnPaymentApproved
{
    public function handle(PaymentApproved $event): void
    {
        $payment = $event->payment;

        match ($payment->kind) {
            PaymentKind::Exam         => $this->confirmExam($payment),
            PaymentKind::SkinAnalysis => $this->confirmSkin($payment),
            default                   => null,
        };
    }

    private function confirmExam(Payment $payment): void
    {
        $updated = MedicalAnalysis::whereKey($payment->payable_id)
            ->where('payment_status', '!=', 'completed')
            ->update([
                'payment_status'       => 'completed',
                'status'               => 'processing',
                'wompi_transaction_id' => $payment->provider_transaction_id,
                'payment_id'           => $payment->reference,
            ]);

        if (!$updated) {
            return; // ya estaba pagada (evento repetido) o la orden ya no existe
        }

        $this->consumePromo($payment);

        $analysis = MedicalAnalysis::find($payment->payable_id);

        if (!$analysis) {
            return;
        }

        // Pagó, pero los archivos ya no existen (purga): el dinero está recibido y hay que atenderlo a mano.
        if (app(MedicalAnalysisFilePurger::class)->paths($analysis) === []) {
            Log::critical('payments.exam_paid_without_files', ['payment' => $payment->reference, 'analysis_id' => $analysis->id]);
        }

        ProcessMedicalAnalysisAI::dispatch($analysis);
        // El correo con el informe se envía al terminar la IA.
    }

    private function confirmSkin(Payment $payment): void
    {
        $updated = SkinAnalysis::whereKey($payment->payable_id)
            ->whereIn('status', SkinAnalysis::PAYABLE_STATUSES)
            ->update([
                'status'               => SkinAnalysis::STATUS_PROCESSING,
                'wompi_transaction_id' => $payment->provider_transaction_id,
                'paid_at'              => now(),
            ]);

        if (!$updated) {
            $analysis = SkinAnalysis::find($payment->payable_id);

            // Pago tardío de una orden ya vencida: el dinero está recibido y hay que atenderlo a mano.
            if ($analysis && $analysis->status === 'expired') {
                Log::critical('payments.skin_paid_after_expiry', ['payment' => $payment->reference, 'analysis_id' => $analysis->id]);
            }

            return;
        }

        $this->consumePromo($payment);

        $analysis = SkinAnalysis::find($payment->payable_id);

        if (!$analysis) {
            return;
        }

        if (Storage::disk('private')->files($analysis->imageDirectory()) === []) {
            Log::critical('payments.skin_paid_without_photos', ['payment' => $payment->reference, 'analysis_id' => $analysis->id]);
        }

        SkinAnalysisJob::dispatch($analysis);

        try {
            Mail::to($analysis->customer_email)->queue(new SkinAnalysisReceived($analysis));
        } catch (Throwable $e) {
            // Un fallo del correo no debe romper el flujo: el análisis ya está en marcha.
            Log::warning('payments.skin_received_mail_failed', ['analysis_id' => $analysis->id, 'message' => $e->getMessage()]);
        }
    }

    /** El pago ya se aprobó: si el cupo del código se agotó justo ahora, solo se deja constancia. */
    private function consumePromo(Payment $payment): void
    {
        if (!$payment->promo_code_id) {
            return;
        }

        $consumed = PromoCode::whereKey($payment->promo_code_id)
            ->where(fn ($q) => $q->whereNull('max_uses')->orWhereColumn('uses', '<', 'max_uses'))
            ->increment('uses');

        if (!$consumed) {
            Log::warning('payments.promo_limit_reached_after_payment', [
                'payment'  => $payment->reference,
                'promo_id' => $payment->promo_code_id,
            ]);
        }
    }
}
