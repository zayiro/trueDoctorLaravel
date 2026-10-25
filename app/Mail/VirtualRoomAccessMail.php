<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VirtualRoomAccessMail extends Mailable
{
    use Queueable, SerializesModels;

    // Propiedad pública para almacenar el nombre del paciente
    public $patientName;
    public $roomUrl;
    public $appointmentId;

    /**
     * Create a new message instance.
     */
    public function __construct($patientName, $roomUrl, $appointmentId)
    {
        $this->patientName = $patientName;
        $this->roomUrl = $roomUrl;
        $this->appointmentId = $appointmentId;
    }

    // Configura el asunto y detalles del encabezado del correo
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Acceso permitido a la sala virtual',
        );
    }

    // Define la vista Blade en inglés que renderizará el cuerpo del correo
    public function content(): Content
    {
        return new Content(
            view: 'emails.appointments.virtual-room-access',
        );
    }

    // Método opcional por si necesitas adjuntar archivos en el futuro
    public function attachments(): array
    {
        return [];
    }
}
