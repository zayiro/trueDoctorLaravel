<?php

namespace App\Services\Incapacity;

use App\Models\Appointment;
use App\Models\Certificate;
use App\Models\Cie10Code;
use App\Models\Doctor;
use App\Models\IncapacityDetail;
use App\Models\Patient;
use App\Services\Certificates\CertificateException;
use Illuminate\Support\Carbon;

/**
 * Reglas de la incapacidad médica (REVISAR CON ABOGADO; los valores están en config/incapacity.php).
 * La usa CertificateIssuer cuando el tipo es «incapacity». No toca Zoom, pagos ni la historia clínica.
 */
class IncapacityRules
{
    public const TYPE = 'incapacity';

    /**
     * ¿Cumple el doctor y la cita lo necesario para emitir una incapacidad?
     * (Las reglas comunes —doctor de la cita, cita completada, tope por cita— las revisa CertificateIssuer.)
     *
     * @return array{ok: bool, reason: ?string, message: ?string}
     */
    public function eligibility(Appointment $appointment, ?Doctor $doctor): array
    {
        $no = fn (string $reason, string $message) => ['ok' => false, 'reason' => $reason, 'message' => $message];

        if (!$doctor || !$doctor->hasValidRethus()) {
            return $no('rethus', 'Para emitir incapacidades tu registro debe estar verificado en ReTHUS y vigente.');
        }

        $habilitation = app(Habilitation::class)->forDoctor($doctor, $appointment);

        if (!$habilitation['ok']) {
            return $no('habilitation', (string) $habilitation['message']);
        }

        $historyStatus = $appointment->patientHistory?->status;

        if (!in_array($historyStatus, ['signed', 'amended'], true)) {
            return $no('history_not_signed', 'Para emitir una incapacidad, primero firma la historia clínica de esta cita.');
        }

        return ['ok' => true, 'reason' => null, 'message' => null];
    }

