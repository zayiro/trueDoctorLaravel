<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbandonedAccountWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $keepUrl)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: (string) config('account_cleanup.texts.subject'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.accounts.abandoned-warning');
    }
}
