<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Respuesta del equipo de soporte a un mensaje del formulario de contacto.
 * A propósito NO implementa ShouldQueue: se envía al instante, para que el admin vea de inmediato si salió o falló.
 */
class ContactReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContactMessage $contactMessage,
        public string $replyBody,
    ) {
    }

    public function envelope(): Envelope
    {
        $soporte = new Address(config('mail.from.address'), config('mail.from.name'));
        $asunto = trim(preg_replace('/\s+/', ' ', (string) $this->contactMessage->subject));

        return new Envelope(
            from: $soporte,
            replyTo: [$soporte],
            subject: 'Re: ' . ($asunto !== '' ? $asunto : 'Tu mensaje a OpenDoctor'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.contact-reply');
    }

    public function attachments(): array
    {
        return [];
    }
}
