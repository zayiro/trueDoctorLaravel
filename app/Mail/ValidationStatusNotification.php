<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// El nombre de la clase debe coincidir exactamente con el archivo
class ValidationStatusNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $status;
    public $whatsappLink;

    public function __construct(User $user, string $status)
    {
        $this->user = $user;
        $this->status = $status; // 'approved' o 'rejected'
        
        // Enlace directo a tu WhatsApp de soporte
        $this->whatsappLink = "https://wa.me" . urlencode("Hola Soporte de OpenDoctor, mi cuenta de médico está en estado: " . $status);
    }

    public function envelope(): Envelope
    {
        $subject = $this->status === 'approved' 
            ? '¡Buenas noticias! Tu cuenta en OpenDoctor ha sido aprobada' 
            : 'Actualización sobre tu solicitud de cuenta en OpenDoctor';

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.validation_status', // Apunta a resources/views/emails/validation_status.blade.php
        );
    }
}
