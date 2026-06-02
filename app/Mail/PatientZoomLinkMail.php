<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PatientZoomLinkMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $appointment;
    public $zoomUrl;
    public $zoomPassword;

    public function __construct(Appointment $appointment, $zoomUrl, $zoomPassword)
    {
        $this->appointment = $appointment;
        $this->zoomUrl = $zoomUrl;
        $this->zoomPassword = $zoomPassword;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Enlace de Teleconferencia para tu Cita médica - Ref: ' . $this->appointment->reference,
        );
    }

    public function content(): Content
    {
        // NOTA: Asegúrate de tener una vista blade en resources/views/emails/patient-zoom.blade.php
        // Si no la tienes, puedes cambiar 'emails.patient-zoom' por una que ya uses para pruebas.
        return new Content(
            view: 'emails.appointments.patient-zoom',
        );
    }
}
