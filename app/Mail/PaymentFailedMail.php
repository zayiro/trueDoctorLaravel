<?php

namespace App\Mail;

use App\Models\Appointment;
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
                'retryUrl'    => route('appointments.preview', $this->appointment->id),
                'expiresAt'   => $this->appointment->updated_at->addHours(2)->format('d/m/Y H:i'),
            ]
        );
    }
}