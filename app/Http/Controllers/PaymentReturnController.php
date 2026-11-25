<?php

namespace App\Http\Controllers;

use App\Enums\PaymentKind;
use App\Enums\PaymentTransactionStatus;
use App\Models\Appointment;
use App\Models\MedicalAnalysis;
use App\Models\Payment;
use App\Models\SkinAnalysis;
use App\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Página a la que la pasarela devuelve al paciente. Es pública: la referencia del pago es larga y aleatoria,
 * así que sirve de llave (el paciente de un enlace de correo no tiene sesión). Solo muestra datos mínimos.
 */
class PaymentReturnController extends Controller
{
    public function __invoke(Request $request, string $reference, PaymentService $payments)
    {
        $payment = Payment::where('reference', $reference)->firstOrFail();

        // No se confía en el navegador: se le pregunta a la pasarela (idempotente con el webhook).
        if ($payment->status === PaymentTransactionStatus::Pending && $payment->method->value === 'online') {
            // Wompi devuelve al usuario con ?id=<transacción>: es la consulta más exacta en ese instante.
            $transactionId = (string) $request->query('id', '');

            if ($transactionId !== '' && preg_match('/^[A-Za-z0-9_\-]{5,80}$/', $transactionId)) {
                try {
                    $payments->syncByTransactionId($transactionId);
                    $payment = $payment->fresh() ?? $payment;
                } catch (Throwable $e) {
                    Log::warning('payments.return_sync_by_id_failed', ['payment' => $reference, 'error' => $e->getMessage()]);
                }
            }

            // Respaldo: si sigue pendiente, se pregunta por la referencia.
            if ($payment->status === PaymentTransactionStatus::Pending) {
                try {
                    $payment = $payments->syncByReference($reference) ?? $payment;
                } catch (Throwable $e) {
                    Log::warning('payments.return_sync_failed', ['payment' => $reference, 'error' => $e->getMessage()]);
                }
            }
        }

        if ($payment->kind === PaymentKind::Subscription) {
            return app(\App\Http\Controllers\PlanPaymentResultController::class)->show($payment);
        }

        if ($payment->kind === PaymentKind::Exam) {
            return $this->examResult($payment);
        }

        if ($payment->kind === PaymentKind::SkinAnalysis) {
            return $this->skinResult($payment);
        }

        if ($payment->kind === PaymentKind::AiChat) {
            return app(\App\Http\Controllers\AiChatPurchaseController::class)->result($payment);
        }

        if ($payment->kind !== PaymentKind::Appointment) {
            return redirect()->route('home');
        }

        $appointment = Appointment::with(['doctor.user', 'service', 'patient.user'])->findOrFail($payment->payable_id);

        $status = match ($payment->status) {
            PaymentTransactionStatus::Approved => 'approved',
            PaymentTransactionStatus::Declined,
            PaymentTransactionStatus::Voided,
            PaymentTransactionStatus::Error    => 'declined',
            default                            => 'pending',
        };

        if ($status === 'approved' && Auth::check() && (int) $appointment->patient?->user_id === (int) Auth::id()) {
            return redirect()->route('appointments.success', $appointment);
        }

        $expired = $appointment->payment_expires_at !== null && $appointment->payment_expires_at->isPast();
        $canRetry = in_array($status, ['declined', 'pending'], true)
            && !$expired
            && in_array((string) ($appointment->status instanceof \BackedEnum ? $appointment->status->value : $appointment->status), ['pending', 'confirmed'], true);

        return view('appointments.payment-result', [
            'appointment' => $appointment,
            'status'      => $status,
            'payment'     => $payment,
            'canRetry'    => $canRetry,
            'retryUrl'    => $this->retryUrl($appointment),
        ]);
    }

    /** Resultado del pago de un examen: la misma pantalla de siempre (aprobado, en verificación o declinado). */
    private function examResult(Payment $payment)
    {
        $analysis = MedicalAnalysis::findOrFail($payment->payable_id);

        $paymentStatus = match ($payment->status) {
            PaymentTransactionStatus::Approved => 'APPROVED',
            PaymentTransactionStatus::Declined,
            PaymentTransactionStatus::Voided,
            PaymentTransactionStatus::Error    => 'DECLINED',
            default                            => 'PENDING',
        };

        return response()
            ->view('medical-analysis.payment-result', [
                'analysis'      => $analysis,
                'paymentStatus' => $paymentStatus,
            ])
            ->withHeaders([
                'X-Robots-Tag'    => 'noindex, nofollow, noarchive',
                'Cache-Control'   => 'no-store, private',
                'Referrer-Policy' => 'no-referrer',
            ]);
    }

    /** Resultado del pago de piel: aprobado va al informe; lo demás vuelve al resumen de pago con un aviso. */
    private function skinResult(Payment $payment)
    {
        $analysis = SkinAnalysis::findOrFail($payment->payable_id);

        if ($payment->status === PaymentTransactionStatus::Approved) {
            return redirect()->route('skin-analysis.result', ['token' => $analysis->access_token]);
        }

        // Sin posibilidad de reintentar (la orden venció o ya avanzó): no hay a dónde volver a pagar.
        if (!in_array($analysis->status, SkinAnalysis::PAYABLE_STATUSES, true)) {
            return redirect()->route('skin-analysis.index')
                ->with('error', 'Esta orden ya no se puede pagar. Inicia un nuevo análisis.');
        }

        $declined = in_array($payment->status, [
            PaymentTransactionStatus::Declined,
            PaymentTransactionStatus::Voided,
            PaymentTransactionStatus::Error,
        ], true);

        return redirect()->route('skin-analysis.payment', ['token' => $analysis->access_token])
            ->with('error', $declined
                ? 'El pago fue rechazado. Intenta nuevamente.'
                : 'Tu pago aún está pendiente de confirmación. Si ya pagaste, espera unos minutos y recarga.');
    }

    private function retryUrl(Appointment $appointment): string
    {
        // Citas creadas por el doctor: el paciente no tiene sesión, vuelve a su enlace de pago.
        return $appointment->payment_token
            ? route('appointments.pay-link.show', $appointment->payment_token)
            : route('appointments.preview', $appointment->id);
    }
}
