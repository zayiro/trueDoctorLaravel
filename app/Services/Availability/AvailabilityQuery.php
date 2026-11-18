<?php

declare(strict_types=1);

namespace App\Services\Availability;

/**
 * Qué se está consultando: doctores, tenant (clínica), sede y servicio.
 */
final class AvailabilityQuery
{
    /**
     * @param int[]    $doctorIds
     * @param int|null $clinicId             null = solo sedes particulares; con valor = solo sedes de esa clínica.
     * @param int|null $addressId            null = todas las sedes reservables.
     * @param int|null $serviceId            null = se usa la menor duración del doctor en cada sede.
     * @param int|null $excludeAppointmentId cita que se está reprogramando (no debe bloquearse a sí misma).
     * @param bool     $staffBooking         true = agenda hecha por el doctor/clínica: sin anticipación mínima ni
     *                                       límite de días hacia adelante. Choques, bloqueos, festivos y cupo diario SÍ aplican.
     */
    public function __construct(
        public readonly array $doctorIds,
        public readonly ?int $clinicId = null,
        public readonly ?int $addressId = null,
        public readonly ?int $serviceId = null,
        public readonly ?int $excludeAppointmentId = null,
        public readonly bool $staffBooking = false,
    ) {
    }
}