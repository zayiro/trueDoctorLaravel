<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProviderAppointmentRescheduledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Appointment $appointment,
        public string $previousDateTime
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Agenda Actualizada: Cita Médica Reprogramada - Ref: ' . $this->appointment->reference,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.rescheduled',
        );
    }
}
