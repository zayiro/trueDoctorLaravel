<?php

namespace App\Services\Certificates;

use App\Models\Appointment;
use App\Models\Certificate;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\SignedDocument;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Emite y anula certificados electrónicos.
 *
 * Reglas de emisión (REVISAR CON ABOGADO):
 *  - Solo el doctor de la cita.
 *  - La cita debe estar completada.
 *  - Certificado médico: además, la historia clínica de la cita debe estar firmada (signed/amended).
 *  - El diagnóstico solo se incluye si el doctor declara que el paciente lo autorizó.
 *  - Máximo N certificados vigentes por cita y tipo.
 *
 * Al emitir: crea el registro, genera el PDF con código y QR, calcula su SHA-256, firma con HMAC,
 * guarda el PDF en el disco privado y lo registra en signed_documents. Todo en una transacción.
 * No toca Zoom, pagos ni la historia clínica.
 */
class CertificateIssuer
{
    /** Logo que va en el encabezado del PDF (ruta dentro de public/). Si no existe, el PDF sale sin logo. */
    public const LOGO = 'images/logoAuth.jpg';

    /**
     * ¿Puede este doctor emitir este tipo de certificado para esta cita?
     *
     * @return array{ok: bool, reason: ?string, message: ?string}
     */
    public function eligibility(Appointment $appointment, ?Doctor $doctor, string $type): array
    {
        $no = fn (string $reason, string $message) => ['ok' => false, 'reason' => $reason, 'message' => $message];

        if (!array_key_exists($type, (array) config('certificates.types', []))) {
            return $no('type_invalid', 'Ese tipo de certificado no existe.');
        }

        if (!$doctor || (int) $appointment->doctor_id !== (int) $doctor->id) {
            return $no('not_owner', 'Solo el doctor que atendió la cita puede emitir certificados de ella.');
        }

        if (blank($doctor->medical_license)) {
            return $no('doctor_incomplete', 'Completa tu tarjeta profesional en tu perfil para poder emitir certificados.');
        }

        $status = $appointment->status instanceof \BackedEnum ? $appointment->status->value : (string) $appointment->status;

        if ($status !== 'completed') {
            return $no('not_completed', 'La cita debe estar marcada como completada para emitir un certificado.');
        }

        if ($type === Certificate::TYPE_MEDICAL) {
            $historyStatus = $appointment->patientHistory?->status;

            if (!in_array($historyStatus, ['signed', 'amended'], true)) {
                return $no('history_not_signed', 'Para emitir un certificado médico, primero firma la historia clínica de esta cita.');
            }
        }

        if ($type === 'incapacity') {
            $extra = app(\App\Services\Incapacity\IncapacityRules::class)->eligibility($appointment, $doctor);

            if (!$extra['ok']) {
                return $no((string) $extra['reason'], (string) $extra['message']);
            }
        }

        $max = (int) config('certificates.max_active_per_appointment_and_type', 3);
        $active = Certificate::query()
            ->where('appointment_id', $appointment->getKey())
            ->where('type', $type)
            ->active()
            ->count();

        if ($active >= $max) {
            return $no('limit_reached', 'Ya emitiste el máximo de certificados vigentes de este tipo para esta cita. Si necesitas otro, anula uno primero.');
        }

        return ['ok' => true, 'reason' => null, 'message' => null];
    }

