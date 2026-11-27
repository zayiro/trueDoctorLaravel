<?php

namespace App\Services\Certificates;

use App\Mail\CertificateIssuedMail;
use App\Models\Certificate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Avisa al paciente por correo de que se emitió su certificado.
 *  - Constancia de asistencia: el correo lleva el PDF adjunto (no tiene información clínica).
 *  - Certificado médico: el correo solo avisa y enlaza a su cuenta; el PDF no viaja por correo.
 * Si el correo falla, el certificado NO se afecta: solo queda sin marcar emailed_at para poder reenviarlo.
 */
class CertificateNotifier
{
    /** @return array{ok: bool, message: string} */
    public function notify(Certificate $certificate): array
    {
        try {
            $certificate->loadMissing(['patient.user', 'doctor.user', 'signedDocument']);

            if ($certificate->isRevoked()) {
                return ['ok' => false, 'message' => 'El certificado está anulado: no se envía por correo.'];
            }

            $email = trim((string) ($certificate->patient?->user?->email ?? ''));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['ok' => false, 'message' => 'El paciente no tiene un correo válido registrado.'];
            }

            Mail::to($email)->send(new CertificateIssuedMail($certificate));

            $certificate->forceFill(['emailed_at' => now()])->save();

            return ['ok' => true, 'message' => 'Enviamos el aviso al correo del paciente.'];
        } catch (\Throwable $e) {
            Log::warning('certificate.mail.failed', ['certificate_id' => $certificate->id, 'error' => substr($e->getMessage(), 0, 300)]);

            return ['ok' => false, 'message' => 'No pudimos enviar el correo al paciente en este momento.'];
        }
    }
}