    /**
     * Valida lo que escribió el doctor y arma todo lo que lleva la incapacidad.
     *
     * $input['incapacity']: origin, start_date (Y-m-d), days, is_extension, previous_certificate_id, retroactive_reason, cie10_code
     *
     * @return array{detail: array, cie10: string, snapshot: array}
     *
     * @throws CertificateException mensaje listo para mostrar
     */
    public function normalize(array $input, Appointment $appointment, Doctor $doctor, Carbon $consultationAt, Patient $patient): array
    {
        $in = (array) ($input['incapacity'] ?? []);

        if ($in === []) {
            throw new CertificateException('incapacity_data_required', 'Para emitir una incapacidad usa el formulario de incapacidades.');
        }

        $tz = (string) config('certificates.display_timezone', 'America/Bogota');
        $origins = (array) config('incapacity.origins', []);
        $max = (int) config('incapacity.max_days', 30);

        $origin = (string) ($in['origin'] ?? '');
        if (!array_key_exists($origin, $origins)) {
            throw new CertificateException('origin_invalid', 'Elige el origen de la incapacidad (enfermedad general o laboral).');
        }

        $days = (int) ($in['days'] ?? 0);
        if ($days < 1 || $days > $max) {
            throw new CertificateException('days_invalid', "La duración debe estar entre 1 y {$max} días. Para más tiempo emite una prórroga.");
        }

        try {
            $start = Carbon::createFromFormat('!Y-m-d', (string) ($in['start_date'] ?? ''), $tz);
            if ($start === false || $start->format('Y-m-d') !== (string) $in['start_date']) {
                throw new \InvalidArgumentException('fecha');
            }
        } catch (\Throwable $e) {
            throw new CertificateException('start_invalid', 'Escribe la fecha de inicio de la incapacidad.');
        }

        $consultDay = $consultationAt->copy()->setTimezone($tz)->startOfDay();
        $offset = (int) round(($start->getTimestamp() - $consultDay->getTimestamp()) / 86400); // negativo = antes de la consulta

        $retroMax = (int) config('incapacity.retroactive_max_days', 30);
        $futureMax = (int) config('incapacity.future_start_max_days', 2);

        if ($offset > $futureMax) {
            throw new CertificateException('start_future', "La incapacidad no puede empezar más de {$futureMax} días después de la consulta.");
        }

        $retro = $offset < 0;
        $reason = null;

        if ($retro) {
            if (-$offset > $retroMax) {
                throw new CertificateException('start_too_old', "La incapacidad no puede empezar más de {$retroMax} días antes de la consulta.");
            }

            $reason = trim((string) ($in['retroactive_reason'] ?? ''));
            $min = (int) config('incapacity.retroactive_reason_min', 20);

            if (mb_strlen($reason) < $min) {
                throw new CertificateException('retro_reason_required', "Una incapacidad retroactiva necesita una justificación (mínimo {$min} caracteres).");
            }

            $reason = mb_substr($reason, 0, 500);
        }

        $end = $start->copy()->addDays($days - 1);

        [$cieCode, $cieDescription] = $this->parseCie10((string) ($in['cie10_code'] ?? ''));

        // Prórroga: debe seguir, sin huecos, a una incapacidad vigente del mismo paciente y del mismo doctor.
        $isExtension = !empty($in['is_extension']);
        $previous = null;

        if ($isExtension) {
            $previous = Certificate::query()
                ->where('id', (int) ($in['previous_certificate_id'] ?? 0))
                ->where('type', self::TYPE)
                ->active()
                ->where('patient_id', $patient->id)
                ->where('doctor_id', $doctor->id)
                ->first();

            $prevDetail = $previous ? IncapacityDetail::query()->where('certificate_id', $previous->id)->first() : null;

            if (!$previous || !$prevDetail) {
                throw new CertificateException('previous_invalid', 'Elige la incapacidad anterior que se está prorrogando.');
            }

            if ($prevDetail->end_date->copy()->addDay()->format('Y-m-d') !== $start->format('Y-m-d')) {
                throw new CertificateException('extension_dates', 'Una prórroga debe empezar el día siguiente al fin de la incapacidad anterior (' . $prevDetail->end_date->format('d/m/Y') . ').');
            }
        }

        // No se superponen dos incapacidades vigentes del mismo paciente.
        $overlap = IncapacityDetail::query()
            ->whereHas('certificate', fn ($q) => $q->where('patient_id', $patient->id)->where('type', self::TYPE)->active())
            ->where('start_date', '<=', $end->format('Y-m-d'))
            ->where('end_date', '>=', $start->format('Y-m-d'))
            ->exists();

        if ($overlap) {
            throw new CertificateException('overlap', 'Este paciente ya tiene una incapacidad vigente que cubre esas fechas. Si es una continuación, márcala como prórroga.');
        }

        $institution = app(Habilitation::class)->forDoctor($doctor, $appointment);

        if (!$institution['ok']) {
            throw new CertificateException('habilitation', (string) $institution['message']);
        }

        $tele = filled($appointment->zoom_meeting_id) || filled($appointment->meeting_link);
        $modality = $tele ? 'telemedicine' : 'in_person';

        $patient->loadMissing('insurance');

        return [
            'detail' => [
                'origin'                  => $origin,
                'service_group'           => (string) config('incapacity.service_group', 'Consulta externa'),
                'modality'                => $modality,
                'start_date'              => $start->format('Y-m-d'),
                'end_date'                => $end->format('Y-m-d'),
                'days'                    => $days,
                'is_extension'            => $isExtension,
                'previous_certificate_id' => $previous?->id,
                'is_retroactive'          => $retro,
                'retroactive_reason'      => $reason,
            ],
            'cie10'    => $cieCode,
            'snapshot' => [
                'institution'         => ['name' => $institution['name'], 'nit' => $institution['nit'], 'reps' => $institution['reps'], 'source' => $institution['source']],
                'patient_eps'         => $patient->insurance?->name,
                'patient_affiliation' => $patient->affiliation_type,
                'incapacity'          => [
                    'origin'      => $origin,
                    'service'     => (string) config('incapacity.service_group', 'Consulta externa'),
                    'modality'    => $modality,
                    'start'       => $start->format('Y-m-d'),
                    'end'         => $end->format('Y-m-d'),
                    'days'        => $days,
                    'extension'   => $isExtension,
                    'previous'    => $previous?->code,
                    'retroactive' => $retro,
                    'reason'      => $reason,
                    'cie10'       => $cieCode,
                ],
            ],
        ];
    }

