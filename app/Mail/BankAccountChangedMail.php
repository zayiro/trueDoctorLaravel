<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso de seguridad: la cuenta de pago de un beneficiario se registró o cambió. Nunca lleva el número completo. */
class BankAccountChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public bool $forAdmin,
        public string $name,
        public string $masked,
        public string $bank,
        public string $type,
        public string $when,
        public bool $created,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->forAdmin
            ? 'Cambio de cuenta bancaria de ' . $this->name
            : ($this->created ? 'Registraste tu cuenta para recibir liquidaciones' : 'Cambió la cuenta para recibir tus liquidaciones'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.payouts.account-changed');
    }
}
