<?php

declare(strict_types=1);

namespace App\Services\Availability;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\Availability\CacheKeyBuilder;
use InvalidArgumentException;

/**
 * FUENTE ÚNICA DE DISPONIBILIDAD.
 *
 * La búsqueda, el calendario del perfil, la decisión de clínica, los endpoints de horarios y la
 * validación al reservar deben llamar a esta clase y no calcular por su cuenta.
 */
final class AvailabilityResolver
{
    public function __construct(
        private readonly AvailabilityLoader $loader,
        private readonly AvailabilityCalculator $calculator,
    ) {
    }

    /**
     * Turnos de un día (sin los pasados, salvo $includePast). Para los endpoints de horarios.
     *
     * @return Slot[]
     */
    public function slotsForDay(AvailabilityQuery $query, DateTimeInterface $day, bool $includePast = false): array
    {
        $dayStart = $this->toBusinessTime($day)->setTime(0, 0);
        $data = $this->loader->load($query, $dayStart, $dayStart);

        return $this->calculator->slotsForDay($data, $dayStart, $this->now(), $includePast);
    }

    /**
     * Fechas con al menos un turno reservable. Para el calendario.
     *
     * @return string[] 'Y-m-d'
     */
    public function enabledDates(AvailabilityQuery $query, DateTimeInterface $from, DateTimeInterface $to): array
    {
        $fromDay = $this->toBusinessTime($from)->setTime(0, 0);
        $toDay = $this->toBusinessTime($to)->setTime(0, 0);
        $data = $this->loader->load($query, $fromDay, $toDay);

        return $this->calculator->enabledDates($data, $fromDay, $toDay, $this->now());
    }

    /**
     * Próximo turno reservable (con su doctor y su sede). Para la búsqueda.
     */
    public function nextSlot(AvailabilityQuery $query): ?Slot
    {
        $key = CacheKeyBuilder::nextSlot($query);
        $cached = Cache::remember($key, 60, fn () => ['slot' => $this->computeNextSlot($query)]);
        return $cached['slot'];
    }

    private function computeNextSlot(AvailabilityQuery $query): ?Slot
    {
        $now = $this->now();
        $windowDays = (int) config('booking.next_slot_window_days', 30);
        $data = $this->loader->load($query, $now->setTime(0, 0), $now->setTime(0, 0)->modify("+{$windowDays} days"));

        return $this->calculator->nextSlot($data, $now, $windowDays);
    }

    /**
     * Reserva segura. Ejecuta $callback dentro de una transacción, con el turno ya validado y
     * bloqueado: crea la cita DENTRO del callback.
     *
     * Bloquea la fila del doctor (mutex): dos reservas simultáneas del mismo doctor se ejecutan
     * una después de la otra, y la segunda ve el turno ya ocupado.
     *
     * @template T
     * @param callable(Slot): T $callback
     * @return T
     *
     * @throws SlotNotAvailableException si el turno ya no se puede reservar.
     */
    public function withBookableSlot(AvailabilityQuery $query, DateTimeInterface $start, callable $callback): mixed
    {
        if (count($query->doctorIds) !== 1) {
            throw new InvalidArgumentException('Para reservar se necesita exactamente un doctor.');
        }

        return DB::transaction(function () use ($query, $start, $callback) {
            DB::table('doctors')->where('id', $query->doctorIds[0])->lockForUpdate()->first();

            $startAt = $this->toBusinessTime($start);

            foreach ($this->slotsForDay($query, $startAt, includePast: true) as $slot) {
                if ($slot->start->getTimestamp() === $startAt->getTimestamp()) {
                    if (!$slot->isAvailable()) {
                        throw SlotNotAvailableException::forSlot($slot);
                    }

                    return $callback($slot);
                }
            }

            throw SlotNotAvailableException::notOffered();
        });
    }

    /** La hora actual en la zona del negocio (respeta Carbon::setTestNow en las pruebas). */
    private function now(): CarbonImmutable
    {
        return CarbonImmutable::now(config('booking.timezone', 'America/Bogota'));
    }

    private function toBusinessTime(DateTimeInterface $date): CarbonImmutable
    {
        return CarbonImmutable::instance(DateTimeImmutable::createFromInterface($date))
            ->setTimezone(config('booking.timezone', 'America/Bogota'));
    }
}