    /** Crea la fila de datos propios de la incapacidad (dentro de la transacción de emisión). */
    public function saveDetail(Certificate $certificate, array $data): IncapacityDetail
    {
        return IncapacityDetail::create(array_merge($data['detail'], ['certificate_id' => $certificate->id]));
    }

    /** Texto de apertura del PDF (con los mismos marcadores que los certificados más los de la incapacidad). */
    public function intro(array $snapshot, array $replace): string
    {
        $i = (array) ($snapshot['incapacity'] ?? []);
        $days = (int) ($i['days'] ?? 0);

        $replace += [
            '{days_words}' => self::daysPhrase($days),
            '{days}'       => (string) $days,
            '{day_word}'   => $days === 1 ? 'día' : 'días',
            '{start}'      => $this->date($i['start'] ?? null),
            '{end}'        => $this->date($i['end'] ?? null),
            '{origin}'     => (string) config('incapacity.origins.' . ($i['origin'] ?? ''), ''),
        ];

        return strtr((string) config('incapacity.texts.intro', ''), $replace);
    }

    /**
     * Filas y notas del bloque de la incapacidad en el PDF.
     *
     * @return array{rows: array<int, array{0: string, 1: string}>, notes: array<int, string>}
     */
    public function pdf(Certificate $certificate, array $snapshot): array
    {
        $i = (array) ($snapshot['incapacity'] ?? []);
        $inst = (array) ($snapshot['institution'] ?? []);
        $days = (int) ($i['days'] ?? 0);
        $eps = trim((string) ($snapshot['patient_eps'] ?? ''));
        $aff = trim((string) ($snapshot['patient_affiliation'] ?? ''));

        $rows = [
            ['Institución', (string) ($inst['name'] ?? '')],
            ['NIT', (string) ($inst['nit'] ?? '')],
            ['Código de habilitación (REPS)', (string) ($inst['reps'] ?? '')],
            ['Paciente', (string) ($snapshot['patient_name'] ?? '')],
            ['Documento del paciente', (string) ($snapshot['patient_identification'] ?? '')],
        ];

        if ($eps !== '' || $aff !== '') {
            $rows[] = ['EPS / afiliación', trim($eps . ($eps !== '' && $aff !== '' ? ' · ' : '') . $aff)];
        }

        $rows = array_merge($rows, [
            ['Origen', (string) config('incapacity.origins.' . ($i['origin'] ?? ''), '')],
            ['Grupo de servicio', (string) ($i['service'] ?? '')],
            ['Modalidad', (string) config('incapacity.modalities.' . ($i['modality'] ?? ''), '')],
            ['Desde', $this->date($i['start'] ?? null)],
            ['Hasta', $this->date($i['end'] ?? null)],
            ['Duración', self::daysPhrase($days) . ' (' . $days . ') ' . ($days === 1 ? 'día' : 'días') . ' calendario'],
            ['Diagnóstico (CIE-10)', (string) ($i['cie10'] ?? '')],
        ]);

        $rows = array_values(array_filter($rows, fn ($r) => $r[1] !== ''));

        $notes = [(string) config('incapacity.texts.diagnosis_note', '')];

        if (!empty($i['extension'])) {
            $notes[] = strtr((string) config('incapacity.texts.extension_note', ''), ['{previous}' => (string) ($i['previous'] ?? '')]);
        }

        if (!empty($i['retroactive'])) {
            $notes[] = strtr((string) config('incapacity.texts.retroactive_note', ''), ['{reason}' => (string) ($i['reason'] ?? '')]);
        }

        return ['rows' => $rows, 'notes' => array_values(array_filter($notes, fn ($n) => $n !== ''))];
    }

