<?php

namespace App\Mail;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Correo al paciente cuando el doctor emite un certificado.
 * No incluye contenido clínico en el cuerpo. Solo la constancia de asistencia lleva el PDF adjunto.
 * REVISAR CON ABOGADO: textos de resources/views/emails/certificates/issued.blade.php
 */
class CertificateIssuedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Certificate $certificate;
    public string $recipientName;
    public string $typeLabel;
    public string $code;
    public string $doctorName;
    public string $verifyUrl;
    public string $downloadUrl;
    public bool $withAttachment;

    public function __construct(Certificate $certificate)
    {
        $this->certificate = $certificate;
        $certificate->loadMissing(['patient.user', 'doctor.user', 'signedDocument']);

        $this->recipientName = (string) ($certificate->patient?->user?->name ?? '');
        $this->typeLabel = $certificate->typeLabel();
        $this->code = (string) $certificate->code;
        $this->doctorName = (string) ($certificate->doctor?->user?->name ?? '');
        $this->verifyUrl = route('certificates.verify.show', ['code' => $certificate->code]);
        $this->downloadUrl = route('patient.certificates.download', ['certificate' => $certificate->id]);
        // Solo la constancia viaja adjunta; el certificado médico se descarga iniciando sesión.
        $this->withAttachment = $certificate->type === Certificate::TYPE_ATTENDANCE && $this->pdfPath() !== null;
    }

    private function diskName(): string
    {
        return (string) config('certificates.disk', 'private');
    }

    private function pdfPath(): ?string
    {
        $path = $this->certificate->signedDocument?->storage_path;
        if (!$path) {
            return null;
        }
        try {
            return Storage::disk($this->diskName())->exists($path) ? $path : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->typeLabel . ' - ' . $this->code);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.certificates.issued');
    }

    public function attachments(): array
    {
        $path = $this->withAttachment ? $this->pdfPath() : null;
        if ($path === null) {
            return [];
        }

        return [
            Attachment::fromStorageDisk($this->diskName(), $path)
                ->as('constancia-' . $this->code . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
