<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;

/**
 * Reglas de cancelación/modificación de citas.
 * La disponibilidad de turnos NO vive aquí: la calcula únicamente App\Services\Availability\AvailabilityResolver.
 */
class AppointmentService
{
    /**
     * Resuelve la política de cancelación de forma genérica.
     * Busca primero en clínica, luego en doctor, finalmente usa defaults.
     */
    private function resolveCancellationPolicy($appointment): array
    {
        return app(\App\Services\Appointments\AppointmentCancellationService::class)->policyFor($appointment);
    }

    /**
     * Convierte una fecha (Carbon o string) a formato string Y-m-d.
     * Método genérico para evitar duplicación de lógica de conversión.
     */
    private function formatDateToString($date): string
    {
        if ($date instanceof Carbon) {
            return $date->format('Y-m-d');
        }
        return (string) $date;
    }

    /**
     * Valida si una cita específica se puede cancelar o reprogramar (Reparada, Completada y Blindada).
     */
    public function checkIfCanModify($appointmentId)
    {
        $appointment = Appointment::with(['doctor.settings', 'clinic.settings'])->find($appointmentId);

        if (!$appointment) {
            return ['allowed' => false, 'message' => 'La cita solicitada no existe en la base de datos.'];
        }

        $statusValue = $appointment->status instanceof \BackedEnum ? $appointment->status->value : (string) $appointment->status;

        if (in_array($statusValue, ['cancelled', 'completed'], true)) {
            return ['allowed' => false, 'message' => "No se puede modificar una cita con estado '{$statusValue}'."];
        }

        // 🔥 CONTROL POLÍTICAS CORPORATIVAS EN EDICIÓN (Refactorizado a método genérico)
        $policy = $this->resolveCancellationPolicy($appointment);

        if (!$policy['allowed']) {
            return ['allowed' => false, 'message' => 'Las políticas vigentes no permiten la cancelación autónoma de citas. Contacta soporte.'];
        }

        // 🔥 CORREGIDO: Formateamos el objeto Carbon 'date' a string limpio antes de concatenar la hora
        $fechaString = $this->formatDateToString($appointment->date);
        $appointmentDateTime = Carbon::parse($fechaString . ' ' . $appointment->start_time);
        
        if (Carbon::now()->addHours($policy['notice_hours'])->gt($appointmentDateTime)) {
            return ['allowed' => false, 'message' => "La cita solo puede modificarse con un mínimo de {$policy['notice_hours']} horas de anticipación."];
        }

        return ['allowed' => true, 'message' => 'Modificación permitida por el sistema de políticas.'];
    }
}
