<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso al médico o a la clínica cuando cambia su porcentaje de servicio. */
class SpecialCommissionChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $virtualText,
        public string $presentialText,
        public bool $isClinic,
        public string $when,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Cambió tu porcentaje de servicio en OpenDoctor');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.commissions.special-changed');
    }
}
