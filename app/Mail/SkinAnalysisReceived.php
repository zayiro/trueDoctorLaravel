<?php

namespace App\Mail;

use App\Models\SkinAnalysis;
use App\Support\SkinAnalysisText;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso de pago recibido: el análisis está en proceso.
 */
class SkinAnalysisReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SkinAnalysis $analysis)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: SkinAnalysisText::get($this->analysis->analysis_language, 'mail_received_subject'),
        );
    }

    public function content(): Content
    {
        $lang = $this->analysis->analysis_language;

        return new Content(
            view: 'emails.skin-analysis',
            with: [
                'heading' => SkinAnalysisText::get($lang, 'mail_received_heading'),
                'body' => SkinAnalysisText::get($lang, 'mail_received_body'),
                'buttonLabel' => SkinAnalysisText::get($lang, 'mail_received_button'),
                'footer' => SkinAnalysisText::get($lang, 'mail_footer'),
                'url' => route('skin-analysis.result', ['token' => $this->analysis->access_token]),
            ],
        );
    }
}