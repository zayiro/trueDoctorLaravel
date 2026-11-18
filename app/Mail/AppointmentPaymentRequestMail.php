<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Correo al paciente con el enlace para pagar una cita que le agendó su doctor o clínica. */
class AppointmentPaymentRequestMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public Appointment $appointment,
        public string $payUrl,
        public float $total,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Paga tu cita médica para confirmarla — Ref. ' . $this->appointment->reference);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.appointments.payment-request');
    }
}
