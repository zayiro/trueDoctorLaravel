<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso al médico o clínica: su documentación necesita una corrección antes de ser aprobada.
 *
 * @param array<int, array{label: string, reason: string, hint: string}> $items
 */
class ValidationActionRequired extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $profileType,
        public array $items,
        public ?string $note,
        public string $actionUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Necesitamos que actualices tus documentos en OpenDoctor');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.validation_action_required');
    }
}
