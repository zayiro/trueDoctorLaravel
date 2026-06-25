<?php

namespace App\Mail;

use App\Models\MedicalAnalysis;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExamPaymentPendingAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // Propiedades públicas accesibles de forma automática en la plantilla Blade
    public $recoveryUrl;
    public $analysis;

    /**
     * Crear una nueva instancia del mensaje de rescate.
     *
     * @param string $recoveryUrl
     * @param MedicalAnalysis $analysis
     */
    public function __construct(string $recoveryUrl, MedicalAnalysis $analysis)
    {
        $this->recoveryUrl = $recoveryUrl;
        $this->analysis = $analysis;
    }

    /**
     * Configurar el remitente y el asunto del correo de recuperación.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔄 Continúa con el análisis de tu examen médico',
        );
    }

    /**
     * Enlazar el mailable con la plantilla HTML correspondiente.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.exam-payment-pending-alert',
        );
    }

    /**
     * Definir archivos adjuntos si fuesen necesarios.
     */
    public function attachments(): array
    {
        return [];
    }
}
