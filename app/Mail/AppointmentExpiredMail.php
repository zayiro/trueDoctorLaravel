<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentExpiredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '❌ Tu reserva de cita médica ha expirado',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.appointments.appointment-expired',
            with: [
                'appointment' => $this->appointment,
                'bookAgainUrl' => route('home'),
            ]
        );
    }
}