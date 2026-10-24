<?php

namespace App\Services;

use App\Repositories\AvailabilityRepository;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AvailabilityService
{
    private AvailabilityRepository $repository;

    public function __construct(AvailabilityRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Obtiene el próximo turno en TODAS las sedes reservables de los doctores.
     *
     * FASE 0:
     *  - Solo cuenta sedes activas y no eliminadas (Address::bookable()).
     *  - Respeta el tenant: con $clinicId solo sedes de esa clínica; sin él, solo sedes particulares.
     */
    public function getNextAvailableTurnAnyAddress(array $doctorIds, ?int $clinicId = null): ?Carbon
    {
        if (empty($doctorIds)) {
            return null;
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

            return $earliestTurn;

        } catch (\Throwable $e) {
            // Un fallo ya no se traga en silencio: queda en el log (solo identificadores, sin datos personales).
            Log::error('AvailabilityService: error en getNextAvailableTurnAnyAddress', [
                'doctor_ids' => $doctorIds,
                'clinic_id'  => $clinicId,
                'error'      => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Obtiene el próximo turno en una sede específica.
     */
    public function getNextAvailableTurn(array $doctorIds, int $addressId, ?int $clinicId = null): ?Carbon
    {
        if (empty($doctorIds)) {
            return null;
        }

        try {
            $tz = 'America/Bogota';
            $nowLocal = Carbon::now($tz);
            $minNoticeHours = $this->repository->getMinNoticeHours($clinicId, $doctorIds);
            $startSearchingFrom = $nowLocal->copy()->addHours((int) $minNoticeHours);
            $slotDurationMinutes = max(1, (int) $this->repository->getSlotDurationMinutes($addressId));

            // Festivos de este año y del siguiente (el barrido puede cruzar el fin de año)
            $colombianHolidays = ColombiaHolidayService::getHolidays($nowLocal->year)
                + ColombiaHolidayService::getHolidays($nowLocal->year + 1);

            // BARRIDO SECUENCIAL DEL CALENDARIO (próximos 14 días)
            for ($i = 0; $i < 14; $i++) {
                $currentDate = $nowLocal->copy()->addDays($i);
                $dayOfWeek = $currentDate->dayOfWeekIso;
                $dateStr = $currentDate->toDateString();

                // Saltar festivos
                if (array_key_exists($dateStr, $colombianHolidays)) {
                    continue;
                }

                // Horarios activos del día
                $schedules = $this->repository
                    ->getSchedulesForDay($addressId, $doctorIds, $dayOfWeek)
                    ->where('is_active', true);

                if ($schedules->isEmpty()) {
                    continue;
                }

                $unavailabilities = $this->repository->getUnavailabilitiesForDate($doctorIds, $currentDate, $addressId);
                $occupiedAppointments = $this->repository->getOccupiedAppointmentsForDate($doctorIds, $addressId, $currentDate);

                // EVALUAR CADA HORARIO
                foreach ($schedules as $schedule) {
                    $doctorId = $schedule->doctor_id;

                    $startTimeStr = $schedule->start_time instanceof Carbon
                        ? $schedule->start_time->format('H:i:s')
                        : Carbon::parse($schedule->start_time)->format('H:i:s');

                    $endTimeStr = $schedule->end_time instanceof Carbon
                        ? $schedule->end_time->format('H:i:s')
                        : Carbon::parse($schedule->end_time)->format('H:i:s');

                    $blockStart = Carbon::parse($dateStr . ' ' . $startTimeStr, $tz);
                    $blockEnd = Carbon::parse($dateStr . ' ' . $endTimeStr, $tz);

                    // Caminar por slots dentro del bloque
                    while ($blockStart->lessThan($blockEnd)) {
                        $slotTime = $blockStart->copy();
                        $currentTimeStr = $slotTime->format('H:i:s');

                        // FASE 0: la anticipación mínima se aplica cualquier día, no solo hoy
                        if ($slotTime->lessThan($startSearchingFrom)) {
                            $blockStart->addMinutes($slotDurationMinutes);
                            continue;
                        }

                        // Indisponibilidad del doctor
                        if ($this->isDoctorUnavailableAtTime($unavailabilities, $doctorId, $currentTimeStr, $clinicId)) {
                            $blockStart->addMinutes($slotDurationMinutes);
                            continue;
                        }

                        // Cita ocupada
                        $isSlotOccupied = $occupiedAppointments
                            ->where('doctor_id', $doctorId)
                            ->contains(function ($app) use ($currentTimeStr) {
                                $appStart = Carbon::parse($app->start_time)->format('H:i:s');
                                $appEnd = Carbon::parse($app->end_time)->format('H:i:s');
                                return $currentTimeStr >= $appStart && $currentTimeStr < $appEnd;
                            });

                        if ($isSlotOccupied) {
                            $blockStart->addMinutes($slotDurationMinutes);
                            continue;
                        }

                        // TURNO ENCONTRADO EN ESTA SEDE
                        return $slotTime;
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

    /**
     * Validación de indisponibilidad
     */
    private function isDoctorUnavailableAtTime($unavailabilities, int $doctorId, string $currentTimeStr, ?int $clinicId = null): bool
    {
        return $unavailabilities->where('doctor_id', $doctorId)
            ->contains(function ($unavailability) use ($currentTimeStr, $clinicId) {
                if ($unavailability->address && $unavailability->address->clinic_id && $clinicId) {
                    if ((int) $unavailability->address->clinic_id !== (int) $clinicId) {
                        return false;
                    }
                }

                if (is_null($unavailability->start_time) && is_null($unavailability->end_time)) {
                    return true;
                }

                if (!is_null($unavailability->start_time) && !is_null($unavailability->end_time)) {
                    $blockStart = Carbon::parse($unavailability->start_time)->format('H:i:s');
                    $blockEnd = Carbon::parse($unavailability->end_time)->format('H:i:s');
                    return $currentTimeStr >= $blockStart && $currentTimeStr < $blockEnd;
                }

                return false;
            });
    }

    public function invalidateAvailabilityCache(?int $clinicId = null, array $doctorIds = [], ?int $addressId = null): void
    {
        $this->repository->invalidateAvailabilityCache($clinicId, $doctorIds, $addressId);
    }

    public function getRepository(): AvailabilityRepository
    {
        return $this->repository;
    }
}