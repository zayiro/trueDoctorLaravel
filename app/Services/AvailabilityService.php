<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Appointment;
use App\Models\Unavailability;
use App\Models\ClinicSetting;
use App\Models\DoctorSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AvailabilityService
{
    /**
     * Calcula con precisión atómica el próximo turno real disponible.
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

        // 1. DETERMINAR EL MARGEN DE AVISO MÍNIMO (COLCHÓN LOGÍSTICO)
        $minNoticeHours = 2;
        if (!is_null($clinicId)) {
            $minNoticeHours = DB::table('clinic_settings')->where('clinic_id', $clinicId)->value('min_notice_hours') ?? 2;
        } else {
            $minNoticeHours = DB::table('doctor_settings')->whereIn('doctor_id', $doctorIds)->value('min_notice_hours') ?? 2;
        }

        // 🔒 BLINDAJE TOTAL DE TIEMPO: Forzamos la zona horaria real de la consulta (America/Bogota)
        $tz = 'America/Bogota';
        
        // Creamos las instancias interpretando la hora actual de Colombia de forma estricta
        $nowLocal = Carbon::now($tz);
        $startSearchingFrom = Carbon::now($tz)->addHours((int)$minNoticeHours);
        
        // Extraer la duración del servicio en la sede o aplicar un respaldo base de 20 minutos (según tu DB)
        $slotDurationMinutes = DB::table('address_service')
            ->where('address_id', $addressId)
            ->value('duration') ?? 20;

        // 2. BARRIDO SECUENCIAL DEL CALENDARIO (Próximos 14 días)
        for ($i = 0; $i < 14; $i++) {
            $currentDate = $nowLocal->copy()->addDays($i);
            $dayOfWeek = $currentDate->dayOfWeekIso; // 1 = Lunes, 7 = Domingo

            $schedules = Schedule::where('address_id', $addressId)
                ->whereIn('doctor_id', $doctorIds)
                ->where('day', $dayOfWeek)
                ->get();

            if ($schedules->isEmpty()) {
                continue;
            }

            // 3. EXTRACCIÓN DE INDISPONIBILIDADES Y CITAS ACTIVAS
            $unavailabilities = Unavailability::whereIn('doctor_id', $doctorIds)
                ->where(function ($q) use ($currentDate, $addressId) {
                    $q->where('start_date', '<=', $currentDate->toDateString())
                      ->where('end_date', '>=', $currentDate->toDateString())
                      ->where(function ($sub) use ($addressId) {
                          $sub->whereNull('address_id')->orWhere('address_id', $addressId);
                      });
                })->get();

            $occupiedAppointments = Appointment::whereIn('doctor_id', $doctorIds)
                ->where('address_id', $addressId)
                ->where('date', $currentDate->toDateString())
                ->whereIn('status', ['pending', 'confirmed'])
                ->get(['start_time', 'end_time', 'doctor_id']);

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
                $blockStart = Carbon::parse($currentDate->toDateString() . ' ' . $startTimeStr)->setTimezone($tz);
                $blockEnd = Carbon::parse($currentDate->toDateString() . ' ' . $endTimeStr)->setTimezone($tz);

                // Caminamos dentro del bloque continuo en fracciones de tiempo del servicio (ej: 20 minutos)
                while ($blockStart->lessThan($blockEnd)) {
                    $currentTimeStr = $blockStart->format('H:i:s');

                    // Control estricto de aviso mínimo para el día de hoy
                    if ($currentDate->isToday() && $blockStart->lessThan($startSearchingFrom)) {
                        $blockStart->addMinutes($slotDurationMinutes);
                        continue;
                    }

                    // Validación 1: Descartar si el doctor tiene un bloqueo
                    $isDoctorUnavailable = $unavailabilities->where('doctor_id', $schedule->doctor_id)
                        ->contains(function ($u) use ($currentTimeStr) {
                            if (is_null($u->start_time)) return true;
                            return $currentTimeStr >= Carbon::parse($u->start_time)->format('H:i:s') && 
                                   $currentTimeStr < Carbon::parse($u->end_time)->format('H:i:s');
                        });

                    if ($isDoctorUnavailable) {
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
    }
}
