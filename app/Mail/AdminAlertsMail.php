<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso al administrador: saldos de proveedores y cobros que requieren atención. Solo datos simples. */
class AdminAlertsMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @param array<int, array{key:string, level:string, title:string, detail:string}> $items */
    public function __construct(public array $items, public bool $isTest = false) {}

    public function envelope(): Envelope
    {
        $crit = count(array_filter($this->items, fn ($i) => $i['level'] === 'crit'));
        $subject = $this->isTest
            ? 'OpenDoctor: correo de prueba de avisos'
            : 'OpenDoctor: ' . ($crit > 0 ? "{$crit} aviso(s) urgente(s)" : count($this->items) . ' aviso(s)') . ' para revisar';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.alerts.admin', with: ['items' => $this->items, 'url' => url('/admin'), 'isTest' => $this->isTest]);
    }
}
