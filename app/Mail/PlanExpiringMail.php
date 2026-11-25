<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso al médico: su plan de pago vence en pocos días. Solo datos simples. */
class PlanExpiringMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $userName, public string $planName, public string $endsAt, public string $renewUrl, public int $daysLeft = 3, public string $period = '30 días') {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Tu {$this->planName} vence el {$this->endsAt}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.plans.expiring');
    }
}
