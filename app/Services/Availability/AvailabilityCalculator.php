<?php

declare(strict_types=1);

namespace App\Services\Availability;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Calcula disponibilidad a partir de datos ya cargados. Es pura: no toca la base de datos.
 *
 * `enabledDates()` y `nextSlot()` salen de `slotsForDay()`, así que el calendario, la búsqueda
 * y el listado de horarios NO pueden discrepar.
 *
 * El `$now` que reciben debe venir en la zona horaria del negocio.
 */
final class AvailabilityCalculator
{
    private const FALLBACKS = [
        'min_notice_hours' => 2,
        'max_advance_days' => 30,
        'buffer_minutes' => 0,
        'max_per_day' => null,
        'fallback_duration' => 20,
    ];

    /**
     * @param array<string, int|null> $defaults Valores por defecto (config/booking.php).
     */
    public function __construct(
        private readonly SlotGenerator $generator = new SlotGenerator(),
        private readonly array $defaults = [],
    ) {
    }

    /**
     * Turnos de un día, de todos los doctores y sedes, ordenados por hora.
     * Por defecto se omiten los turnos pasados (la interfaz los oculta); los demás estados se devuelven
     * para que la interfaz decida si los muestra en gris.
     *
     * @return Slot[]
     */
    public function slotsForDay(AvailabilityData $data, DateTimeImmutable $day, DateTimeImmutable $now, bool $includePast = false): array
    {
        $date = $day->format('Y-m-d');
        $isoDay = (int) $day->format('N');
        $tz = $day->getTimezone();

        $groups = [];
        foreach ($data->schedules as $schedule) {
            if ((int) $schedule['day'] === $isoDay) {
                $groups[$schedule['doctor_id'] . ':' . $schedule['address_id']][] = $schedule;
            }
        }

        $all = [];

        foreach ($groups as $rows) {
            $doctorId = (int) $rows[0]['doctor_id'];
            $addressId = (int) $rows[0]['address_id'];

            if ($this->beyondHorizon($data, $doctorId, $addressId, $date, $now)) {
                continue;
            }

            $slots = $this->generator->generate(
                day: $day,
                doctorId: $doctorId,
                addressId: $addressId,
                blocks: array_map(fn (array $r) => ['start' => $r['start'], 'end' => $r['end']], $rows),
                durationMinutes: (int) ($data->durations[$addressId] ?? $this->setting($data, $doctorId, $addressId, 'fallback_duration')),
                now: $now,
                minNoticeHours: (int) $this->setting($data, $doctorId, $addressId, 'min_notice_hours'),
                booked: $this->bookedIntervals($data, $doctorId, $date, $tz),
                blocked: $this->blockedIntervals($data, $doctorId, $addressId, $date, $tz),
                bufferMinutes: (int) $this->setting($data, $doctorId, $addressId, 'buffer_minutes'),
                maxAppointmentsPerDay: $this->setting($data, $doctorId, $addressId, 'max_per_day'),
                bookedCountForDay: $this->bookedCount($data, $doctorId, $date),
            );

            foreach ($slots as $slot) {
                if ($includePast || $slot->state !== SlotState::Past) {
                    $all[] = $slot;
                }
            }
        }

        usort($all, fn (Slot $a, Slot $b) => [$a->start->getTimestamp(), $a->doctorId, $a->addressId]
            <=> [$b->start->getTimestamp(), $b->doctorId, $b->addressId]);

        return $all;
    }

    /**
     * Fechas ('Y-m-d') con al menos un turno reservable, entre $from y $to (ambas inclusive).
     *
     * @return string[]
     */
    public function enabledDates(AvailabilityData $data, DateTimeImmutable $from, DateTimeImmutable $to, DateTimeImmutable $now): array
    {
        $dates = [];

        for ($day = $from->setTime(0, 0); $day <= $to; $day = $day->modify('+1 day')) {
            if (SlotGenerator::firstAvailable($this->slotsForDay($data, $day, $now)) !== null) {
                $dates[] = $day->format('Y-m-d');
            }
        }

        return $dates;
    }

    /**
     * Primer turno reservable desde ahora, en cualquier doctor y sede, dentro de $maxDays días.
     */
    public function nextSlot(AvailabilityData $data, DateTimeImmutable $now, int $maxDays = 30): ?Slot
    {
        $start = $now->setTime(0, 0);

        for ($i = 0; $i <= $maxDays; $i++) {
            $slot = SlotGenerator::firstAvailable($this->slotsForDay($data, $start->modify("+{$i} days"), $now));

            if ($slot !== null) {
                return $slot;
            }
        }

        return null;
    }

    private function setting(AvailabilityData $data, int $doctorId, int $addressId, string $key): mixed
    {
        $row = $data->settings[$doctorId . ':' . $addressId] ?? [];

        return $row[$key] ?? ($this->defaults[$key] ?? self::FALLBACKS[$key]);
    }

    private function beyondHorizon(AvailabilityData $data, int $doctorId, int $addressId, string $date, DateTimeImmutable $now): bool
    {
        $maxDays = (int) $this->setting($data, $doctorId, $addressId, 'max_advance_days');

        return $date > $now->modify("+{$maxDays} days")->format('Y-m-d');
    }

    /** @return array<int, array{start:DateTimeImmutable, end:DateTimeImmutable}> */
    private function bookedIntervals(AvailabilityData $data, int $doctorId, string $date, DateTimeZone $tz): array
    {
        $intervals = [];

        foreach ($data->appointments as $appointment) {
            if ((int) $appointment['doctor_id'] === $doctorId && $appointment['date'] === $date) {
                $intervals[] = [
                    'start' => new DateTimeImmutable($date . ' ' . $appointment['start'], $tz),
                    'end' => new DateTimeImmutable($date . ' ' . $appointment['end'], $tz),
                ];
            }
        }

        return $intervals;
    }

    private function bookedCount(AvailabilityData $data, int $doctorId, string $date): int
    {
        $count = 0;

        foreach ($data->appointments as $appointment) {
            if ((int) $appointment['doctor_id'] === $doctorId && $appointment['date'] === $date) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Ausencias del doctor ese día (de la sede o de todas) y, si es festivo, el día completo.
     *
     * @return array<int, array{start:DateTimeImmutable, end:DateTimeImmutable}>
     */
    private function blockedIntervals(AvailabilityData $data, int $doctorId, int $addressId, string $date, DateTimeZone $tz): array
    {
        $dayStart = new DateTimeImmutable($date . ' 00:00:00', $tz);
        $wholeDay = ['start' => $dayStart, 'end' => $dayStart->modify('+1 day')];

        if (isset($data->holidays[$date])) {
            return [$wholeDay];
        }

        $intervals = [];

        foreach ($data->absences as $absence) {
            if ((int) $absence['doctor_id'] !== $doctorId || $absence['date'] !== $date) {
                continue;
            }

            if ($absence['address_id'] !== null && (int) $absence['address_id'] !== $addressId) {
                continue;
            }

            $intervals[] = ($absence['start'] === null || $absence['end'] === null)
                ? $wholeDay
                : [
                    'start' => new DateTimeImmutable($date . ' ' . $absence['start'], $tz),
                    'end' => new DateTimeImmutable($date . ' ' . $absence['end'], $tz),
                ];
        }

        return $intervals;
    }
}