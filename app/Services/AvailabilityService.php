<?php

namespace App\Services;

use App\Repositories\AvailabilityRepository;
use App\Models\Schedule;
use App\Services\Availability\AvailabilityQuery;
use App\Services\Availability\AvailabilityResolver;
use App\Services\Availability\ShadowComparator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\Scheduling\SlotOverlapChecker;

class AvailabilityService
{
    private AvailabilityRepository $repository;

    public function __construct(AvailabilityRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getNextAvailableTurnAnyAddress(array $doctorIds, ?int $clinicId = null): ?Carbon
    {
        if (empty($doctorIds)) {
            return null;
        }

        // FASE 3 (interruptor): si BOOKING_NEW_SEARCH=true se usa el motor nuevo.
        // Si falla O no encuentra turnos, se cae al motor viejo para no dejar la búsqueda sin turnos.
        if (config('booking.new_availability.search')) {
            try {
                $slot = app(AvailabilityResolver::class)->nextSlot(new AvailabilityQuery($doctorIds, $clinicId));

                // 🔒 CORRECCIÓN CRÍTICA: Si el motor nuevo encuentra un slot, lo retorna.
                // Si NO encuentra (devuelve null), NO retorna inmediatamente, sino que cae al legacy.
                if ($slot) {
                    return Carbon::instance($slot->start);
                }
                
                Log::info('AvailabilityService: motor nuevo no encontró turnos, cayendo al legacy', [
                    'doctor_ids' => $doctorIds,
                    'clinic_id'  => $clinicId,
                ]);
                
            } catch (\Throwable $e) {
                Log::error('AvailabilityService: falló el motor nuevo; se usa el anterior', [
                    'doctor_ids' => $doctorIds,
                    'clinic_id'  => $clinicId,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        try {
            // 1️⃣ SEDES RESERVABLES DONDE ESTOS DOCTORES ATIENDEN
            $addressIds = Schedule::query()
                ->whereIn('doctor_id', $doctorIds)
                ->where('is_active', true)
                ->whereHas('address', function ($query) use ($clinicId) {
                    $query->bookable();

                    if ($clinicId === null) {
                        $query->whereNull('clinic_id');
                    } else {
                        $query->where('clinic_id', $clinicId);
                    }
                })
                ->distinct()
                ->pluck('address_id')
                ->all();

            if (empty($addressIds)) {
                return null;
            }

            // 2️⃣ BUSCAR EN CADA SEDE Y QUEDARSE CON EL TURNO MÁS CERCANO
            $earliestTurn = null;

            foreach ($addressIds as $addressId) {
                $turnInThisAddress = $this->getNextAvailableTurn($doctorIds, (int) $addressId, $clinicId);

                if ($turnInThisAddress && (!$earliestTurn || $turnInThisAddress->lessThan($earliestTurn))) {
                    $earliestTurn = $turnInThisAddress;
                }
            }

            // FASE 2 (modo sombra): compara con el motor nuevo sin cambiar el resultado.
            app(ShadowComparator::class)->compareNextTurn($doctorIds, $clinicId, $earliestTurn);

            return $earliestTurn;

        } catch (\Throwable $e) {
            Log::error('AvailabilityService: error en getNextAvailableTurnAnyAddress', [
                'doctor_ids' => $doctorIds,
                'clinic_id'  => $clinicId,
                'error'      => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getNextAvailableTurn(array $doctorIds, int $addressId, ?int $clinicId = null): ?Carbon
    {
        if (empty($doctorIds)) {
            return null;
        }

        try {
            $tz = config('app.timezone', 'America/Bogota');
            $nowLocal = Carbon::now($tz);
            $minNoticeHours = $this->repository->getMinNoticeHours($clinicId, $doctorIds);
            $startSearchingFrom = $nowLocal->copy()->addHours((int) $minNoticeHours);
            $slotDurationMinutes = max(1, (int) $this->repository->getSlotDurationMinutes($addressId));

            $colombianHolidays = \App\Services\ColombiaHolidayService::getHolidays($nowLocal->year)
                + \App\Services\ColombiaHolidayService::getHolidays($nowLocal->year + 1);

            for ($i = 0; $i < 14; $i++) {
                $currentDate = $nowLocal->copy()->addDays($i);
                $dayOfWeek = $currentDate->dayOfWeekIso;
                $dateStr = $currentDate->toDateString();

                if (array_key_exists($dateStr, $colombianHolidays)) {
                    continue;
                }

                $schedules = $this->repository->getSchedulesForDay($addressId, $doctorIds, $dayOfWeek);
                if ($schedules->isEmpty()) {
                    continue;
                }

                $unavailabilities = $this->repository->getUnavailabilitiesForDate($doctorIds, $currentDate, $addressId);
                $occupiedAppointments = $this->repository->getOccupiedAppointmentsForDate($doctorIds, $addressId, $currentDate);

                foreach ($schedules as $schedule) {
                    $doctorId = $schedule->doctor_id;
                    $bufferTime = $this->repository->getBufferTimeMinutes($clinicId, $doctorId);

                    // 🔒 CORRECCIÓN CRÍTICA: Usar getRawOriginal() para obtener el valor crudo de la BD
                    $startTimeRaw = $schedule->getRawOriginal('start_time');
                    $endTimeRaw = $schedule->getRawOriginal('end_time');

                    // Extraer solo la parte de hora (HH:MM:SS) del valor crudo
                    $startTimeStr = substr($startTimeRaw, 0, 8); // '06:00:00'
                    $endTimeStr = substr($endTimeRaw, 0, 8);     // '18:00:00'

                    $blockStart = Carbon::parse($dateStr . ' ' . $startTimeStr, $tz);
                    $blockEnd = Carbon::parse($dateStr . ' ' . $endTimeStr, $tz);

                    while ($blockStart->lessThan($blockEnd)) {
                        $currentTimeStr = $blockStart->format('H:i:s');
                        $slotEndStr = $blockStart->copy()->addMinutes($slotDurationMinutes)->format('H:i:s');

                        if ($blockStart->lessThan($startSearchingFrom)) {
                            $blockStart->addMinutes($slotDurationMinutes + $bufferTime);
                            continue;
                        }

                        // 2. Validar indisponibilidad (delegado al checker centralizado)
                        $isUnavailable = SlotOverlapChecker::isSlotUnavailableInCollection(
                            unavailabilities: $unavailabilities,
                            doctorId: $doctorId,
                            slotStart: $currentTimeStr,
                            slotEnd: $slotEndStr
                        );

                        if ($isUnavailable) {
                            $blockStart->addMinutes($slotDurationMinutes + $bufferTime);
                            continue;
                        }

                        // 3. Validar citas ocupadas (delegado al checker centralizado)
                        $isSlotOccupied = SlotOverlapChecker::isSlotOccupiedInCollection(
                            appointments: $occupiedAppointments,
                            doctorId: $doctorId,
                            slotStart: $currentTimeStr,
                            slotEnd: $slotEndStr
                        );

                        if ($isSlotOccupied) {
                            $blockStart->addMinutes($slotDurationMinutes + $bufferTime);
                            continue;
                        }

                        return $blockStart->copy();
                    }
                }
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('AvailabilityService: error en getNextAvailableTurn', [
                'doctor_ids' => $doctorIds,
                'address_id' => $addressId,
                'clinic_id'  => $clinicId,
                'error'      => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function invalidateAvailabilityCache(?int $clinicId = null, array $doctorIds = [], ?int $addressId = null): void
    {
        $this->repository->invalidateAvailabilityCache($clinicId, $doctorIds, $addressId);
    }
}