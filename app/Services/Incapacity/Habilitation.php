<?php

namespace App\Services\Incapacity;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Doctor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Datos de la institución (razón social, NIT y código de habilitación REPS) con los que se emite una incapacidad.
 *
 * Quién es la institución:
 *  1) la clínica de la cita (si la cita se atendió en una clínica);
 *  2) si no, los datos propios del doctor (NIT y REPS de su perfil);
 *  3) si no, la única clínica aprobada a la que pertenece el doctor.
 * Si no hay forma de saberlo, devuelve ok=false con un mensaje listo para mostrar.
 *
 * REVISAR CON ABOGADO: qué datos de habilitación debe llevar el documento en cada caso.
 */
class Habilitation
{
    public const NIT_REGEX = '/^\d{5,15}(-\d)?$/';
    public const REPS_REGEX = '/^\d{8,12}$/';

    /** «900.123.456 - 7» → «900123456-7»; vacío → null. */
    public static function cleanNit(?string $raw): ?string
    {
        $v = preg_replace('/[\s.]/', '', (string) $raw) ?? '';
        $v = preg_replace('/-+/', '-', $v) ?? '';

        return $v === '' ? null : $v;
    }

    /** Solo dígitos; vacío → null. */
    public static function cleanReps(?string $raw): ?string
    {
        $v = preg_replace('/\D/', '', (string) $raw) ?? '';

        return $v === '' ? null : $v;
    }

    /**
     * @return array{ok: bool, source: ?string, name: ?string, nit: ?string, reps: ?string, message: ?string}
     */
    public function forDoctor(Doctor $doctor, ?Appointment $appointment = null): array
    {
        $clinic = $this->clinicOfAppointment($appointment);

        if ($clinic && ($r = $this->fromClinic($clinic))['ok']) {
            return $r;
        }

        if (($own = $this->fromDoctor($doctor))['ok']) {
            return $own;
        }

        try {
            $clinics = $doctor->exists
                ? $doctor->clinics()->wherePivot('status', 'approved')->with('user')->get()
                : collect();
        } catch (\Throwable $e) {
            $clinics = collect();
        }

        if ($clinics->count() === 1 && ($r = $this->fromClinic($clinics->first()))['ok']) {
            return $r;
        }

        return $this->no('Para emitir incapacidades completa el NIT y el código REPS (habilitación) en tu perfil.');
    }

    /** @return array{ok: bool, source: ?string, name: ?string, nit: ?string, reps: ?string, message: ?string} */
    public function fromClinic(Clinic $clinic): array
    {
        $nit = trim((string) $clinic->nit);
        $reps = trim((string) $clinic->reps_code);

        if ($nit === '' || $reps === '') {
            return $this->no('La clínica aún no tiene completos su NIT y su código REPS.');
        }

        $name = trim((string) ($clinic->user?->name ?? ''));

        return ['ok' => true, 'source' => 'clinic', 'name' => $name !== '' ? $name : 'Clínica', 'nit' => $nit, 'reps' => $reps, 'message' => null];
    }

    /** @return array{ok: bool, source: ?string, name: ?string, nit: ?string, reps: ?string, message: ?string} */
    public function fromDoctor(Doctor $doctor): array
    {
        $nit = self::cleanNit((string) $doctor->habilitation_nit);
        $reps = self::cleanReps((string) $doctor->habilitation_reps);

        if ($nit === null || $reps === null || !preg_match(self::NIT_REGEX, $nit) || !preg_match(self::REPS_REGEX, $reps)) {
            return $this->no('Para emitir incapacidades completa el NIT y el código REPS (habilitación) en tu perfil.');
        }

        $name = trim((string) ($doctor->user?->name ?? ''));

        return ['ok' => true, 'source' => 'doctor', 'name' => ($name !== '' ? $name : 'Profesional') . ' (profesional independiente)', 'nit' => $nit, 'reps' => $reps, 'message' => null];
    }

    /** Clínica en la que se atendió la cita (por clinic_id o por la sede), o null. Nunca lanza errores. */
    private function clinicOfAppointment(?Appointment $appointment): ?Clinic
    {
        if (!$appointment) {
            return null;
        }

        try {
            $clinicId = null;

            if (Schema::hasColumn('appointments', 'clinic_id')) {
                $clinicId = $appointment->getAttribute('clinic_id');
            }

            if (!$clinicId && Schema::hasColumn('appointments', 'address_id') && $appointment->getAttribute('address_id')) {
                $clinicId = DB::table('addresses')->where('id', $appointment->getAttribute('address_id'))->value('clinic_id');
            }

            return $clinicId ? Clinic::with('user')->find($clinicId) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function no(string $message): array
    {
        return ['ok' => false, 'source' => null, 'name' => null, 'nit' => null, 'reps' => null, 'message' => $message];
    }
}
