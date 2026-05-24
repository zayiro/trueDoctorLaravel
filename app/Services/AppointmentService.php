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
     * Soporta flujos Web, App Móvil y API Externa, citas presenciales y virtuales corporativas.
     */
    public function getAvailableSlots($addressId, $date, $doctorId, $isVirtual = false)
    {
        $requestedDate = Carbon::parse($date);
        
        // El bucle de nuestro backend procesa los días de 0 (Domingo) a 6 (Sábado)
        $dayOfWeek = $requestedDate->dayOfWeek; 
        
        // 1. Obtener la sede con sus relaciones estructurales cruzadas de co-propiedad
        $address = Address::with(['clinic.settings', 'services' => function($q) {
            $q->where('active', true);
        }])->find($addressId);
        
        if (!$address) return [];

        // Buscamos la duración específica del servicio asignado a esta sede física o virtual
        $serviceSpecific = $address->services->first();
        $intervalMinutes = $serviceSpecific && $serviceSpecific->pivot ? (int) $serviceSpecific->pivot->duration : 20;

        // 2. Obtener horario base adaptado para consultorios institucionales y privados
        if ($isVirtual) {
            // El médico puede hacer telemedicina en su consulta privada o contratado por una clínica
            $schedule = Schedule::where('address_id', $addressId)
                ->where('doctor_id', $doctorId)
                ->where('day', $dayOfWeek)
                ->first();
                
            // Contingencia: Si no hay horario virtual local, busca su bloque técnico general
            if (!$schedule) {
                $schedule = Schedule::where('address_id', $addressId)
                    ->where('day', $dayOfWeek)
                    ->first();
            }
        } else {
            // Sede física: Se extrae el bloque de horas que el médico tiene asignado en ese consultorio
            $schedule = Schedule::where('address_id', $addressId)
                ->where('doctor_id', $doctorId)
                ->where('day', $dayOfWeek)
                ->first();
        }

        if (!$schedule || !$schedule->start_time) return [];

        // Convertimos Carbon Strings a strings planos limpios para la matriz matemática
        $startTimeStr = $schedule->start_time instanceof Carbon ? $schedule->start_time->format('H:i:s') : $schedule->start_time;
        $endTimeStr = $schedule->end_time instanceof Carbon ? $schedule->end_time->format('H:i:s') : $schedule->end_time;

        // 3. Generar la matriz de bloques de tiempo posibles
        $allSlots = $this->generateTimeSlots($startTimeStr, $endTimeStr, $intervalMinutes);

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

        // 5. 🔥 CARGA INTELIGENTE DE POLÍTICAS DE ANTICIPACIÓN (SaaS Multi-inquilino)
        // Si la sede le pertenece a una clínica, hereda sus plazos; si no, lee los de la consulta privada del doctor
        if ($address->clinic_id && $address->clinic) {
            $settings = $address->clinic->settings;
            $minNoticeHours = $settings->min_notice_hours ?? 2;
        } else {
            $doctorModel = \App\Models\Doctor::with('settings')->find($doctorId);
            $settings = $doctorModel?->settings;
            $minNoticeHours = $settings->min_notice_hours ?? 24;
        }
        
        $limiteCitaMinima = Carbon::now()->addHours($minNoticeHours);

        // 6. Filtrar disponibilidad real cruzando con el validador de traslapes y anticipación
        return collect($allSlots)->map(function ($slot) use ($doctorId, $date, $intervalMinutes, $unavailabilities, $limiteCitaMinima) {
            $startTimeString = $slot['start'];
            $slotDateTime = Carbon::parse("$date $startTimeString");

            // REGLA DE ORO DE ANTICIPACIÓN: Control estricto de horas pasadas
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
                'time'      => Carbon::parse($startTimeString)->format('H:i'),
                'available' => !$esPasado && !$estaBloqueado && !$estaOcupado
            ];
        })->filter(function($slot) {
            return $slot['available'] === true; // API Filtra solo los bloques listos para agendar
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
     * Valida si una cita específica se puede cancelar o reprogramar (Reparada y Completada)
     */
    public function checkIfCanModify($appointmentId)
    {
        $appointment = Appointment::with(['doctor.settings', 'clinic.settings'])->find($appointmentId);

        if (!$appointment) {
            return ['allowed' => false, 'message' => 'La cita solicitada no existe en la base de datos.'];
        }

        if (in_array($appointment->status, ['cancelled', 'completed'])) {
            return ['allowed' => false, 'message' => "No se puede modificar una cita con estado '{$appointment->status}'."];
        }

        // 🔥 CONTROL POLÍTICAS CORPORATIVAS EN EDICIÓN
        if ($appointment->clinic_id && $appointment->clinic) {
            $settings = $appointment->clinic->settings;
            $allowCancellation = $settings->allow_patient_cancellation ?? true;
            $noticeHours = $settings->cancellation_notice_hours ?? 2;
        } else {
            $settings = $appointment->doctor->settings;
            $allowCancellation = $settings->allow_patient_cancellation ?? true;
            $noticeHours = $settings->cancellation_notice_hours ?? 24;
        }

        if (!$allowCancellation) {
            return ['allowed' => false, 'message' => 'Las políticas vigentes no permiten la cancelación autónoma de citas. Contacta soporte.'];
        }

        $appointmentDateTime = Carbon::parse($appointment->date . ' ' . $appointment->start_time);
        if (Carbon::now()->addHours($noticeHours)->gt($appointmentDateTime)) {
            return ['allowed' => false, 'message' => "La cita solo puede modificarse con un mínimo de {$noticeHours} horas de anticipación."];
        }

        return ['allowed' => true, 'message' => 'Modificación permitida por el sistema de políticas.'];
    }
}
