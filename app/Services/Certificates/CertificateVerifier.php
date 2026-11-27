<?php

namespace App\Services\Certificates;

use App\Models\Certificate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Comprueba que el PDF guardado de un certificado no cambió desde que se firmó:
 *  1) el SHA-256 del archivo guardado coincide con el registrado al emitir, y
 *  2) la firma HMAC (hash + ids + tarjeta profesional + fecha + tipo + código) coincide.
 */
class CertificateVerifier
{
    /** Firma HMAC. La usan el emisor (al firmar) y este verificador (al comprobar). */
    public static function signature(
        string $documentHash,
        int $certificateId,
        string $code,
        int $doctorId,
        ?string $doctorLicense,
        int $patientId,
        int $signedTimestamp,
        string $signedType
    ): string {
        return hash_hmac('sha256', implode('|', [
            $documentHash,
            $certificateId,
            $code,
            $doctorId,
            (string) $doctorLicense,
            $patientId,
            $signedTimestamp,
            $signedType,
        ]), (string) config('app.key'));
    }

    /**
     * @return array{intact: bool, reason: ?string}
     */
    public function verify(Certificate $certificate): array
    {
        try {
            $doc = $certificate->signedDocument;

            if (!$doc) {
                return ['intact' => false, 'reason' => 'no_signature'];
            }

            $disk = Storage::disk((string) config('certificates.disk', 'private'));

            if (!$disk->exists($doc->storage_path)) {
                return ['intact' => false, 'reason' => 'file_missing'];
            }

            $content = (string) $disk->get($doc->storage_path);

            if (!hash_equals((string) $doc->document_hash, hash('sha256', $content))) {
                return ['intact' => false, 'reason' => 'hash_mismatch'];
            }

            $snapshot = (array) ($certificate->snapshot ?? []);

            $expected = self::signature(
                (string) $doc->document_hash,
                (int) $certificate->id,
                (string) $certificate->code,
                (int) $certificate->doctor_id,
                $snapshot['doctor_license'] ?? null,
                (int) $certificate->patient_id,
                (int) $doc->signed_at->timestamp,
                (string) $doc->type
            );

            if (!hash_equals((string) $doc->signature_hash, $expected)) {
                return ['intact' => false, 'reason' => 'signature_mismatch'];
            }

            return ['intact' => true, 'reason' => null];
        } catch (\Throwable $e) {
            Log::warning('certificate.verify.error', [
                'certificate_id' => $certificate->id,
                'error' => substr($e->getMessage(), 0, 200),
            ]);

            return ['intact' => false, 'reason' => 'error'];
        }
    }
}
