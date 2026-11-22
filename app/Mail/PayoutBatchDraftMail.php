<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso al equipo: el borrador del lote semanal está listo para revisar. Solo datos simples. */
class PayoutBatchDraftMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $number, public int $lines, public int $beneficiaries, public string $total, public int $batchId) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Lote de liquidación ' . $this->number . ' listo para revisar');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.payouts.draft', with: ['url' => route('administrator.payouts.batches.show', $this->batchId)]);
    }
}
