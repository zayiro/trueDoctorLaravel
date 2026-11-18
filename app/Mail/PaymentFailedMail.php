<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⏳ Tu cita está reservada, solo falta completar el pago',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.appointments.payment-failed',
            with: [
                'appointment' => $this->appointment,
                'retryUrl'    => $this->retryUrl(),
                'expiresAt'   => $this->expiresText(),
                'total'       => $this->total(),
            ]
        );
    }

    /** Citas creadas por el doctor: el paciente no tiene sesión, así que vuelve a su enlace público de pago. */
    private function retryUrl(): string
    {
        return $this->appointment->payment_token
            ? route('appointments.pay-link.show', $this->appointment->payment_token)
            : route('appointments.preview', $this->appointment->id);
    }

    /** Hasta cuándo queda guardada la reserva (con el día si no es hoy). Citas anteriores: 2 h desde la última actualización. */
    private function expiresText(): string
    {
        $tz = config('booking.timezone', 'America/Bogota');

        $limit = ($this->appointment->payment_expires_at ?? $this->appointment->updated_at->copy()->addHours(2))
            ->copy()
            ->timezone($tz);

        return $limit->isToday()
            ? 'las ' . $limit->format('H:i')
            : 'el ' . $limit->format('d/m/Y') . ' a las ' . $limit->format('H:i');
    }

    /** Lo que se cobra: el total del último intento de pago; si no hay, el cálculo de las citas anteriores. */
    private function total(): float
    {
        $cents = Payment::where('payable_type', $this->appointment->getMorphClass())
            ->where('payable_id', $this->appointment->id)
            ->latest('id')
            ->value('total_in_cents');

        return $cents !== null
            ? $cents / 100
            : (float) $this->appointment->price + (float) $this->appointment->commission_amount;
    }
}
