<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Schedule;
use App\Models\Unavailability;
use App\Models\Appointment;
use Carbon\Carbon;

class AppointmentService
{
    /**
     * Genera los slots (intervalos) disponibles para un doctor en una dirección específica.
     * Soporta flujos Web y API Externa, citas presenciales y virtuales.
     */
    public function getAvailableSlots($addressId, $date, $isVirtual = false)
    {
        $requestedDate = Carbon::parse($date);
        $dayOfWeek = $requestedDate->dayOfWeekIso; // 1 (Lunes) a 7 (Domingo) - Alineado con tu migración
        
        // 1. Obtener la dirección, el doctor y sus configuraciones de forma segura
        $address = Address::with(['services', 'doctor.settings'])->find($addressId);
        if (!$address) return [];
        $doctorId = $address->doctor_id;

        // Extraer la duración configurada en la tabla intermedia (Pivot)
        $serviceSpecific = $address->services->first();
        $intervalMinutes = $serviceSpecific && $serviceSpecific->pivot ? (int) $serviceSpecific->pivot->duration : 20;

        // 2. Obtener horario base (Arreglado el bug virtual)
        if ($isVirtual) {
            $schedule = Schedule::join('addresses', 'schedules.address_id', '=', 'addresses.id')
                ->where('addresses.doctor_id', $doctorId)
                ->where('schedules.day', $dayOfWeek)
                ->selectRaw('MIN(start_time) as start_time, MAX(end_time) as end_time')
                ->first();
        } else {
            $schedule = Schedule::where('address_id', $addressId)
                ->where('day', $dayOfWeek)
                ->first();
        }

        if (!$schedule || !$schedule->start_time) return [];

        // 3. Crear todos los bloques de tiempo posibles
        $allSlots = $this->generateTimeSlots($schedule->start_time, $schedule->end_time, $intervalMinutes);

        // 4. Obtener bloqueos (unavailabilities) activos del doctor
        $unavailabilities = Unavailability::where('doctor_id', $doctorId)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->where(function($q) use ($addressId, $isVirtual) {
                if ($isVirtual) {
                    $q->whereNull('address_id');
                } else {
                    $q->whereNull('address_id')->orWhere('address_id', $addressId);
                }
            })
            ->get();

        // 5. Cargar las horas de aviso mínimo de la tabla doctor_settings (por defecto 24 horas)
        $address = Address::with(['services', 'doctor.settings'])->find($addressId);
        $minNoticeHours = $address->doctor->settings->min_notice_hours ?? 24;
        
        // Calcular el límite exacto sumando la anticipación requerida a la hora de este instante
        $limiteCitaMinima = Carbon::now()->addHours($minNoticeHours);

        // 6. Filtrar disponibilidad real cruzando con el validador de traslapes y anticipación
        return collect($allSlots)->map(function ($slot) use ($doctorId, $date, $intervalMinutes, $unavailabilities, $limiteCitaMinima) {
            $startTimeString = $slot['start'];
            $slotDateTime = Carbon::parse("$date $startTimeString");

            // 👇 REGLA DE ORO DE ANTICIPACIÓN: El bloque de la cita no puede violar las horas de aviso mínimo configuradas.
            // Esto bloquea horas pasadas de hoy y también horas del día de mañana si no cumplen el mínimo de anticipación.
            $esPasado = $slotDateTime->lt($limiteCitaMinima);

            // Condición B: ¿Está bloqueado manualmente por ausencia del doctor?
            $estaBloqueado = false;
            foreach ($unavailabilities as $block) {
                if ($this->isTimeBlocked($slotDateTime, $block)) {
                    $estaBloqueado = true;
                    break;
                }
            }

            // Condición C: ¿Choca con alguna cita agendada en la base de datos?
            $estaOcupado = !$this->isAvailable($doctorId, $date, $startTimeString, $intervalMinutes);

            return [
                'time' => Carbon::parse($startTimeString)->format('H:i'),
                'available' => !$esPasado && !$estaBloqueado && !$estaOcupado
            ];
        })->values()->toArray();
    }

    /**
     * Genera la matriz de bloques de tiempo en base a la duración del servicio
     */
    private function generateTimeSlots($startTime, $endTime, $serviceDuration)
    {
        $slots = [];
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        while ($start->copy()->addMinutes($serviceDuration) <= $end) {
            $slots[] = [
                'start' => $start->format('H:i:s'),
                'end'   => $start->copy()->addMinutes($serviceDuration)->format('H:i:s'),
            ];
            $start->addMinutes($serviceDuration);
        }
        return $slots;
    }

