<?php

declare(strict_types=1);

namespace App\Services\Availability;

/**
 * ÚNICA FUENTE DE VERDAD para la clave de caché de disponibilidad.
 *
 * Los doctorIds SIEMPRE se ordenan antes de concatenar,
 * para que [1,2] y [2,1] generen la misma clave.
 */
final class CacheKeyBuilder
{
    private const PREFIX = 'availability:';

    /**
     * Clave del próximo turno reservable.
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
}
