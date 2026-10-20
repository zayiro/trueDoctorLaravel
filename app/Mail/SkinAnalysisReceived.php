<?php

namespace App\Mail;

use App\Models\SkinAnalysis;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso de pago (o solicitud) recibido: el análisis está en proceso.
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
            subject: $this->isFree()
                ? 'Recibimos tu solicitud: estamos analizando tu fotografía'
                : 'Recibimos tu pago: estamos analizando tu fotografía',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.skin-analysis',
            with: [
                'heading' => $this->isFree() ? 'Solicitud recibida' : 'Pago recibido',
                'body' => 'Gracias. Estamos analizando tu fotografía; en un momento tu informe estará disponible en el siguiente enlace.',
                'buttonLabel' => 'Ver mi análisis',
                'url' => route('skin-analysis.result', ['token' => $this->analysis->access_token]),
            ],
        );
    }

    /** Las órdenes cubiertas al 100% por un código no tienen pago. */
    private function isFree(): bool
    {
        return (int) $this->analysis->total_amount === 0;
    }
}