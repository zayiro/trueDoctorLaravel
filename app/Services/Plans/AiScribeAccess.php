<?php

namespace App\Services\Plans;

use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Support\Facades\DB;

/**
 * Único lugar donde se decide si un médico puede usar el dictado con IA (grabar, transcribir y estructurar el SOAP).
 *
 * Se permite si:
 *  a) el plan PROPIO del médico lo incluye (plans.ai_scribe_enabled), o
 *  b) la cita es de una clínica (appointments.clinic_id), el médico es el médico de la cita y está APROBADO en esa
 *     clínica, y el plan de ESA clínica incluye el dictado.
 *
 * Es por cita: un médico en plan gratuito tiene dictado en las citas de su clínica de pago y en ninguna otra.
 * El formulario SOAP manual y la historia clínica NO dependen de esto: siempre están disponibles.
 */
final class AiScribeAccess
{
    public function allows(?Doctor $doctor, ?Appointment $appointment = null): bool
    {
        if (!$doctor) {
            return false;
        }

        if ((bool) ($doctor->settings?->plan?->ai_scribe_enabled)) {
            return true;
        }

        if (!$appointment || !$appointment->clinic_id || (int) $appointment->doctor_id !== (int) $doctor->id) {
            return false;
        }

        return DB::table('clinic_doctor as cd')
            ->join('clinic_settings as cs', 'cs.clinic_id', '=', 'cd.clinic_id')
            ->join('plans as p', 'p.id', '=', 'cs.plan_id')
            ->where('cd.doctor_id', $doctor->id)
            ->where('cd.clinic_id', $appointment->clinic_id)
            ->where('cd.status', 'approved')
            ->where('p.ai_scribe_enabled', 1)
            ->exists();
    }
}
