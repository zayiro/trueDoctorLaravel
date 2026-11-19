<?php

declare(strict_types=1);

namespace App\Services\Scheduling;

use App\Models\Schedule;

/**
 * Detección de solapamientos al CONFIGURAR la agenda (horarios y ausencias).
 *
 * No calcula disponibilidad de turnos: eso lo hace únicamente
 * App\Services\Availability\AvailabilityResolver.
 *
 * Fórmula universal de intersección de intervalos:
 *   (StartA < EndB) AND (EndA > StartB)
 */
final class SlotOverlapChecker
{
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