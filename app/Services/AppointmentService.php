<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Schedule;
use App\Models\Unavailability;
use App\Models\Appointment;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    /**
     * Genera los slots (intervalos) disponibles para un doctor en una dirección específica.
     * Soporta flujos Web, App Móvil y API Externa, citas presenciales y virtuales corporativas.
     */
    public function getAvailableSlots($addressId, $date, $doctorId, $isVirtual = false, $serviceId = null)
    {
        $requestedDate = Carbon::parse($date);
        
        // 🔒 SINCRONIZACIÓN ISO: Garantiza que 1=Lunes y 7=Domingo en coincidencia con la DB
        $dayOfWeekIso = $requestedDate->dayOfWeekIso; 
        
        // 1. Obtener la sede con sus relaciones estructurales de co-propiedad
        $address = Address::with(['clinic.settings', 'services' => function($q) {
            $q->where('services.active', true);
        }])->find($addressId);
        
        if (!$address) return [];

        // 🔒 DETERMINACIÓN DEL INTERVALO: Busca la duración exacta del servicio seleccionado
        $intervalMinutes = $this->resolveServiceDuration($address, $serviceId);

        // 2. Obtener horario base adaptado para consultorios institucionales y privados
        $schedule = $this->resolveSchedule($addressId, $doctorId, $dayOfWeekIso, $isVirtual);

        if (!$schedule || !$schedule->start_time) return [];

        // Convertimos Carbon Strings a strings planos limpios para la matriz matemática
        $startTimeStr = $this->formatTimeToString($schedule->start_time);
        $endTimeStr = $this->formatTimeToString($schedule->end_time);

        // 3. Generar la matriz de bloques de tiempo posibles
        $allSlots = $this->generateTimeSlots($startTimeStr, $endTimeStr, $intervalMinutes);

        // 4. Obtener bloqueos (unavailabilities) activos del doctor
        $unavailabilities = $this->getUnavailabilities($doctorId, $date, $addressId, $isVirtual);

        // 5. 🔥 CARGA INTELIGENTE DE POLÍTICAS DE ANTICIPACIÓN (SaaS Multi-inquilino)
        $minNoticeHours = $this->resolveMinNoticeHours($address, $doctorId);
        $limiteCitaMinima = Carbon::now()->addHours($minNoticeHours);

        // 🔥 OPTIMIZACIÓN RENDIMIENTO: Cargar citas confirmadas para evitar consultas N+1 repetitivas
        $bookedAppointments = $this->getBookedAppointments($doctorId, $date);

        // 6. Filtrar disponibilidad real cruzando con el validador de traslapes y anticipación
        return $this->filterAvailableSlots($allSlots, $date, $unavailabilities, $limiteCitaMinima, $bookedAppointments);
    }

    /**
     * Resuelve la duración del servicio de forma genérica.
     * Busca primero el servicio específico, luego el primero disponible, finalmente usa default.
     */
    private function resolveServiceDuration(Address $address, ?int $serviceId): int
    {
        $defaultDuration = 20;

        if ($serviceId) {
            $targetService = $address->services->firstWhere('id', $serviceId);
            if ($targetService && $targetService->pivot) {
                return (int) $targetService->pivot->duration;
            }
        }

        $firstService = $address->services->first();
        if ($firstService && $firstService->pivot) {
            return (int) $firstService->pivot->duration;
        }

        return $defaultDuration;
    }

    /**
     * Resuelve el horario del doctor de forma genérica.
     * Busca primero horario específico del doctor, luego horario general de la sede (para virtual).
     */
    private function resolveSchedule(int $addressId, int $doctorId, int $dayOfWeekIso, bool $isVirtual): ?Schedule
    {
        // Buscar horario específico del doctor en la sede
        $schedule = Schedule::where('address_id', $addressId)
            ->where('doctor_id', $doctorId)
            ->where('day', $dayOfWeekIso)
            ->first();

        // Contingencia virtual institucional: Si es telemedicina y no hay horario exclusivo,
        // toma la franja técnica corporativa parametrizada para la sede
        if (!$schedule && $isVirtual) {
            $schedule = Schedule::where('address_id', $addressId)
                ->where('day', $dayOfWeekIso)
                ->first();
        }

        return $schedule;
    }

    /**
     * Convierte un valor de tiempo (Carbon o string) a formato string H:i:s.
     * Método genérico para evitar duplicación de lógica de conversión.
     */
    private function formatTimeToString($time): string
    {
        if ($time instanceof Carbon) {
            return $time->format('H:i:s');
        }
        return (string) $time;
    }

    /**
     * Convierte una fecha (Carbon o string) a formato string Y-m-d.
     * Método genérico para evitar duplicación de lógica de conversión.
     */
    private function formatDateToString($date): string
    {
        if ($date instanceof Carbon) {
            return $date->format('Y-m-d');
        }
        return (string) $date;
    }

    /**
     * Obtiene los bloqueos (unavailabilities) activos del doctor de forma genérica.
     * Consolida la lógica de filtrado por tipo de cita (virtual o presencial).
     */
    private function getUnavailabilities(int $doctorId, string $date, int $addressId, bool $isVirtual): \Illuminate\Database\Eloquent\Collection
    {
        return Unavailability::where('doctor_id', $doctorId)
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
    }

    /**
     * Obtiene las citas confirmadas/pendientes/completadas del doctor en una fecha específica.
     * Método genérico para evitar duplicación de consultas.
     */
    private function getBookedAppointments(int $doctorId, string $date): \Illuminate\Database\Eloquent\Collection
    {
        return Appointment::where('doctor_id', $doctorId)
            ->whereDate('date', $date)
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->get();
    }

    /**
     * Resuelve la política de anticipación mínima de forma genérica.
     * Busca primero en clínica, luego en doctor, finalmente usa defaults.
     */
    private function resolveMinNoticeHours(Address $address, int $doctorId): int
    {
        // Si la dirección pertenece a una clínica, usar políticas de clínica
        if ($address->clinic_id && $address->clinic) {
            $settings = $address->clinic->settings;
            return $settings->min_notice_hours ?? 2;
        }

        // Si no, usar políticas del doctor
        $doctor = Doctor::with('settings')->find($doctorId);
        $settings = $doctor?->settings;
        return $settings->min_notice_hours ?? 24;
    }

    /**
     * Resuelve la política de cancelación de forma genérica.
     * Busca primero en clínica, luego en doctor, finalmente usa defaults.
     */
    private function resolveCancellationPolicy($appointment): array
    {
        if ($appointment->clinic_id && $appointment->clinic) {
            $settings = $appointment->clinic->settings;
            return [
                'allowed' => $settings->allow_patient_cancellation ?? true,
                'notice_hours' => $settings->cancellation_notice_hours ?? 2,
            ];
        }

        $settings = $appointment->doctor->settings;
        return [
            'allowed' => $settings->allow_patient_cancellation ?? true,
            'notice_hours' => $settings->cancellation_notice_hours ?? 24,
        ];
    }

    /**
     * Filtra los slots disponibles aplicando todas las validaciones.
     * Método genérico que consolida la lógica de filtrado.
     */
    private function filterAvailableSlots(array $allSlots, string $date, $unavailabilities, Carbon $limiteCitaMinima, $bookedAppointments): array
    {
        return collect($allSlots)->map(function ($slot) use ($date, $unavailabilities, $limiteCitaMinima, $bookedAppointments) {
            $startTimeString = $slot['start'];
            $endTimeString = $slot['end'];
            $slotDateTime = Carbon::parse("$date $startTimeString");

            // REGLA DE ORO DE ANTICIPACIÓN: Control estricto de horas pasadas
            $esPasado = $slotDateTime->lt($limiteCitaMinima);

            // Condición B: ¿Está bloqueado manualmente por ausencia del doctor?
            $estaBloqueado = $this->isSlotBlocked($slotDateTime, $unavailabilities);

            // 🔥 OPTIMIZACIÓN MÁXIMA (Fin del N+1): Validar colisiones directamente en memoria cacheada
            $estaOcupado = $this->isSlotBooked($startTimeString, $endTimeString, $bookedAppointments);

            return [
                'time'      => Carbon::parse($startTimeString)->format('g:i A'),
                'available' => !$esPasado && !$estaBloqueado && !$estaOcupado
            ];
        })->filter(function($slot) {
            return $slot['available'] === true; // API Filtra solo los bloques listos para agendar
        })->values()->toArray();
    }

    /**
     * Valida si un slot específico está bloqueado por unavailability.
     * Método genérico que consolida la lógica de validación de bloqueos.
     */
    private function isSlotBlocked(Carbon $slotDateTime, $unavailabilities): bool
    {
        foreach ($unavailabilities as $block) {
            if ($this->isTimeBlocked($slotDateTime, $block)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Valida si un slot específico está ocupado por una cita confirmada.
     * Método genérico que consolida la lógica de validación de colisiones.
     */
    private function isSlotBooked(string $startTimeString, string $endTimeString, $bookedAppointments): bool
    {
        return $bookedAppointments->contains(function ($appointment) use ($startTimeString, $endTimeString) {
            $appStart = $appointment->start_time;
            $appEnd = $appointment->end_time;

            return ($appStart < $endTimeString && $appStart >= $startTimeString) ||
                   ($appEnd > $startTimeString && $appEnd <= $endTimeString) ||
                   ($appStart <= $startTimeString && $appEnd >= $endTimeString);
        });
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
     * Valida si una cita específica se puede cancelar o reprogramar (Reparada, Completada y Blindada).
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

        // 🔥 CONTROL POLÍTICAS CORPORATIVAS EN EDICIÓN (Refactorizado a método genérico)
        $policy = $this->resolveCancellationPolicy($appointment);

        if (!$policy['allowed']) {
            return ['allowed' => false, 'message' => 'Las políticas vigentes no permiten la cancelación autónoma de citas. Contacta soporte.'];
        }

        // 🔥 CORREGIDO: Formateamos el objeto Carbon 'date' a string limpio antes de concatenar la hora
        $fechaString = $this->formatDateToString($appointment->date);
        $appointmentDateTime = Carbon::parse($fechaString . ' ' . $appointment->start_time);
        
        if (Carbon::now()->addHours($policy['notice_hours'])->gt($appointmentDateTime)) {
            return ['allowed' => false, 'message' => "La cita solo puede modificarse con un mínimo de {$policy['notice_hours']} horas de anticipación."];
        }

        return ['allowed' => true, 'message' => 'Modificación permitida por el sistema de políticas.'];
    }
}
