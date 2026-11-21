<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso de que una cita de clínica fue reasignada a otro médico.
 * $role: 'patient' | 'previous_doctor' | 'new_doctor'
 */
class AppointmentDoctorChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Appointment $appointment,
        public string $role,
        public string $recipientName,
        public string $previousDoctorName,
        public string $newDoctorName,
        public ?string $reasonText = null
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->role) {
            'patient'         => 'Tu cita tiene un nuevo especialista - Ref: ',
            'previous_doctor' => 'Cita reasignada a otro médico - Ref: ',
            default           => 'Se te asignó una cita - Ref: ',
        };

        return new Envelope(subject: $subject . $this->appointment->reference);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.appointments.doctor-changed');
    }
}
