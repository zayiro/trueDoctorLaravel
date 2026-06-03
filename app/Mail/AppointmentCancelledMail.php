<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class AppointmentCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public $appointment;

    public $userAction;

    public function __construct(Appointment $appointment, $userAction)
    {
        $this->appointment = $appointment;
        $this->userAction = $userAction;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cita Cancelada - Ref: ' . $this->appointment->reference,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.appointments.cancelled',
        );
    }
}
