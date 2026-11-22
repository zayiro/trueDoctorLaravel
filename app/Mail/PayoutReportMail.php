<?php

namespace App\Mail;

use App\Models\Clinic;
use App\Models\PayoutReport;
use App\Services\Payments\PayoutReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Soporte de liquidación con el PDF adjunto. Se envía desde un job, por eso no se encola de nuevo. */
class PayoutReportMail extends Mailable
{
    use Queueable;

    public function __construct(public PayoutReport $report) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Soporte de liquidación ' . $this->report->number);
    }

    public function content(): Content
    {
        $b = PayoutReportService::beneficiary($this->report);

        return new Content(markdown: 'emails.payouts.report', with: [
            'name'       => $b['name'],
            'number'     => $this->report->number,
            'total'      => '$' . number_format((float) $this->report->total_amount, 0, ',', '.') . ' COP',
            'lines'      => $this->report->lines_count,
            'reference'  => $this->report->transfer_reference,
            'paidAt'     => $this->report->paid_at?->timezone('America/Bogota')->format('d/m/Y'),
            'url'        => route($this->report->payable_type === Clinic::class ? 'partner.clinic.payouts.index' : 'partner.payouts.index'),
        ]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk(PayoutReportService::DISK, $this->report->pdf_path)
                ->as('soporte-liquidacion-' . $this->report->number . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
