<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso al médico: su plan venció y pasó al plan gratuito. Solo datos simples. */
class PlanExpiredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $userName, public string $planName, public string $freeName, public int $addressesOff, public string $renewUrl, public int $doctorsOff = 0) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Tu {$this->planName} terminó");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.plans.expired');
    }
}
