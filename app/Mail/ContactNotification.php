<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // Al ser pública, estará disponible en la vista automáticamente
    public $contactMessage;

    public function __construct(ContactMessage $contactMessage)
    {
        $this->contactMessage = $contactMessage;
    }

    /**
     * Define el "Sobre" del correo (Metadatos)
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuevo mensaje: ' . ($this->contactMessage->subject ?? 'Consulta General'),
            replyTo: [
                $this->contactMessage->email, // Permite que al darle "Responder" le escribas al cliente
            ],
        );
    }

    /**
     * Define el "Contenido" del correo (La Vista)
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-notification',
        );
    }

    /**
     * Define adjuntos si los hubiera
     */
    public function attachments(): array
    {
        return [];
    }
}
