<?php

namespace App\Mail;

use App\Models\SkinAnalysis;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso de informe listo. No incluye resultados médicos: solo el enlace privado.
 */
class SkinAnalysisReady extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SkinAnalysis $analysis)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu informe de piel está listo');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.skin-analysis',
            with: [
                'heading' => 'Tu informe está listo',
                'body' => 'Ya puedes consultar el informe preliminar de tu fotografía. Es orientativo y no reemplaza la valoración de un profesional de la salud.',
                'buttonLabel' => 'Ver mi informe',
                'url' => route('skin-analysis.result', ['token' => $this->analysis->access_token]),
            ],
        );
    }
}