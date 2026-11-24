<?php

namespace App\Mail;

use App\Models\AiChatWaitlist;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso único de lanzamiento a quien lo pidió en la lista de espera. */
class AiChatLaunch extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AiChatWaitlist $entry)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'El Chat de Salud con IA de OpenDoctor ya está disponible');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.ai-chat-launch', with: ['url' => route('ai-chat.landing')]);
    }
}
