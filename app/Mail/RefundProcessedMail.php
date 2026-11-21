<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Models\PaymentRefund;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso al paciente: el reembolso de su cita cancelada ya fue procesado. Solo datos simples (sin modelos). */
class RefundProcessedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $recipientName;
    public string $reference;
    public string $serviceName;
    public string $dateLabel;
    public string $amount;
    public ?string $providerReference;

    public function __construct(PaymentRefund $refund)
    {
        $appointment = Appointment::withTrashed()->with(['patient.user', 'service'])->find($refund->appointment_id);

        $this->recipientName     = (string) ($appointment?->patient?->user?->name ?? '');
        $this->reference         = (string) ($appointment?->reference ?? '');
        $this->serviceName       = (string) ($appointment?->service?->name ?? 'Consulta médica');
        $this->dateLabel         = $appointment
            ? Carbon::parse($appointment->date)->translatedFormat('l d \d\e F, Y') . ' · ' . Carbon::parse($appointment->start_time)->format('g:i A')
            : '';
        $this->amount            = '$' . number_format($refund->amount_in_cents / 100, 0, ',', '.') . ' COP';
        $this->providerReference = $refund->provider_reference;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reembolso procesado - Ref: ' . $this->reference);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.refunds.processed');
    }
}