    /**
     * Emite un certificado.
     *
     * $input:
     *   declaration (bool, obligatorio)  el doctor acepta la declaración del profesional
     *   body (string)                    solo certificado médico: texto del certificado
     *   include_diagnosis (bool)         solo certificado médico
     *   patient_authorized_diagnosis     obligatorio si include_diagnosis
     *   diagnosis_text, cie10_code       si include_diagnosis
     *
     * @throws CertificateException cuando algo no cumple las reglas (mensaje listo para mostrar)
     */
    public function issue(Appointment $appointment, Doctor $doctor, string $type, array $input = [], ?string $ip = null): Certificate
    {
        if (!array_key_exists($type, (array) config('certificates.types', []))) {
            throw new CertificateException('type_invalid', 'Ese tipo de certificado no existe.');
        }

        if (empty($input['declaration'])) {
            throw new CertificateException('declaration_required', 'Para emitir el certificado debes aceptar la declaración del profesional.');
        }

        $body = null;
        $diagnosisText = null;
        $cie10 = null;
        $diagnosisAuthorized = false;

        if ($type === Certificate::TYPE_MEDICAL) {
            $body = trim((string) ($input['body'] ?? ''));

            if (mb_strlen($body) < 10) {
                throw new CertificateException('body_required', 'Escribe el contenido del certificado (mínimo 10 caracteres).');
            }

            if (mb_strlen($body) > 2000) {
                throw new CertificateException('body_too_long', 'El contenido del certificado es muy largo (máximo 2000 caracteres).');
            }

            if (!empty($input['include_diagnosis'])) {
                if (empty($input['patient_authorized_diagnosis'])) {
                    throw new CertificateException('authorization_required', 'Para incluir el diagnóstico debes confirmar que el paciente lo autorizó.');
                }

                $diagnosisText = trim((string) ($input['diagnosis_text'] ?? ''));

                if (mb_strlen($diagnosisText) < 3) {
                    throw new CertificateException('diagnosis_required', 'Escribe el diagnóstico que se incluirá en el certificado.');
                }

                if (mb_strlen($diagnosisText) > 500) {
                    throw new CertificateException('diagnosis_too_long', 'El diagnóstico es muy largo (máximo 500 caracteres).');
                }

                $cie10 = trim((string) ($input['cie10_code'] ?? ''));
                $cie10 = $cie10 === '' ? null : mb_substr($cie10, 0, 20);
                $diagnosisAuthorized = true;
            }
        }

        $disk = Storage::disk((string) config('certificates.disk', 'private'));
        $path = null;

        try {
            return DB::transaction(function () use ($appointment, $doctor, $type, $input, $body, $diagnosisText, $cie10, $diagnosisAuthorized, $ip, $disk, &$path) {
                // Bloquea la cita para que dos clics a la vez no superen el tope.
                $locked = Appointment::query()->whereKey($appointment->getKey())->lockForUpdate()->first();

                if (!$locked) {
                    throw new CertificateException('appointment_missing', 'No encontramos la cita.');
                }

                $check = $this->eligibility($locked, $doctor, $type);

                if (!$check['ok']) {
                    throw new CertificateException((string) $check['reason'], (string) $check['message']);
                }

                $patient = $locked->patient ?? Patient::find($locked->patient_id);

                if (!$patient) {
                    throw new CertificateException('patient_missing', 'No encontramos al paciente de esta cita.');
                }

                $consultationAt = $this->consultationAt($locked);

                if (!$consultationAt) {
                    throw new CertificateException('appointment_incomplete', 'La cita no tiene fecha y hora definidas.');
                }

                $incapacityData = null;

                if ($type === 'incapacity') {
                    $incapacityData = app(\App\Services\Incapacity\IncapacityRules::class)->normalize($input, $locked, $doctor, $consultationAt, $patient);
                }

                $issuedAt = now()->startOfSecond();
                $code = $this->uniqueCode();

                $snapshot = [
                    'doctor_name'            => $doctor->user?->name,
                    'doctor_license'         => $doctor->medical_license,
                    'doctor_identification'  => $doctor->identification,
                    'doctor_specialties'     => $this->specialtiesOf($doctor),
                    'patient_name'           => $patient->user?->name,
                    'patient_identification' => $patient->identification,
                    'teleconsultation'       => filled($locked->zoom_meeting_id) || filled($locked->meeting_link),
                ];

                if ($incapacityData) {
                    $snapshot = array_merge($snapshot, $incapacityData['snapshot']);
                }

                $certificate = Certificate::create([
                    'code'                    => $code,
                    'type'                    => $type,
                    'status'                  => Certificate::STATUS_ISSUED,
                    'appointment_id'          => $locked->id,
                    'doctor_id'               => $doctor->id,
                    'patient_id'              => $patient->id,
                    'patient_history_id'      => $locked->patientHistory?->id,
                    'consultation_at'         => $consultationAt,
                    'issued_at'               => $issuedAt,
                    'issued_by_ip'            => $ip,
                    'doctor_declaration_at'   => $issuedAt,
                    'body'                    => $body,
                    'diagnosis_text'          => $diagnosisText,
                    'cie10_code'              => $incapacityData['cie10'] ?? $cie10,
                    'diagnosis_authorized'    => $diagnosisAuthorized,
                    'diagnosis_authorized_at' => $diagnosisAuthorized ? $issuedAt : null,
                    'snapshot'                => $snapshot,
                ]);

                if ($incapacityData) {
                    app(\App\Services\Incapacity\IncapacityRules::class)->saveDetail($certificate, $incapacityData);
                }

                $verifyUrl = route('certificates.verify.show', ['code' => $code]);

                $pdf = $this->renderPdf(
                    $this->pdfData($certificate, $snapshot, $verifyUrl, CertificateQr::dataUri($verifyUrl), $issuedAt)
                );

                $documentHash = hash('sha256', $pdf);
                $directory = trim((string) config('certificates.directory', 'certificates'), '/') . '/' . $issuedAt->format('Y');
                $path = $directory . '/' . $certificate->id . '.pdf';

                $disk->put($path, $pdf);

                $signedType = (string) config("certificates.types.$type.signed_type");

                $signed = SignedDocument::create([
                    'signable_type'  => Certificate::class,
                    'signable_id'    => $certificate->id,
                    'doctor_id'      => $doctor->id,
                    'patient_id'     => $patient->id,
                    'type'           => $signedType,
                    'document_hash'  => $documentHash,
                    'signature_hash' => CertificateVerifier::signature(
                        $documentHash,
                        (int) $certificate->id,
                        $code,
                        (int) $doctor->id,
                        $snapshot['doctor_license'] ?? null,
                        (int) $patient->id,
                        (int) $issuedAt->timestamp,
                        $signedType
                    ),
                    'storage_path'   => $path,
                    'signed_at'      => $issuedAt,
                    'signed_by_ip'   => $ip,
                    'status'         => 'signed',
                ]);

                $certificate->forceFill(['signed_document_id' => $signed->id])->save();

                Log::info('certificate.issued', ['certificate_id' => $certificate->id, 'type' => $type]);

                return $certificate->refresh();
            });
        } catch (\Throwable $e) {
            if ($path !== null) {
                try {
                    $disk->delete($path);
                } catch (\Throwable $ignored) {
                    // El archivo huérfano no es grave; lo importante es no dejar el registro a medias.
                }
            }

            throw $e;
        }
    }

