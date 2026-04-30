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

    private function generateTimeSlots($start, $end, $minutes)
    {
        $slots = [];
        $startTime = Carbon::parse($start);
        $endTime = Carbon::parse($end);

        while ($startTime < $endTime) {
            $slots[] = $startTime->format('H:i');
            $startTime->addMinutes($minutes);
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
}
