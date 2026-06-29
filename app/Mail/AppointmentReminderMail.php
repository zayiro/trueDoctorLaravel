<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⏰ Recordatorio: Tu cita médica es mañana',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.appointments.reminder',
            with: [
                'appointment' => $this->appointment,
                'date'        => \Carbon\Carbon::parse($this->appointment->date)->format('d/m/Y'),
                'time'        => \Carbon\Carbon::parse($this->appointment->start_time)->format('H:i'),
                'doctor'      => $this->appointment->doctor?->user?->name ?? 'tu especialista',
                'dashboardUrl' => route('home'),
            ]
        );
    }
}