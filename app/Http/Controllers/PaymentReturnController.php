<?php

namespace App\Http\Controllers;

use App\Enums\PaymentKind;
use App\Enums\PaymentTransactionStatus;
use App\Models\Appointment;
use App\Models\Payment;
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

    private function retryUrl(Appointment $appointment): string
    {
        // Citas creadas por el doctor: el paciente no tiene sesión, vuelve a su enlace de pago.
        return $appointment->payment_token
            ? route('appointments.pay-link.show', $appointment->payment_token)
            : route('appointments.preview', $appointment->id);
    }
}
