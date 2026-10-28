<?php

declare(strict_types=1);

namespace App\Services\Availability;

/**
 * ÚNICA FUENTE DE VERDAD para generar claves de caché de disponibilidad.
 *
 * Garantiza que el motor legacy (AvailabilityRepository) y el motor nuevo
 * (AvailabilityResolver) usen EXACTAMENTE las mismas claves.
 *
 * Regla de oro: los doctorIds SIEMPRE se ordenan antes de concatenar,
 * para que [1,2] y [2,1] generen la misma clave.
 */
final class CacheKeyBuilder
{
    private const PREFIX = 'availability:';

    /**
     * Clave del motor nuevo: próximo turno reservable.
     * TTL corto (60s) porque la búsqueda calcula muchos resultados por página.
     */
    public static function nextSlot(AvailabilityQuery $query): string
    {
        $ids = $query->doctorIds;
        sort($ids);

        return sprintf(
            '%snext:%s:%s:%s:%s:%s',
            self::PREFIX,
            $query->clinicId ?? 'particular',
            implode('-', $ids),
            $query->addressId ?? 'all',
            $query->serviceId ?? 'any',
            $query->excludeAppointmentId ?? 'none',
        );
    }

    /**
     * Clave legacy: schedules de un día específico.
     */
    public static function schedules(int $addressId, array $doctorIds, int $dayOfWeek): string
    {
        $sorted = $doctorIds;
        sort($sorted);
        return self::PREFIX . 'schedules:' . $addressId . ':' . implode(',', $sorted) . ':' . $dayOfWeek;
    }

    /**
     * Clave legacy: citas ocupadas en una fecha.
     */
    public static function appointments(array $doctorIds, int $addressId, string $date): string
    {
        $sorted = $doctorIds;
        sort($sorted);
        return self::PREFIX . 'appointments:' . implode(',', $sorted) . ':' . $addressId . ':' . $date;
    }

    /**
     * Clave legacy: indisponibilidades (bloqueos) en una fecha.
     */
    public static function unavailabilities(array $doctorIds, string $date, ?int $addressId = null): string
    {
        $sorted = $doctorIds;
        sort($sorted);
        return self::PREFIX . 'unavailabilities:' . implode(',', $sorted) . ':' . $date . ':' . ($addressId ?? 'all');
    }

    /**
     * Clave legacy: duración del slot por sede (y opcionalmente por servicio).
     * 🔒 CORRECCIÓN: ahora acepta serviceId para evitar el bug de "toma el primero que MySQL devuelva".
     */
    public static function slotDuration(int $addressId, ?int $serviceId = null): string
    {
        return self::PREFIX . 'slot_duration:' . $addressId . ':' . ($serviceId ?? 'any');
    }

    /**
     * Clave legacy: min_notice_hours (aviso mínimo para reservar).
     */
    public static function minNotice(?int $clinicId, array $doctorIds): string
    {
        if ($clinicId !== null) {
            return self::PREFIX . 'min_notice:clinic:' . $clinicId;
        }
        $sorted = $doctorIds;
        sort($sorted);
        return self::PREFIX . 'min_notice:doctors:' . implode(',', $sorted);
    }

    /**
     * Clave legacy: buffer_time_minutes (descanso entre pacientes).
     */
    public static function buffer(?int $clinicId, ?int $doctorId): string
    {
        if ($clinicId !== null) {
            return self::PREFIX . 'buffer:clinic:' . $clinicId;
        }
        return self::PREFIX . 'buffer:doctor:' . ($doctorId ?? 'none');
    }
}