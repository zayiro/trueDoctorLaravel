<?php

declare(strict_types=1);

namespace App\Services\Availability;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Generador de turnos: la ÚNICA implementación de las reglas de disponibilidad de un día.
 *
 * Es una clase pura (sin base de datos, sin caché, sin Carbon): recibe todo lo que necesita y
 * devuelve los turnos con su estado. Así se prueba sin infraestructura, y `slotsForDay`,
 * `enabledDates` y `nextSlot` del servicio salen todos de aquí y no pueden discrepar.
 *
 * Reglas (ver docs/disponibilidad-diseno.md):
 *  R4  se procesan todos los bloques del día;
 *  R5  el turno debe terminar antes o justo al fin del bloque;
 *  R6  anticipación mínima sobre el INICIO de cada turno, cualquier día;
 *  R9  ausencias; R10 citas por solapamiento más buffer; R11 máximo de citas por día;
 *  R12 estado por turno, con este orden de prioridad:
 *      past > too_soon > blocked > booked > full > available.
 */
final class SlotGenerator
{
    /**
     * @param DateTimeImmutable $day  Cualquier instante del día a evaluar (se usa su fecha y su zona horaria).
     * @param array<int, array{start:string, end:string}> $blocks  Bloques de horario, "H:i" o "H:i:s".
     * @param array<int, array{start:DateTimeImmutable, end:DateTimeImmutable}> $booked   Citas del doctor ese día.
     * @param array<int, array{start:DateTimeImmutable, end:DateTimeImmutable}> $blocked  Ausencias del doctor ese día.
     * @param int $bufferMinutes  Tiempo de descanso antes y después de cada cita.
     * @param int $bookedCountForDay  Citas ya agendadas ese día (para el máximo por día).
     *
     * @return Slot[] Ordenados por hora de inicio, sin duplicados.
     */
    public function generate(
        DateTimeImmutable $day,
        int $doctorId,
        int $addressId,
        array $blocks,
        int $durationMinutes,
        DateTimeImmutable $now,
        int $minNoticeHours,
        array $booked = [],
        array $blocked = [],
        int $bufferMinutes = 0,
        ?int $maxAppointmentsPerDay = null,
        int $bookedCountForDay = 0,
    ): array {
        if ($durationMinutes < 1) {
            throw new InvalidArgumentException('La duración del turno debe ser de al menos 1 minuto.');
        }

        $tz = $day->getTimezone();
        $date = $day->format('Y-m-d');
        $earliestBookable = $now->modify(sprintf('+%d hours', max(0, $minNoticeHours)));
        $dayIsFull = $maxAppointmentsPerDay !== null && $bookedCountForDay >= $maxAppointmentsPerDay;

        usort($blocks, fn (array $a, array $b) => strcmp($a['start'], $b['start']));

        $slots = [];

        foreach ($blocks as $block) {
            $blockStart = new DateTimeImmutable($date . ' ' . $block['start'], $tz);
            $blockEnd = new DateTimeImmutable($date . ' ' . $block['end'], $tz);

            if ($blockEnd <= $blockStart) {
                continue;
            }

            $cursor = $blockStart;

            while (true) {
                $slotEnd = $cursor->modify(sprintf('+%d minutes', $durationMinutes));

                if ($slotEnd > $blockEnd) {
                    break; // R5: el turno no cabe
                }

                $key = $cursor->getTimestamp();

                if (!isset($slots[$key])) {
                    [$state, $reason] = $this->resolveState(
                        $cursor,
                        $slotEnd,
                        $now,
                        $earliestBookable,
                        $minNoticeHours,
                        $booked,
                        $blocked,
                        $bufferMinutes,
                        $dayIsFull,
                    );

                    $slots[$key] = new Slot($cursor, $slotEnd, $doctorId, $addressId, $state, $reason);
                }

                $cursor = $slotEnd;
            }
        }

        ksort($slots);

        return array_values($slots);
    }

    /**
     * Primer turno reservable de una lista, o null.
     *
     * @param Slot[] $slots
     */
    public static function firstAvailable(array $slots): ?Slot
    {
        foreach ($slots as $slot) {
            if ($slot->isAvailable()) {
                return $slot;
            }
        }

        return null;
    }

    /**
     * @return array{0: SlotState, 1: ?string}
     */
    private function resolveState(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        DateTimeImmutable $now,
        DateTimeImmutable $earliestBookable,
        int $minNoticeHours,
        array $booked,
        array $blocked,
        int $bufferMinutes,
        bool $dayIsFull,
    ): array {
        if ($start < $now) {
            return [SlotState::Past, 'Ya pasó'];
        }

        if ($start < $earliestBookable) {
            return [SlotState::TooSoon, sprintf('Requiere %d h de anticipación', $minNoticeHours)];
        }

        if ($this->overlapsAny($start, $end, $blocked, 0)) {
            return [SlotState::Blocked, 'No disponible'];
        }

        if ($this->overlapsAny($start, $end, $booked, $bufferMinutes)) {
            return [SlotState::Booked, 'Ocupado'];
        }

        if ($dayIsFull) {
            return [SlotState::Full, 'Sin cupo para este día'];
        }

        return [SlotState::Available, null];
    }

    /**
     * Solapamiento de intervalos [inicio, fin): inicioA < finB y finA > inicioB.
     * El buffer extiende cada intervalo ocupado hacia ambos lados.
     *
     * @param array<int, array{start:DateTimeImmutable, end:DateTimeImmutable}> $intervals
     */
    private function overlapsAny(DateTimeImmutable $start, DateTimeImmutable $end, array $intervals, int $bufferMinutes): bool
    {
        foreach ($intervals as $interval) {
            $from = $bufferMinutes > 0
                ? $interval['start']->modify(sprintf('-%d minutes', $bufferMinutes))
                : $interval['start'];
            $to = $bufferMinutes > 0
                ? $interval['end']->modify(sprintf('+%d minutes', $bufferMinutes))
                : $interval['end'];

            if ($start < $to && $end > $from) {
                return true;
            }
        }

        return false;
    }
}