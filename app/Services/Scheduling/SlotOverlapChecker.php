<?php

declare(strict_types=1);

namespace App\Services\Scheduling;

use App\Models\Appointment;
use App\Models\Schedule;
use App\Models\Unavailability;
use Illuminate\Database\Eloquent\Builder;

/**
 * ÚNICA FUENTE DE VERDAD para detectar solapamientos de horarios.
 *
 * Toda la plataforma (creación de schedules, reservas, reagendamientos,
 * cálculo de disponibilidad) debe pasar por esta clase.
 *
 * Fórmula universal de intersección de intervalos:
 *   (StartA < EndB) AND (EndA > StartB)
 */
final class SlotOverlapChecker
{
    /**
     * Detecta si un rango [newStart, newEnd) se solapa con citas existentes de un doctor.
     *
     * @param int         $doctorId
     * @param string      $date        Formato 'Y-m-d'
     * @param string      $newStart    Formato 'H:i:s' o 'H:i'
     * @param string      $newEnd      Formato 'H:i:s' o 'H:i'
     * @param int|null    $excludeAppointmentId  Para reagendamientos (no chocar consigo misma)
     * @param array       $statuses    Estados que bloquean el turno
     */
    public static function overlapsWithAppointments(
        int $doctorId,
        string $date,
        string $newStart,
        string $newEnd,
        ?int $excludeAppointmentId = null,
        array $statuses = ['pending', 'confirmed', 'completed']
    ): bool {
        return Appointment::where('doctor_id', $doctorId)
            ->where('date', $date)
            ->whereIn('status', $statuses)
            ->when($excludeAppointmentId !== null, fn ($q) => $q->where('id', '!=', $excludeAppointmentId))
            ->where('start_time', '<', $newEnd)
            ->where('end_time', '>', $newStart)
            ->exists();
    }

    /**
     * Detecta si un rango [newStart, newEnd) se solapa con otros schedules del doctor
     * en un día específico de la semana (1=Lun, 7=Dom).
     *
     * Valiza TODOS los contextos: particular + clínicas (solapamiento global del doctor).
     */
    public static function overlapsWithSchedules(
        int $doctorId,
        int $dayOfWeek,
        string $newStart,
        string $newEnd,
        ?int $excludeScheduleId = null
    ): bool {
        return Schedule::where('doctor_id', $doctorId)
            ->where('day', $dayOfWeek)
            ->where('is_active', true)
            ->when($excludeScheduleId !== null, fn ($q) => $q->where('id', '!=', $excludeScheduleId))
            ->where('start_time', '<', $newEnd)
            ->where('end_time', '>', $newStart)
            ->exists();
    }

    /**
     * Detecta si un rango [newStart, newEnd) cae dentro de un bloqueo de agenda
     * (vacaciones, congresos, bloqueos administrativos).
     */
    public static function overlapsWithUnavailabilities(
        int $doctorId,
        string $date,
        string $newStart,
        string $newEnd
    ): bool {
        return Unavailability::where('doctor_id', $doctorId)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->where(function (Builder $query) use ($newStart, $newEnd) {
                $query->where(function (Builder $q) {
                    // Bloqueo de día completo (horas nulas)
                    $q->whereNull('start_time')->whereNull('end_time');
                })->orWhere(function (Builder $q) use ($newStart, $newEnd) {
                    // Fórmula universal para bloqueos parciales
                    $q->where('start_time', '<', $newEnd)
                      ->where('end_time', '>', $newStart);
                });
            })
            ->exists();
    }

    /**
     * Verifica si un slot [slotStart, slotEnd) está ocupado por una cita existente.
     * Útil para el motor legacy que itera slots minuto a minuto.
     *
     * @param \Illuminate\Support\Collection $appointments  Colección ya cargada en memoria
     * @param string $slotStart  'H:i:s'
     * @param string $slotEnd    'H:i:s'
     */
    public static function isSlotOccupiedInCollection(
        \Illuminate\Support\Collection $appointments,
        int $doctorId,
        string $slotStart,
        string $slotEnd
    ): bool {
        return $appointments->where('doctor_id', $doctorId)->contains(
            fn ($app) => self::intervalsOverlap(
                $slotStart,
                $slotEnd,
                substr((string) $app->start_time, 0, 8),
                substr((string) $app->end_time, 0, 8)
            )
        );
    }

    /**
     * Verifica si un slot cae dentro de una indisponibilidad.
     */
    public static function isSlotUnavailableInCollection(
        \Illuminate\Support\Collection $unavailabilities,
        int $doctorId,
        string $slotStart,
        string $slotEnd
    ): bool {
        return $unavailabilities->where('doctor_id', $doctorId)->contains(
            function ($unav) use ($slotStart, $slotEnd) {
                // Bloqueo de día completo
                if (is_null($unav->start_time) && is_null($unav->end_time)) {
                    return true;
                }
                return self::intervalsOverlap(
                    $slotStart,
                    $slotEnd,
                    substr((string) $unav->start_time, 0, 8),
                    substr((string) $unav->end_time, 0, 8)
                );
            }
        );
    }

    /**
     * Fórmula universal de intersección de intervalos (núcleo matemático).
     * Dos intervalos [a, b) y [c, d) se solapan SI Y SOLO SI: a < d AND b > c
     *
     * @internal Usar solo internamente. Los métodos públicos son la API real.
     */
    public static function intervalsOverlap(
        string $startA,
        string $endA,
        string $startB,
        string $endB
    ): bool {
        return $startA < $endB && $endA > $startB;
    }
}