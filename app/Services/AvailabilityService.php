<?php

namespace App\Services;

use App\Repositories\AvailabilityRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AvailabilityService
{
    /**
     * Instancia del repositorio de disponibilidad
     */
    private AvailabilityRepository $repository;

    /**
     * Constructor con inyección de dependencias
     */
    public function __construct(AvailabilityRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Calcula con precisión atómica el próximo turno real disponible.
     * 
     * ⚡ OPTIMIZADO: Utiliza caché del repositorio para consultas repetidas
     * 🆕 MEJORADO: Consolida bloques horarios de múltiples contextos (particular + clínicas)
     * 🔒 BLINDADO: Intercepta inasistencias con validación de contexto de propiedad
     * 
     * @param array $doctorIds Matriz de IDs de los doctores a evaluar (Staff o Particular).
     * @param int $addressId ID de la sede física o virtual.
     * @param int|null $clinicId ID de la clínica si aplica, para extraer sus reglas logísticas.
     * @return Carbon|null Instancia de tiempo con el turno más cercano o NULL si no hay cupo.
     */
    public function getNextAvailableTurn__(array $doctorIds, int $addressId, ?int $clinicId = null): ?Carbon
    {
        if (empty($doctorIds)) {
            return null;
        }

        try {
            // 1. DETERMINAR EL MARGEN DE AVISO MÍNIMO (COLCHÓN LOGÍSTICO)
            // ⚡ Utiliza caché del repositorio
            $minNoticeHours = $this->repository->getMinNoticeHours($clinicId, $doctorIds);

            // 🔒 BLINDAJE TOTAL DE TIEMPO: Forzamos la zona horaria real de la consulta (America/Bogota)
            $tz = 'America/Bogota';
            
            // Creamos las instancias interpretando la hora actual de Colombia de forma estricta
            $nowLocal = Carbon::now($tz);
            $startSearchingFrom = Carbon::now($tz)->addHours((int)$minNoticeHours);
            
            // ⚡ Extraer la duración del servicio en la sede con caché
            $slotDurationMinutes = max(1, (int) $this->repository->getSlotDurationMinutes($addressId));

            // 2. BARRIDO SECUENCIAL DEL CALENDARIO (Próximos 14 días)
            for ($i = 0; $i < 14; $i++) {
                $currentDate = $nowLocal->copy()->addDays($i);
                $dayOfWeek = $currentDate->dayOfWeekIso; // 1 = Lunes, 7 = Domingo

                // ⚡ Obtener horarios con caché
                // 🆕 Ahora carga clinic_id para identificar el contexto de propiedad
                $schedules = $this->repository->getSchedulesForDay($addressId, $doctorIds, $dayOfWeek);

                if ($schedules->isEmpty()) {
                    continue;
                }

                // 3. EXTRACCIÓN DE INDISPONIBILIDADES Y CITAS ACTIVAS
                // ⚡ Ambas consultas utilizan caché del repositorio
                $unavailabilities = $this->repository->getUnavailabilitiesForDate($doctorIds, $currentDate, $addressId);
                $occupiedAppointments = $this->repository->getOccupiedAppointmentsForDate($doctorIds, $addressId, $currentDate);

                //valida los festivos
                $colombianHolidays = \App\Services\ColombiaHolidayService::getHolidays($currentDate->year);
                if (array_key_exists($currentDate->toDateString(), $colombianHolidays)) {
                    continue;
                }

                // 4. EVALUACIÓN Y FRAGMENTACIÓN DE BLOQUES EN TIEMPO REAL
                foreach ($schedules as $schedule) {
                    // Purificamos la hora de la base de datos asegurándonos de que sea un String limpio (HH:MM:SS)
                    $startTimeStr = $schedule->start_time instanceof Carbon 
                        ? $schedule->start_time->format('H:i:s') 
                        : Carbon::parse($schedule->start_time)->format('H:i:s');

                    $endTimeStr = $schedule->end_time instanceof Carbon 
                        ? $schedule->end_time->format('H:i:s') 
                        : Carbon::parse($schedule->end_time)->format('H:i:s');

                    // 🔒 PARSEO SEGURO PARA PRODUCCIÓN: Combinamos fecha y hora, y fijamos la zona horaria de Colombia
                    $blockStart = Carbon::parse($currentDate->toDateString() . ' ' . $startTimeStr, $tz);
                    $blockEnd   = Carbon::parse($currentDate->toDateString() . ' ' . $endTimeStr, $tz);

                    // Caminamos dentro del bloque continuo en fracciones de tiempo del servicio (ej: 20 minutos)
                    while ($blockStart->lessThan($blockEnd)) {
                        $currentTimeStr = $blockStart->format('H:i:s');

                        // Control estricto de aviso mínimo para el día de hoy
                        if ($currentDate->isToday() && $blockStart->lessThan($startSearchingFrom)) {
                            $blockStart->addMinutes($slotDurationMinutes);
                            continue;
                        }

                        // 🔒 VALIDACIÓN MEJORADA 1: Descartar si el doctor tiene un bloqueo (con validación de contexto)
                        if ($this->isDoctorUnavailableAtTime($unavailabilities, $schedule->doctor_id, $currentTimeStr, $clinicId)) {
                            $blockStart->addMinutes($slotDurationMinutes);
                            continue;
                        }

                        // Validación 2: Descartar si el espacio coincide con una cita ocupada
                        $isSlotOccupied = $occupiedAppointments->where('doctor_id', $schedule->doctor_id)
                            ->contains(function ($app) use ($currentTimeStr) {
                                return $currentTimeStr >= Carbon::parse($app->start_time)->format('H:i:s') && 
                                       $currentTimeStr < Carbon::parse($app->end_time)->format('H:i:s');
                            });

                        if ($isSlotOccupied) {
                            $blockStart->addMinutes($slotDurationMinutes);
                            continue;
                        }

                        // 🔥 CRITERIO DE ÉXITO: Retornamos el primer espacio de tiempo libre de esta tarde
                        return $blockStart;
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('AvailabilityService: Error al calcular próximo turno disponible', [
                'doctor_ids' => $doctorIds,
                'address_id' => $addressId,
                'clinic_id' => $clinicId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    /**
     * Calcula con precisión atómica el próximo turno real disponible.
     * 
     * ⚡ OPTIMIZADO: Utiliza caché del repositorio para consultas repetidas
     * 🆕 MEJORADO: Consolida bloques horarios de múltiples contextos (particular + clínicas)
     * 🔒 BLINDADO: Intercepta inasistencias con validación de contexto de propiedad
     * ✅ FIX 1: Timezone aplicado desde el parseo inicial (no con setTimezone post-parse)
     * ✅ FIX 2: slotDurationMinutes protegido contra 0 para evitar bucle infinito
     * ✅ FIX 3: Festivos colombianos validados dentro del barrido de días
     * 
     * @param array $doctorIds Matriz de IDs de los doctores a evaluar (Staff o Particular).
     * @param int $addressId ID de la sede física o virtual.
     * @param int|null $clinicId ID de la clínica si aplica, para extraer sus reglas logísticas.
     * @return Carbon|null Instancia de tiempo con el turno más cercano o NULL si no hay cupo.
     */
    public function getNextAvailableTurn(array $doctorIds, int $addressId, ?int $clinicId = null): ?Carbon
    {
        if (empty($doctorIds)) {
            return null;
        }

        try {
            // 1. DETERMINAR EL MARGEN DE AVISO MÍNIMO (COLCHÓN LOGÍSTICO)
            $minNoticeHours = $this->repository->getMinNoticeHours($clinicId, $doctorIds);

            // 🔒 BLINDAJE TOTAL DE TIEMPO: Forzamos la zona horaria real de Colombia
            $tz = 'America/Bogota';
            $nowLocal = Carbon::now($tz);
            $startSearchingFrom = Carbon::now($tz)->addHours((int) $minNoticeHours);

            // ✅ FIX 2: Protección contra bucle infinito si la duración es 0 o null
            $slotDurationMinutes = max(1, (int) $this->repository->getSlotDurationMinutes($addressId));

            // 2. BARRIDO SECUENCIAL DEL CALENDARIO (Próximos 14 días)
            for ($i = 0; $i < 14; $i++) {
                $currentDate = $nowLocal->copy()->addDays($i);
                $dayOfWeek   = $currentDate->dayOfWeekIso; // 1=Lunes, 7=Domingo

                // ✅ FIX 3: Saltar festivos colombianos
                $colombianHolidays = \App\Services\ColombiaHolidayService::getHolidays($currentDate->year);
                if (array_key_exists($currentDate->toDateString(), $colombianHolidays)) {
                    continue;
                }

                // ⚡ Obtener horarios con caché
                $schedules = $this->repository->getSchedulesForDay($addressId, $doctorIds, $dayOfWeek);

                if ($schedules->isEmpty()) {
                    continue;
                }

                // 3. EXTRACCIÓN DE INDISPONIBILIDADES Y CITAS ACTIVAS
                $unavailabilities      = $this->repository->getUnavailabilitiesForDate($doctorIds, $currentDate, $addressId);
                $occupiedAppointments  = $this->repository->getOccupiedAppointmentsForDate($doctorIds, $addressId, $currentDate);

                // 4. EVALUACIÓN Y FRAGMENTACIÓN DE BLOQUES EN TIEMPO REAL
                foreach ($schedules as $schedule) {
                    $startTimeStr = $schedule->start_time instanceof Carbon
                        ? $schedule->start_time->format('H:i:s')
                        : Carbon::parse($schedule->start_time)->format('H:i:s');

                    $endTimeStr = $schedule->end_time instanceof Carbon
                        ? $schedule->end_time->format('H:i:s')
                        : Carbon::parse($schedule->end_time)->format('H:i:s');

                    // ✅ FIX 1: Parseo con timezone desde el inicio, no con setTimezone() posterior
                    $blockStart = Carbon::parse($currentDate->toDateString() . ' ' . $startTimeStr, $tz);
                    $blockEnd   = Carbon::parse($currentDate->toDateString() . ' ' . $endTimeStr, $tz);

                    // Caminamos dentro del bloque en fracciones del servicio (ej: 20 min)
                    while ($blockStart->lessThan($blockEnd)) {
                        $currentTimeStr = $blockStart->format('H:i:s');

                        // Control estricto de aviso mínimo para el día de hoy
                        if ($currentDate->isToday() && $blockStart->lessThan($startSearchingFrom)) {
                            $blockStart->addMinutes($slotDurationMinutes);
                            continue;
                        }

                        // 🔒 Descartar si el doctor tiene un bloqueo (con validación de contexto)
                        if ($this->isDoctorUnavailableAtTime($unavailabilities, $schedule->doctor_id, $currentTimeStr, $clinicId)) {
                            $blockStart->addMinutes($slotDurationMinutes);
                            continue;
                        }

                        // Descartar si el espacio coincide con una cita ocupada
                        $isSlotOccupied = $occupiedAppointments
                            ->where('doctor_id', $schedule->doctor_id)
                            ->contains(function ($app) use ($currentTimeStr) {
                                return $currentTimeStr >= Carbon::parse($app->start_time)->format('H:i:s')
                                    && $currentTimeStr <  Carbon::parse($app->end_time)->format('H:i:s');
                            });

                        if ($isSlotOccupied) {
                            $blockStart->addMinutes($slotDurationMinutes);
                            continue;
                        }

                        // 🔥 CRITERIO DE ÉXITO: Primer espacio libre encontrado
                        return $blockStart;
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('AvailabilityService: Error al calcular próximo turno disponible', [
                'doctor_ids' => $doctorIds,
                'address_id' => $addressId,
                'clinic_id'  => $clinicId,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    /**
     * 🔒 VALIDACIÓN MEJORADA: Detecta si un doctor está indisponible en un horario específico.
     * 
     * Maneja correctamente:
     * - Bloqueos de día completo (start_time y end_time son NULL)
     * - Bloqueos horarios específicos
     * - Validación de contexto de propiedad (particular vs clínica)
     * 
     * @param \Illuminate\Database\Eloquent\Collection $unavailabilities
     * @param int $doctorId
     * @param string $currentTimeStr Formato HH:MM:SS
     * @param int|null $clinicId
     * @return bool
     */
    private function isDoctorUnavailableAtTime($unavailabilities, int $doctorId, string $currentTimeStr, ?int $clinicId = null): bool
    {
        return $unavailabilities->where('doctor_id', $doctorId)
            ->contains(function ($unavailability) use ($currentTimeStr, $clinicId) {
                // 🔒 VALIDACIÓN DE CONTEXTO: Si es una inasistencia de clínica, solo aplica si estamos en ese contexto
                if ($unavailability->address && $unavailability->address->clinic_id && $clinicId) {
                    if ((int)$unavailability->address->clinic_id !== (int)$clinicId) {
                        return false; // Esta inasistencia no aplica a este contexto
                    }
                }

                // Caso 1: Bloqueo de día completo (sin horas específicas)
                if (is_null($unavailability->start_time) && is_null($unavailability->end_time)) {
                    return true; // Bloquea TODO el día
                }

                // Caso 2: Bloqueo horario específico
                if (!is_null($unavailability->start_time) && !is_null($unavailability->end_time)) {
                    $blockStart = Carbon::parse($unavailability->start_time)->format('H:i:s');
                    $blockEnd = Carbon::parse($unavailability->end_time)->format('H:i:s');
                    
                    return $currentTimeStr >= $blockStart && $currentTimeStr < $blockEnd;
                }

                return false;
            });
    }

    /**
     * 🆕 Obtiene los bloques horarios de un doctor agrupados por contexto de propiedad.
     * Útil para renderizar la agenda consolidada mostrando "Particular" vs "Clínica X".
     * 
     * @param int $doctorId
     * @param int $dayOfWeek (1-7)
     * @return array Estructura: ['particular' => [...], 'clinics' => ['clinic_id' => [...]]]
     */
    public function getSchedulesByContext(int $doctorId, int $dayOfWeek): array
    {
        try {
            $schedules = \App\Models\Schedule::where('doctor_id', $doctorId)
                ->where('day', $dayOfWeek)
                ->where('is_active', true)
                ->with(['clinic', 'address'])
                ->orderBy('start_time')
                ->get();

            $grouped = [
                'particular' => [],
                'clinics' => []
            ];

            foreach ($schedules as $schedule) {
                if (is_null($schedule->clinic_id)) {
                    // Bloque particular
                    $grouped['particular'][] = [
                        'id' => $schedule->id,
                        'start_time' => $schedule->start_time->format('H:i'),
                        'end_time' => $schedule->end_time->format('H:i'),
                        'address_name' => $schedule->address->name ?? 'Consultorio',
                        'context' => 'Consulta Particular'
                    ];
                } else {
                    // Bloque de clínica
                    $clinicId = $schedule->clinic_id;
                    if (!isset($grouped['clinics'][$clinicId])) {
                        $grouped['clinics'][$clinicId] = [
                            'clinic_name' => $schedule->clinic->name ?? "Clínica #{$clinicId}",
                            'blocks' => []
                        ];
                    }

                    $grouped['clinics'][$clinicId]['blocks'][] = [
                        'id' => $schedule->id,
                        'start_time' => $schedule->start_time->format('H:i'),
                        'end_time' => $schedule->end_time->format('H:i'),
                        'address_name' => $schedule->address->name ?? 'Sede',
                        'context' => "Staff - {$schedule->clinic->name}"
                    ];
                }
            }

            return $grouped;
        } catch (\Exception $e) {
            Log::error('AvailabilityService: Error al agrupar horarios por contexto', [
                'doctor_id' => $doctorId,
                'day_of_week' => $dayOfWeek,
                'error' => $e->getMessage()
            ]);

            return ['particular' => [], 'clinics' => []];
        }
    }

    /**
     * Invalida el caché de disponibilidad cuando se confirma una nueva cita
     * Debe llamarse desde el controlador o evento de confirmación de cita
     * 
     * @param int|null $clinicId ID de la clínica
     * @param array $doctorIds IDs de los doctores
     * @param int|null $addressId ID de la sede
     * @return void
     */
    public function invalidateAvailabilityCache(?int $clinicId = null, array $doctorIds = [], ?int $addressId = null): void
    {
        $this->repository->invalidateAvailabilityCache($clinicId, $doctorIds, $addressId);
    }

    /**
     * Obtiene el repositorio para acceso directo si es necesario
     * 
     * @return AvailabilityRepository
     */
    public function getRepository(): AvailabilityRepository
    {
        return $this->repository;
    }
}
