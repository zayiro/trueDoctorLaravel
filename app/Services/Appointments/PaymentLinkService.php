<?php

declare(strict_types=1);

namespace App\Services\Appointments;

use App\Mail\AppointmentPaymentRequestMail;
use App\Models\Appointment;
use App\Services\Payments\AppointmentCheckout;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Enlace de pago "sin login" de las citas que crea el doctor/clínica.
 * El token es la llave: largo, aleatorio, y solo sirve para pagar ESA cita.
 */
class PaymentLinkService
{
    public function __construct(private readonly AppointmentCheckout $checkout)
    {
    }

    public function newToken(): string
    {
        return Str::random(48);
    }

    /** Vencimiento del enlace: 24 h (configurable) pero nunca después de la hora de la cita. */
    public function expiryFor(Carbon $startsAt): Carbon
    {
        return now()->addHours((int) config('booking.payment_link_hours', 24))->min($startsAt);
    }

    public function url(Appointment $appointment): ?string
    {
        return $appointment->payment_token
            ? route('appointments.pay-link.show', $appointment->payment_token)
            : null;
    }

    /** ¿Sigue esperando pago con un enlace vigente? (para mostrar "Reenviar" / "Copiar enlace"). */
    public function isOpen(Appointment $appointment): bool
    {
        return $appointment->payment_token !== null
            && $this->raw($appointment->status) === 'pending'
            && $this->raw($appointment->payment_status) === 'pending'
            && $appointment->payment_expires_at !== null
            && $appointment->payment_expires_at->isFuture();
    }

    /** Envía (o reenvía) el correo con el enlace. Devuelve false si el correo falló. */
    public function send(Appointment $appointment): bool
    {
        $appointment->loadMissing(['patient.user', 'doctor.user', 'clinic', 'service', 'address']);

        $email = $appointment->patient?->user?->email;

        if (!$email || !$this->isOpen($appointment)) {
            return false;
        }

        try {
            $quote = $this->checkout->quote($appointment);

            Mail::to($email)->send(new AppointmentPaymentRequestMail(
                $appointment,
                (string) $this->url($appointment),
                $quote->totalCents / 100,
            ));

            return true;
        } catch (Throwable $e) {
            Log::error('payment_link.mail_failed', ['appointment_id' => $appointment->id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    private function raw(mixed $v): string
    {
        return $v instanceof \BackedEnum ? (string) $v->value : (string) $v;
    }
}
