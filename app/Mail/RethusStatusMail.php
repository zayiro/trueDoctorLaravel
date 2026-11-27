<?php

namespace App\Mail;

use App\Models\Doctor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correos de la verificación ReTHUS al doctor: verified | mismatch | expiring | expired.
 * REVISAR CON ABOGADO: textos de resources/views/emails/rethus/status.blade.php
 */
class RethusStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $kind;
    public string $doctorName;
    public string $url;
    public ?string $expiresAt;
    public ?string $reasonLabel = null;
    public ?string $reasonHint = null;

    public function __construct(Doctor $doctor, string $kind)
    {
        $this->kind = in_array($kind, ['verified', 'mismatch', 'expiring', 'expired'], true) ? $kind : 'verified';
        $this->doctorName = (string) ($doctor->user?->name ?? '');
        $this->url = route('partner.rethus.show');
        $this->expiresAt = $doctor->rethus_expires_at?->copy()->setTimezone('America/Bogota')->format('d/m/Y');

        if ($this->kind === 'mismatch') {
            $r = (array) (config('rethus.mismatch_reasons')[(string) $doctor->rethus_mismatch_reason] ?? config('rethus.mismatch_reasons.data_error', []));
            $this->reasonLabel = $r['label'] ?? null;
            $this->reasonHint = $r['hint'] ?? null;
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: match ($this->kind) {
            'verified' => 'Tu registro fue verificado en ReTHUS',
            'mismatch' => 'No pudimos verificar tu registro en ReTHUS',
            'expiring' => 'Tu verificación en ReTHUS vence pronto',
            default    => 'Tu verificación en ReTHUS venció',
        });
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.rethus.status');
    }
}
