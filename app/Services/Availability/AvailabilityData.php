<?php

declare(strict_types=1);

namespace App\Services\Availability;

/**
 * Todo lo que el calculador necesita, como arreglos simples (sin Eloquent ni base de datos).
 * Lo arma AvailabilityLoader; en las pruebas se arma a mano.
 */
final class AvailabilityData
{
    /**
     * @param array<int, array{doctor_id:int, address_id:int, day:int, start:string, end:string}> $schedules
     *        `day` en ISO: 1 = lunes … 7 = domingo.
     * @param array<int, array{doctor_id:int, address_id:?int, date:string, start:?string, end:?string}> $absences
     *        Ya expandidas por fecha. start/end nulos = día completo. address_id nulo = todas las sedes.
     * @param array<int, array{doctor_id:int, date:string, start:string, end:string}> $appointments
     *        Citas que ocupan al doctor (confirmadas, completadas y pendientes que aún retienen el turno).
     * @param array<string, array<string, int|null>> $settings
     *        Clave "doctorId:addressId" => min_notice_hours, max_advance_days, buffer_minutes, max_per_day.
     * @param array<int, int> $durations  addressId => minutos.
     * @param array<string, bool> $holidays 'Y-m-d' => true.
     */
    public function __construct(
        public readonly array $schedules = [],
        public readonly array $absences = [],
        public readonly array $appointments = [],
        public readonly array $settings = [],
        public readonly array $durations = [],
        public readonly array $holidays = [],
    ) {
    }

    public static function empty(): self
    {
        return new self();
    }
}