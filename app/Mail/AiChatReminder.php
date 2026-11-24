<?php

namespace App\Mail;

use App\Models\AiChatSession;
use App\Services\AiChat\AiChatReminderText;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Recordatorio de sesión sin empezar. No lleva datos médicos. */
class AiChatReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AiChatSession $session)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: AiChatReminderText::t('mail_subject', [], $this->session->locale));
    }

    public function content(): Content
    {
        $locale = $this->session->locale;

        return new Content(
            view: 'emails.ai-chat-reminder',
            with: [
                'locale' => $locale,
                'brand' => AiChatReminderText::t('brand', [], $locale),
                'heading' => AiChatReminderText::t('mail_heading', [], $locale),
                'body' => AiChatReminderText::t('mail_body', [
                    'min' => $this->session->duration_minutes,
                    'date' => $this->session->expires_at ? AiChatReminderText::date($this->session->expires_at, $locale) : '',
                ], $locale),
                'buttonLabel' => AiChatReminderText::t('mail_button', [], $locale),
                'url' => route('ai-chat.enter', ['token' => $this->session->token_access]),
                'privacy' => AiChatReminderText::t('mail_privacy', [], $locale),
                'disclaimer' => AiChatReminderText::t('mail_disclaimer', [], $locale),
            ],
        );
    }
}
