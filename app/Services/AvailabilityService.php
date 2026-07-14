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
     * 🚀 NUEVO: Obtiene el próximo turno en TODAS las sedes del doctor
     * Sin importar dirección (virtual o presencial)
     * 
     * ✅ Busca en todas las sedes donde el doctor atiende
     * ✅ Retorna el MÁS CERCANO
     * ✅ Valida modalidades (virtual/presencial)
     * ✅ Respeta festivos y horarios
     */
    public function getNextAvailableTurnAnyAddress(array $doctorIds, ?int $clinicId = null): ?Carbon
    {
        if (empty($doctorIds)) {
            //Log::warning('AvailabilityService: doctorIds vacío');
            return null;
        }

        try {
            // 1️⃣ OBTENER TODAS LAS SEDES DONDE ESTOS DOCTORES ATIENDEN
            $addressIds = Schedule::whereIn('doctor_id', $doctorIds)
                ->where('is_active', true)
                ->distinct()
                ->pluck('address_id')
                ->toArray();

            if (empty($addressIds)) {
                /*Log::warning('AvailabilityService: No hay sedes configuradas para estos doctores', [
                    'doctors' => $doctorIds,
                ]);*/
                return null;
            }

            /*Log::info('AvailabilityService: Buscando en múltiples sedes', [
                'doctors' => $doctorIds,
                'addressCount' => count($addressIds),
                'addresses' => $addressIds,
            ]);*/

            // 2️⃣ BUSCAR EN CADA SEDE Y COMPARAR RESULTADOS
            $earliestTurn = null;

            foreach ($addressIds as $addressId) {
                $turnInThisAddress = $this->getNextAvailableTurn(
                    $doctorIds,
                    $addressId,
                    $clinicId
                );

                // Si encontramos un turno en esta sede
                if ($turnInThisAddress) {
                    // Si es el primero o es más cercano que el anterior
                    if (!$earliestTurn || $turnInThisAddress->lessThan($earliestTurn)) {
                        $earliestTurn = $turnInThisAddress;
                    }
                }
            }

            if ($earliestTurn) {
                /*Log::info('AvailabilityService: ✅ TURNO MÁS PRÓXIMO ENCONTRADO', [
                    'dateTime' => $earliestTurn->format('Y-m-d H:i:s'),
                    'dayName' => $earliestTurn->format('l'),
                ]);*/
            } else {
                /*Log::warning('AvailabilityService: No hay turnos disponibles en ninguna sede', [
                    'doctors' => $doctorIds,
                ]);*/
            }

            return $earliestTurn;

        } catch (\Exception $e) {
            /*Log::error('AvailabilityService: Error en getNextAvailableTurnAnyAddress', [
                'doctor_ids' => $doctorIds,
                'clinic_id' => $clinicId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);*/

            return null;
        }
    }

    /**
     * 🔧 ORIGINAL: Obtiene próximo turno en una sede específica
     * (Mantiene la lógica detallada con logging)
     */
    public function getNextAvailableTurn(array $doctorIds, int $addressId, ?int $clinicId = null): ?Carbon
    {
        if (empty($doctorIds)) {
            //Log::warning('AvailabilityService: doctorIds vacío');
            return null;
        }

        try {
            $tz = 'America/Bogota';
            $nowLocal = Carbon::now($tz);
            $minNoticeHours = $this->repository->getMinNoticeHours($clinicId, $doctorIds);
            $startSearchingFrom = $nowLocal->copy()->addHours((int)$minNoticeHours);
            $slotDurationMinutes = max(1, (int)$this->repository->getSlotDurationMinutes($addressId));

            /*Log::info('AvailabilityService: Iniciando búsqueda en sede específica', [
                'doctors' => $doctorIds,
                'address' => $addressId,
                'clinic' => $clinicId,
                'minNoticeHours' => $minNoticeHours,
                'nowLocal' => $nowLocal->format('Y-m-d H:i:s'),
                'startSearching' => $startSearchingFrom->format('Y-m-d H:i:s'),
                'slotDuration' => $slotDurationMinutes,
            ]);*/

            // Validar festivos una sola vez
            $colombianHolidays = \App\Services\ColombiaHolidayService::getHolidays($nowLocal->year);

            // 2. BARRIDO SECUENCIAL DEL CALENDARIO (Próximos 14 días)
            for ($i = 0; $i < 14; $i++) {
                $currentDate = $nowLocal->copy()->addDays($i);
                $dayOfWeek = $currentDate->dayOfWeekIso;
                $dateStr = $currentDate->toDateString();

                // ✅ VALIDACIÓN 1: Saltar festivos
                if (array_key_exists($dateStr, $colombianHolidays)) {
                    /*Log::info("AvailabilityService: Saltando festivo", [
                        'date' => $dateStr,
                        'holiday' => $colombianHolidays[$dateStr]
                    ]);*/
                    continue;
                }

                // ⚡ Obtener horarios
                $schedules = $this->repository->getSchedulesForDay($addressId, $doctorIds, $dayOfWeek);

                if ($schedules->isEmpty()) {
                    /*Log::info("AvailabilityService: Sin horarios en address $addressId para el día", [
                        'date' => $dateStr,
                        'dayOfWeek' => $dayOfWeek
                    ]);*/
                    continue;
                }

                /*Log::info("AvailabilityService: Horarios encontrados", [
                    'date' => $dateStr,
                    'address' => $addressId,
                    'count' => $schedules->count(),
                ]);*/

                // 3. EXTRACCIÓN DE INDISPONIBILIDADES Y CITAS
                $unavailabilities = $this->repository->getUnavailabilitiesForDate($doctorIds, $currentDate, $addressId);
                $occupiedAppointments = $this->repository->getOccupiedAppointmentsForDate($doctorIds, $addressId, $currentDate);

                // 4. EVALUAR CADA HORARIO
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

                        // ✅ Control estricto de aviso mínimo
                        if ($currentDate->isToday() && $slotTime->lessThan($startSearchingFrom)) {
                            $blockStart->addMinutes($slotDurationMinutes);
                            continue;
                        }

                        // ✅ Validar indisponibilidad del doctor
                        if ($this->isDoctorUnavailableAtTime($unavailabilities, $doctorId, $currentTimeStr, $clinicId)) {
                            $blockStart->addMinutes($slotDurationMinutes);
                            continue;
                        }

                        // ✅ Validar si hay cita ocupada
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

                        // 🔥 TURNO ENCONTRADO EN ESTA SEDE
                        return $slotTime;
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            /*Log::error('AvailabilityService: Error en getNextAvailableTurn', [
                'doctor_ids' => $doctorIds,
                'address_id' => $addressId,
                'clinic_id' => $clinicId,
                'error' => $e->getMessage(),
            ]);*/

            return null;
        }
    }

    /**
     * 🔒 Validación de indisponibilidad
     */
    private function isDoctorUnavailableAtTime($unavailabilities, int $doctorId, string $currentTimeStr, ?int $clinicId = null): bool
    {
        return $unavailabilities->where('doctor_id', $doctorId)
            ->contains(function ($unavailability) use ($currentTimeStr, $clinicId) {
                if ($unavailability->address && $unavailability->address->clinic_id && $clinicId) {
                    if ((int)$unavailability->address->clinic_id !== (int)$clinicId) {
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