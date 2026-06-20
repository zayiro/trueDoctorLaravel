<?php

namespace App\Mail;

use App\Models\MedicalAnalysis;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExamAnalysisReady extends Mailable
{
    use SerializesModels;

    // Propiedad pública para que esté disponible automáticamente en la vista Blade
    public $analysis;

    /**
     * Crear una nueva instancia del mensaje.
     */
    public function __construct(MedicalAnalysis $analysis)
    {
        $this->analysis = $analysis;
    }

    /**
     * Definir el asunto y encabezados del correo.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📋 Tu Reporte Clínico Inteligente está listo',
        );
    }

    /**
     * Definir la vista del cuerpo del correo.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.exam-analysis-ready',
        );
    }

    /**
     * Adjuntar archivos si lo deseas (Opcional)
     */
    public function attachments(): array
    {
        return [];
    }
}