    /**
     * Valida si un bloque específico cae dentro de una ausencia configurada
     */
    private function isTimeBlocked($slotDateTime, $block)
    {
        if (!$block->start_time && !$block->end_time) return true;

        $startBlock = Carbon::parse($block->start_date . ' ' . ($block->start_time ?? '00:00:00'));
        $endBlock = Carbon::parse($block->end_date . ' ' . ($block->end_time ?? '23:59:59'));

        return $slotDateTime->between($startBlock, $endBlock);
    }

    /**
     * Cerebro de validación de traslapes (Lógica matemática original de colisiones)
     */
    public function isAvailable($doctorId, $date, $startTime, $durationMinutes)
    {
        $start = Carbon::parse($startTime);
        $end = $start->copy()->addMinutes($durationMinutes);

        return !Appointment::where('doctor_id', $doctorId)
            ->whereDate('date', $date)
            ->whereNotIn('status', ['cancelled'])
            ->where(function ($query) use ($start, $end) {
                $query->where(function ($q) use ($start, $end) {
                    $q->where('start_time', '<', $end->format('H:i:s'))
                      ->where('start_time', '>=', $start->format('H:i:s'));
                })
                ->orWhere(function ($q) use ($start, $end) {
                    $q->where('end_time', '>', $start->format('H:i:s'))
                      ->where('end_time', '<=', $end->format('H:i:s'));
                })
                ->orWhere(function ($q) use ($start, $end) {
                    $q->where('start_time', '<=', $start->format('H:i:s'))
                      ->where('end_time', '>=', $end->format('H:i:s'));
                });
            })
            ->exists();
    }

        /**
     * Valida si una cita específica se puede cancelar o reprogramar
     * basándose en las políticas de aviso del doctor.
     *
     * @param int $appointmentId
     * @return array ['allowed' => bool, 'message' => string]
     */
    public function checkIfCanModify($appointmentId)
    {
        // 1. Buscar la cita junto con la relación de configuraciones de su doctor
        $appointment = Appointment::with('doctor.settings')->find($appointmentId);

        if (!$appointment) {
            return [
                'allowed' => false,
                'message' => 'The requested appointment does not exist.'
            ];
        }

        // 2. Verificar si el estado actual permite alguna modificación
        if (in_array($appointment->status, ['cancelled', 'completed'])) {
            return [
                'allowed' => false,
                'message' => "Cannot modify an appointment that is already marked as '{$appointment->status}'."
            ];
        }

        // 3. Extraer las políticas de cancelación desde doctor settings (respaldo de 24 horas por defecto)
        $settings = $appointment->doctor->settings;
        $allowPatientCancellation = $settings->allow_patient_cancellation ?? true;
        $cancellationNoticeHours = $settings->cancellation_notice_hours ?? 24;

        // Si el doctor desactivó las cancelaciones autónomas en su dashboard
        if (!$allowPatientCancellation) {
            return [
                'allowed' => false,
                'message' => 'The specialist does not allow autonomous cancellations. Please contact the office directly.'
            ];
        }

        // 4. Cómputo matemático del tiempo restante
        $now = Carbon::now();
        $appointmentStartDateTime = Carbon::parse($appointment->date . ' ' . $appointment->start_time);

        // Si la cita ya comenzó o pertenece al pasado
        if ($appointmentStartDateTime->isPast()) {
            return [
                'allowed' => false,
                'message' => 'Cannot modify an appointment that has already expired or belongs to the past.'
            ];
        }

        // Calcular la diferencia neta en horas entre el momento actual y el inicio de la cita
        $remainingHours = $now->diffInHours($appointmentStartDateTime, false);

        // Validar si cumple con el mínimo de horas requerido por el doctor
        if ($remainingHours < $cancellationNoticeHours) {
            return [
                'allowed' => false,
                'message' => "Cancellations or changes must be made at least {$cancellationNoticeHours} hours in advance. Current remaining time: {$remainingHours} hours."
            ];
        }

        // Si todas las comprobaciones de seguridad pasan con éxito
        return [
            'allowed' => true,
            'message' => 'The appointment can be modified successfully.'
        ];
    }
}
