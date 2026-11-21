<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso al PACIENTE de que su cita fue reprogramada.
 * Lo envía SendAppointmentRescheduledNotification (que ya corre en cola), por eso este mailable no es ShouldQueue.
 */
class AppointmentRescheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Appointment $appointment,
        public string $previousDateTime
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cita Reprogramada - Ref: ' . $this->appointment->reference,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointments.rescheduled',
        );
    }
}
