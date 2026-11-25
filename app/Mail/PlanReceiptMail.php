<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Comprobante de pago de un plan de suscripción. Solo datos simples. */
class PlanReceiptMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $planName,
        public string $amount,
        public string $reference,
        public string $paidAt,
        public string $endsAt,
        public string $profileUrl,
        public string $period = '30 días',
        public int $renewDays = 7,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Comprobante de pago: {$this->planName}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.plans.receipt');
    }
}
