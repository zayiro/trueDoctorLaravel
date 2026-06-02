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

    // Número máximo de veces que se intentará enviar el correo si falla
    public $tries = 3;

    // Segundos de espera entre cada intento (Ej: espera 60 segundos antes de reintentar)
    public $backoff = 60;

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
            subject: 'Contactenos: ' . ($this->contactMessage->subject ?? 'Formulario de contactenos'),            
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * El trabajo falló definitivamente después de todos los intentos.
     */
    public function failed(Throwable $exception): void
    {
        Log::critical("El correo de contacto #{$this->contactMessage->id} falló definitivamente: " . $exception->getMessage());
    }
}
