<?php
namespace App\Services;

use App\Models\Schedule;
use App\Models\Appointment;
use App\Models\Unavailability;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AppointmentService
{
    /**
     * Genera los slots (intervalos) disponibles para un doctor en una dirección específica
     */
    public function getAvailableSlots($doctorId, $addressId, $date, $intervalMinutes = 30)
    {
        $requestedDate = Carbon::parse($date);
        $dayOfWeek = $requestedDate->dayOfWeekIso; // 1 (Lunes) a 7 (Domingo)

        // 1. Obtener horario base para ese día y dirección
        $schedule = Schedule::where('address_id', $addressId)
            ->where('day', $dayOfWeek)
            ->first();

        if (!$schedule) return [];

        // 2. Crear todos los slots posibles (ej: 08:00, 08:30, 09:00...)
        $allSlots = $this->generateTimeSlots(
            $schedule->start_time, 
            $schedule->end_time, 
            $intervalMinutes
        );

        // 3. Obtener citas ya ocupadas
        $bookedSlots = Appointment::where('address_id', $addressId)
            ->whereDate('appointment_date', $date)
            ->where('status', '!=', 'cancelled')
            ->pluck('appointment_date')
            ->map(fn($date) => Carbon::parse($date)->format('H:i'))
            ->toArray();

        // 4. Obtener bloqueos (unavailabilities)
        $unavailabilities = Unavailability::where('doctor_id', $doctorId)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->get();

        // 5. Filtrar disponibilidad real
        return collect($allSlots)->filter(function ($slotTime) use ($bookedSlots, $unavailabilities, $date) {
            $slotDateTime = Carbon::parse("$date $slotTime");

            // ¿Ya está ocupado por una cita?
            if (in_array($slotTime, $bookedSlots)) return false;

            // ¿Está bloqueado manualmente?
            foreach ($unavailabilities as $block) {
                if ($this->isTimeBlocked($slotDateTime, $block)) return false;
            }

            return true;
        })->values();
    }

    private function generateTimeSlots($startTime, $endTime, $serviceDuration)
    {
        $slots = [];
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        // El turno solo se crea si cabe antes de la hora de cierre
        while ($start->copy()->addMinutes($serviceDuration) <= $end) {
            $slots[] = [
                'start' => $start->format('H:i'),
                'end'   => $start->copy()->addMinutes($serviceDuration)->format('H:i'),
            ];
            // El siguiente turno empieza donde termina el anterior
            $start->addMinutes($serviceDuration);
        }
        return $slots;
    }

    private function isTimeBlocked($slotDateTime, $block)
    {
        // Si no hay horas definidas, bloquea todo el día
        if (!$block->start_time && !$block->end_time) return true;

        $startBlock = Carbon::parse($block->start_date . ' ' . ($block->start_time ?? '00:00:00'));
        $endBlock = Carbon::parse($block->end_date . ' ' . ($block->end_time ?? '23:59:59'));

        return $slotDateTime->between($startBlock, $endBlock);
    }

    /*
    /*Este servicio será el "cerebro" que valide los choques de horario.
    */
    public function isAvailable($doctorId, $date, $startTime, $durationMinutes)
    {
        $start = Carbon::parse($startTime);
        $end = $start->copy()->addMinutes($durationMinutes);

        // Buscamos citas que se traslapen
        return !Appointment::where('doctor_id', $doctorId)
            ->whereDate('date', $date)
            ->where(function ($query) use ($start, $end) {
                $query->where(function ($q) use ($start, $end) {
                    // Caso 1: Una cita empieza mientras esta aún no termina
                    $q->where('start_time', '<', $end->format('H:i:s'))
                      ->where('start_time', '>=', $start->format('H:i:s'));
                })
                ->orWhere(function ($q) use ($start, $end) {
                    // Caso 2: Una cita termina después de que esta empezó
                    $q->where('end_time', '>', $start->format('H:i:s'))
                      ->where('end_time', '<=', $end->format('H:i:s'));
                })
                ->orWhere(function ($q) use ($start, $end) {
                    // Caso 3: Una cita existente envuelve completamente a la nueva
                    $q->where('start_time', '<=', $start->format('H:i:s'))
                      ->where('end_time', '>=', $end->format('H:i:s'));
                });
            })
            ->exists();
    }
}
