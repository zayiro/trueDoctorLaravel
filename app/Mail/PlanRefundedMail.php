<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso al usuario: se registró la devolución del pago de su plan. Solo datos simples. */
class PlanRefundedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $planName,
        public string $amount,
        public ?string $keptUntil,
        public ?string $freeName,
        public int $addressesOff,
        public int $doctorsOff,
        public string $plansUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Reembolso de tu {$this->planName}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.plans.refunded');
    }
}
