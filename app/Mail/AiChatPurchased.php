<?php

namespace App\Mail;

use App\Models\AiChatSession;
use App\Services\AiChat\AiChatText;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo con el enlace de la sesión. No lleva datos médicos.
 */
class AiChatPurchased extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AiChatSession $session)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: AiChatText::t('mail_subject', [], $this->session->locale));
    }

    public function content(): Content
    {
        $locale = $this->session->locale;
        $expires = $this->session->expires_at
            ? $this->session->expires_at->copy()->timezone('America/Bogota')->locale($locale)
                ->translatedFormat($locale === 'en' ? 'F j, Y' : 'j \d\e F \d\e Y')
            : '';

        return new Content(
            view: 'emails.ai-chat-purchased',
            with: [
                'locale' => $locale,
                'heading' => AiChatText::t('mail_heading', [], $locale),
                'body' => AiChatText::t('mail_body', ['min' => $this->session->duration_minutes], $locale),
                'buttonLabel' => AiChatText::t('mail_button', [], $locale),
                'url' => route('ai-chat.enter', ['token' => $this->session->token_access]),
                'expires' => AiChatText::t('mail_expires', ['date' => $expires], $locale),
                'privacy' => AiChatText::t('mail_privacy', [], $locale),
                'disclaimer' => AiChatText::t('mail_disclaimer', [], $locale),
                'brand' => AiChatText::t('name', [], $locale),
            ],
        );
    }
}