    /**
     * Filas para la verificación pública (sin diagnóstico ni código CIE-10).
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public function publicRows(array $snapshot): array
    {
        $i = (array) ($snapshot['incapacity'] ?? []);

        if ($i === []) {
            return [];
        }

        $days = (int) ($i['days'] ?? 0);

        return array_values(array_filter([
            ['Incapacidad desde', $this->date($i['start'] ?? null)],
            ['Incapacidad hasta', $this->date($i['end'] ?? null)],
            ['Duración', $days . ' ' . ($days === 1 ? 'día' : 'días') . ' calendario'],
            ['Origen', (string) config('incapacity.origins.' . ($i['origin'] ?? ''), '')],
        ], fn ($r) => $r[1] !== ''));
    }

    /**
     * Separa el código CIE-10 de lo que escribe o elige el doctor («J00 - Rinofaringitis aguda» → ['J00', 'Rinofaringitis aguda']).
     * Valida solo el FORMATO (el catálogo de OpenDoctor es parcial). Si el código está en el catálogo, toma su descripción.
     *
     * @return array{0: string, 1: ?string}
     *
     * @throws CertificateException
     */
    public function parseCie10(string $raw): array
    {
        $raw = trim($raw);

        if (!preg_match('/^([A-Za-z]\d{2}(?:\.?[A-Za-z0-9]{1,2})?)(?![A-Za-z0-9])\s*[-–—:]?\s*(.*)$/su', $raw, $m)) {
            throw new CertificateException('cie10_required', 'Escribe o elige el diagnóstico con su código CIE-10 (por ejemplo J00).');
        }

        $code = \App\Services\Cie10\Cie10Format::normalize($m[1]) ?? strtoupper($m[1]);
        $description = trim($m[2]) !== '' ? mb_substr(trim($m[2]), 0, 200) : null;

        $catalogOk = null;
        try {
            $row = Cie10Code::query()->where('code', $code)->where('is_active', true)->first();
            $catalogOk = $row !== null;
            $description = $row?->description ?: $description;
        } catch (\Throwable $e) {
            // El catálogo es solo ayuda: sin él se sigue con lo escrito.
        }

        if ($catalogOk === false && config('incapacity.cie10_require_catalog', false) && Cie10Code::query()->count() > 100) {
            throw new CertificateException('cie10_unknown', 'El código CIE-10 «' . $code . '» no existe en el catálogo. Búscalo y elígelo de la lista.');
        }

        return [$code, $description];
    }

    /** Para «… días»: 1 → «UN», 21 → «VEINTIÚN», 30 → «TREINTA» (en mayúsculas, con la forma corta de «uno»). */
    public static function daysPhrase(int $n): string
    {
        $w = self::daysInWords($n);

        if ($n % 10 === 1 && $n % 100 !== 11) {
            $w = preg_replace('/veintiuno$/u', 'veintiún', $w);
            $w = preg_replace('/uno$/u', 'un', (string) $w);
        }

        return mb_strtoupper((string) $w);
    }

    /** 1 → «uno», 21 → «veintiuno», 30 → «treinta», 45 → «cuarenta y cinco». Soporta de 0 a 999. */
    public static function daysInWords(int $n): string
    {
        $n = max(0, min(999, $n));
        $small = ['cero', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez', 'once', 'doce', 'trece', 'catorce', 'quince',
            'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve', 'veinte', 'veintiuno', 'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco',
            'veintiséis', 'veintisiete', 'veintiocho', 'veintinueve'];
        $tens = [3 => 'treinta', 4 => 'cuarenta', 5 => 'cincuenta', 6 => 'sesenta', 7 => 'setenta', 8 => 'ochenta', 9 => 'noventa'];
        $hundreds = [1 => 'ciento', 2 => 'doscientos', 3 => 'trescientos', 4 => 'cuatrocientos', 5 => 'quinientos', 6 => 'seiscientos', 7 => 'setecientos', 8 => 'ochocientos', 9 => 'novecientos'];

        if ($n === 100) {
            return 'cien';
        }

        $out = [];
        $h = intdiv($n, 100);
        $r = $n % 100;

        if ($h > 0) {
            $out[] = $hundreds[$h];
        }

        if ($r > 0 || $h === 0) {
            if ($r < 30) {
                $out[] = $small[$r];
            } else {
                $t = intdiv($r, 10);
                $u = $r % 10;
                $out[] = $tens[$t] . ($u > 0 ? ' y ' . $small[$u] : '');
            }
        }

        return implode(' ', $out);
    }

    private function date(?string $ymd): string
    {
        if (!$ymd) {
            return '';
        }

        try {
            return Carbon::parse($ymd)->format('d/m/Y');
        } catch (\Throwable $e) {
            return '';
        }
    }
}
