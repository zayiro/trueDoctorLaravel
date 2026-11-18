<?php

declare(strict_types=1);

namespace App\Listeners\Payments;

use App\Enums\PaymentKind;
use App\Mail\PaymentFailedMail;
use App\Models\Appointment;
use App\Payments\Events\PaymentDeclined;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Pago de cita rechazado: se avisa al paciente (la reserva sigue guardada hasta que venza). */
class NotifyAppointmentPaymentDeclined
{
    public function handle(PaymentDeclined $event): void
    {
        $payment = $event->payment;

        if ($payment->kind !== PaymentKind::Appointment) {
            return;
        }

        $appointment = Appointment::with('patient.user')->find($payment->payable_id);
        $email = $appointment?->patient?->user?->email;

        if (!$email) {
            return;
        }

        try {
            Mail::to($email)->send(new PaymentFailedMail($appointment));
        } catch (Throwable $e) {
            Log::error('payments.declined_mail_failed', ['payment' => $payment->reference, 'error' => $e->getMessage()]);
        }
    }
}
