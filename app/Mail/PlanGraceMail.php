<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso al médico: su plan venció pero conserva sus beneficios unos días más. Solo datos simples. */
class PlanGraceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $planName,
        public string $endedAt,
        public string $graceUntil,
        public string $renewUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Tu {$this->planName} venció: renuévalo antes del {$this->graceUntil}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.plans.grace');
    }
}