    /**
     * Anula un certificado (queda en el registro; la verificación pública lo mostrará como anulado).
     *
     * @throws CertificateException
     */
    public function revoke(Certificate $certificate, Doctor $doctor, string $reason, ?int $userId = null): Certificate
    {
        if ((int) $certificate->doctor_id !== (int) $doctor->id) {
            throw new CertificateException('not_owner', 'Solo el doctor que emitió el certificado puede anularlo.');
        }

        if ($certificate->isRevoked()) {
            throw new CertificateException('already_revoked', 'Este certificado ya estaba anulado.');
        }

        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw new CertificateException('reason_required', 'Escribe el motivo de la anulación (mínimo 5 caracteres).');
        }

        DB::transaction(function () use ($certificate, $reason, $userId) {
            $certificate->forceFill([
                'status'             => Certificate::STATUS_REVOKED,
                'revoked_at'         => now(),
                'revoked_reason'     => mb_substr($reason, 0, 255),
                'revoked_by_user_id' => $userId,
            ])->save();

            if ($certificate->signedDocument) {
                $certificate->signedDocument->forceFill(['status' => 'revoked'])->save();
            }
        });

        Log::info('certificate.revoked', ['certificate_id' => $certificate->id]);

        return $certificate->refresh();
    }

    /** Dibuja el PDF a partir de los datos ya preparados (ver pdfData). */
    public function renderPdf(array $data): string
    {
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('certificates.pdf', $data)
            ->setPaper('letter')
            ->output();
    }

    /**
     * Prepara todos los textos que lleva el PDF. La plantilla solo los imprime.
     * Público para poder probar el dibujo del PDF sin emitir nada.
     */
    public function pdfData(Certificate $certificate, array $snapshot, string $verifyUrl, ?string $qr, Carbon $issuedAt): array
    {
        $tz = (string) config('certificates.display_timezone', 'America/Bogota');
        $consultation = $certificate->consultation_at->copy()->setTimezone($tz);
        $issued = $issuedAt->copy()->setTimezone($tz);

        $identification = (string) ($snapshot['patient_identification'] ?? '');
        $replace = [
            '{patient}'    => (string) ($snapshot['patient_name'] ?? 'el paciente'),
            '{patient_id}' => $identification !== '' ? ', identificado(a) con documento de identidad No. ' . $identification : '',
            '{date}'       => $consultation->format('d/m/Y'),
            '{time}'       => $consultation->format('H:i'),
            '{modality}'   => !empty($snapshot['teleconsultation']) ? ', mediante teleconsulta' : '',
            '{url}'        => $verifyUrl,
            '{code}'       => $certificate->code,
        ];

        $texts = (array) config('certificates.texts', []);
        $t = fn (string $key) => strtr((string) ($texts[$key] ?? ''), $replace);

        $isMedical = $certificate->type === Certificate::TYPE_MEDICAL;

        return [
            'title'            => $certificate->typeLabel(),
            'code'             => $certificate->code,
            'verifyUrl'        => $verifyUrl,
            'qr'               => $qr,
            'logo'             => $this->logoDataUri(),
            'issuedAt'         => $issued->format('d/m/Y H:i'),
            'consultationDate' => $consultation->format('d/m/Y'),
            'intro'            => $certificate->type === 'incapacity' ? app(\App\Services\Incapacity\IncapacityRules::class)->intro($snapshot, $replace) : $t($isMedical ? 'medical_intro' : 'attendance_body'),
            'body'             => $isMedical ? (string) $certificate->body : null,
            'diagnosisLabel'   => ($isMedical && $certificate->diagnosis_authorized) ? $t('diagnosis_label') : null,
            'diagnosisText'    => ($isMedical && $certificate->diagnosis_authorized) ? (string) $certificate->diagnosis_text : null,
            'cie10'            => ($isMedical && $certificate->diagnosis_authorized) ? $certificate->cie10_code : null,
            'noDiagnosisNote'  => $certificate->type === 'incapacity' ? null : (($isMedical && !$certificate->diagnosis_authorized) || !$isMedical ? $t('no_diagnosis_note') : null),
            'declaration'      => $t('declaration'),
            'incapacity'      => $certificate->type === 'incapacity' ? app(\App\Services\Incapacity\IncapacityRules::class)->pdf($certificate, $snapshot) : null,
            'footerVerify'     => $t('footer_verify'),
            'footerDisclaimer' => $t('footer_disclaimer'),
            'doctor'           => [
                'name'            => (string) ($snapshot['doctor_name'] ?? ''),
                'license'         => (string) ($snapshot['doctor_license'] ?? ''),
                'identification'  => (string) ($snapshot['doctor_identification'] ?? ''),
                'specialties'     => implode(', ', (array) ($snapshot['doctor_specialties'] ?? [])),
            ],
            'patient'          => [
                'name'           => (string) ($snapshot['patient_name'] ?? ''),
                'identification' => $identification,
            ],
        ];
    }

    /** Logo como data URI (así dompdf no necesita leer archivos locales). */
    private function logoDataUri(): ?string
    {
        try {
            $file = public_path(self::LOGO);

            if (!is_file($file) || filesize($file) > 1500000) {
                return null;
            }

            $mime = str_ends_with(strtolower($file), '.png') ? 'image/png' : 'image/jpeg';

            return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($file));
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function uniqueCode(): string
    {
        for ($i = 0; $i < 10; $i++) {
            $code = Certificate::generateCode();

            if (!Certificate::where('code', $code)->exists()) {
                return $code;
            }
        }

        throw new CertificateException('code_generation_failed', 'No pudimos generar el código del certificado. Intenta de nuevo.');
    }

    private function consultationAt(Appointment $appointment): ?Carbon
    {
        try {
            $start = $appointment->starts_at;

            if ($start instanceof \DateTimeInterface) {
                return Carbon::instance($start);
            }
        } catch (\Throwable $e) {
            // Se intenta con fecha + hora más abajo.
        }

        try {
            $date = substr((string) $appointment->date, 0, 10);
            $time = (string) $appointment->start_time;

            if ($date !== '' && $time !== '') {
                return Carbon::parse($date . ' ' . $time, (string) config('app.timezone', 'America/Bogota'));
            }
        } catch (\Throwable $e) {
            // Sin fecha utilizable.
        }

        return null;
    }

    private function specialtiesOf(Doctor $doctor): array
    {
        try {
            return $doctor->specialties
                ->map(fn ($s) => $s->name ?? $s->title ?? null)
                ->filter()
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
