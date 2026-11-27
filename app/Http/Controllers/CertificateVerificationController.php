<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Services\Certificates\CertificateVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Página pública para comprobar un certificado con su código (o escaneando el QR).
 * Nunca muestra diagnóstico ni contenido clínico, y nunca un error crudo:
 * todo caso (no existe, anulado, no verificable, fallo interno) responde con una página amable.
 */
class CertificateVerificationController extends Controller
{
    /** GET /verify-certificate  (formulario; con ?code= redirige a la página del código) */
    public function form(Request $request)
    {
        $raw = trim((string) $request->query('code', ''));

        if ($raw === '') {
            return $this->page('form');
        }

        $normalized = Certificate::normalizeCode($raw);

        if ($normalized === null) {
            return $this->page('invalid_format', ['typed' => mb_substr($raw, 0, 40)]);
        }

        return redirect()->route('certificates.verify.show', ['code' => $normalized]);
    }

    /** GET /verify-certificate/{code} */
    public function show(string $code)
    {
        try {
            $normalized = Certificate::normalizeCode($code);

            if ($normalized === null) {
                return $this->page('invalid_format', ['typed' => mb_substr($code, 0, 40)]);
            }

            $certificate = Certificate::query()->where('code', $normalized)->first();

            if (!$certificate) {
                return $this->page('notfound', ['code' => $normalized]);
            }

            if ($certificate->isRevoked()) {
                return $this->page('revoked', [
                    'code'      => $certificate->code,
                    'typeLabel' => $certificate->typeLabel(),
                    'issuedAt'  => $this->day($certificate->issued_at),
                    'revokedAt' => $this->day($certificate->revoked_at),
                ]);
            }

            $integrity = app(CertificateVerifier::class)->verify($certificate);

            if (!$integrity['intact']) {
                Log::warning('certificate.verify.not_intact', [
                    'certificate_id' => $certificate->id,
                    'reason'         => $integrity['reason'],
                ]);

                return $this->page('unverifiable', ['code' => $certificate->code]);
            }

            $snapshot = (array) ($certificate->snapshot ?? []);

            return $this->page('valid', [
                'code'           => $certificate->code,
                'typeLabel'      => $certificate->typeLabel(),
                'issuedAt'       => $this->day($certificate->issued_at),
                'consultationAt' => $this->day($certificate->consultation_at),
                'doctorName'     => (string) ($snapshot['doctor_name'] ?? ''),
                'doctorLicense'  => (string) ($snapshot['doctor_license'] ?? ''),
                'specialties'    => implode(', ', (array) ($snapshot['doctor_specialties'] ?? [])),
                'patientMasked'  => $this->maskName((string) ($snapshot['patient_name'] ?? '')),
                'patientIdMasked' => $this->maskIdentification((string) ($snapshot['patient_identification'] ?? '')),
                'incapacityRows' => app(\App\Services\Incapacity\IncapacityRules::class)->publicRows($snapshot),
            ]);
        } catch (\Throwable $e) {
            Log::error('certificate.verify.error', ['error' => substr($e->getMessage(), 0, 300)]);

            return $this->page('error');
        }
    }

    /** GET /verify/{signatureHash}  (ruta antigua de recetas, que no funcionaba): lleva a la página nueva. */
    public function legacy(string $signatureHash)
    {
        return redirect()->route('certificates.verify.form');
    }

    private function page(string $state, array $data = [])
    {
        return response()
            ->view('certificates.verify', array_merge(['state' => $state], $data))
            ->header('Cache-Control', 'no-store, max-age=0')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    private function day($date): string
    {
        if (!$date) {
            return '';
        }

        return $date->copy()
            ->setTimezone((string) config('certificates.display_timezone', 'America/Bogota'))
            ->format('d/m/Y');
    }

    /** "Juan Pérez Gómez" -> "Juan P*** G***" (REVISAR CON ABOGADO el nivel de detalle que se muestra). */
    private function maskName(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (!$parts) {
            return '';
        }

        $first = array_shift($parts);
        $rest = array_map(fn ($p) => mb_substr($p, 0, 1) . '***', $parts);

        return trim($first . ' ' . implode(' ', $rest));
    }

    /** "1234567890" -> "•••••••890" (REVISAR CON ABOGADO). */
    private function maskIdentification(string $id): string
    {
        $clean = preg_replace('/\s+/', '', $id) ?? '';

        if (mb_strlen($clean) < 6) {
            return '';
        }

        return str_repeat('•', mb_strlen($clean) - 3) . mb_substr($clean, -3);
    }
}
