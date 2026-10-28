<?php

namespace App\Repositories;

use App\Models\Schedule;
use App\Models\Appointment;
use App\Models\Unavailability;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AvailabilityRepository
{
    private const CACHE_PREFIX = 'availability:';
    private const CACHE_TTL = 30; // 30 minutos

    public function getMinNoticeHours(?int $clinicId = null, array $doctorIds = []): int
    {
        $cacheKey = self::CACHE_PREFIX . 'min_notice:' . ($clinicId ?? 'doctors:' . implode(',', $doctorIds));
        return Cache::remember($cacheKey, self::CACHE_TTL * 60, function () use ($clinicId, $doctorIds) {
            if (!is_null($clinicId)) {
                return (int) (DB::table('clinic_settings')->where('clinic_id', $clinicId)->value('min_notice_hours') ?? 2);
            }
            return (int) (DB::table('doctor_settings')->whereIn('doctor_id', $doctorIds)->min('min_notice_hours') ?? 2);
        });
    }

    public function getBufferTimeMinutes(?int $clinicId = null, ?int $doctorId = null): int
    {
        $cacheKey = self::CACHE_PREFIX . 'buffer:' . ($clinicId ?? 'doctor:' . $doctorId);
        return Cache::remember($cacheKey, self::CACHE_TTL * 60, function () use ($clinicId, $doctorId) {
            if (!is_null($clinicId)) {
                return (int) (DB::table('clinic_settings')->where('clinic_id', $clinicId)->value('buffer_time_minutes') ?? 0);
            }
            if (!is_null($doctorId)) {
                return (int) (DB::table('doctor_settings')->where('doctor_id', $doctorId)->value('buffer_time_minutes') ?? 0);
            }
            return 0;
        });
    }

    /**
     * 🔒 CORRECCIÓN CRÍTICA: Se agrega orderBy('duration', 'asc') para evitar que 
     * MySQL devuelva una fila aleatoria cuando hay múltiples servicios con duraciones 
     * distintas en la misma sede. Esto elimina el bug de "Hoy -> Viernes -> Sin turnos".
     */
    public function getSlotDurationMinutes(int $addressId): int
    {
        $cacheKey = self::CACHE_PREFIX . 'slot_duration:' . $addressId;
        
        return Cache::remember($cacheKey, self::CACHE_TTL * 60, function () use ($addressId) {
            return (int) (DB::table('address_service')
                ->where('address_id', $addressId)
                ->orderBy('duration', 'asc') // 🔒 BLINDAJE: Hace la consulta 100% determinista
                ->value('duration') ?? 20);
        });
    }

    public function getSchedulesForDay(int $addressId, array $doctorIds, int $dayOfWeek)
    {
        $cacheKey = self::CACHE_PREFIX . 'schedules:' . $addressId . ':' . implode(',', $doctorIds) . ':' . $dayOfWeek;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return collect($cached)->map(function ($item) {
                $schedule = new Schedule();
                foreach ($item as $key => $value) {
                    $schedule->setAttribute($key, $value);
                }
                return $schedule;
            });
        }

        $schedules = Schedule::with(['doctor', 'address'])
            ->where('address_id', $addressId)
            ->whereIn('doctor_id', $doctorIds)
            ->where('day', $dayOfWeek)
            ->where('is_active', true)
            ->get();

        Cache::put($cacheKey, $schedules->toArray(), self::CACHE_TTL * 60);
        return $schedules;
    }

    public function getUnavailabilitiesForDate(array $doctorIds, Carbon $date, ?int $addressId = null)
    {
        $dateStr = $date->toDateString();
        $cacheKey = self::CACHE_PREFIX . 'unavailabilities:' . implode(',', $doctorIds) . ':' . $dateStr . ':' . ($addressId ?? 'all');
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return collect($cached)->map(function ($item) {
                $unavailability = new Unavailability();
                foreach ($item as $key => $value) {
                    $unavailability->setAttribute($key, $value);
                }
                return $unavailability;
            });
        }

        $unavailabilities = Unavailability::whereIn('doctor_id', $doctorIds)
            ->where(function ($q) use ($dateStr, $addressId) {
                $q->where('start_date', '<=', $dateStr)
                  ->where('end_date', '>=', $dateStr)
                  ->where(function ($sub) use ($addressId) {
                      $sub->whereNull('address_id')->orWhere('address_id', $addressId);
                  });
            })
            ->get();

        Cache::put($cacheKey, $unavailabilities->toArray(), self::CACHE_TTL * 60);
        return $unavailabilities;
    }

    public function getOccupiedAppointmentsForDate(array $doctorIds, int $addressId, Carbon $date)
    {
        $dateStr = $date->toDateString();
        $cacheKey = self::CACHE_PREFIX . 'appointments:' . implode(',', $doctorIds) . ':' . $addressId . ':' . $dateStr;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return collect($cached)->map(function ($item) {
                $appointment = new Appointment();
                foreach ($item as $key => $value) {
                    $appointment->setAttribute($key, $value);
                }
                return $appointment;
            });
        }

        $appointments = Appointment::whereIn('doctor_id', $doctorIds)
            ->where('address_id', $addressId)
            ->where('date', $dateStr)
            ->whereIn('status', ['pending', 'confirmed'])
            ->get(['id', 'start_time', 'end_time', 'doctor_id']);

        Cache::put($cacheKey, $appointments->toArray(), self::CACHE_TTL * 60);
        return $appointments;
    }

    public function invalidateAvailabilityCache(?int $clinicId = null, array $doctorIds = [], ?int $addressId = null): void
    {
        try {
            $keysToForget = [];

            if ($clinicId !== null) {
                $keysToForget[] = self::CACHE_PREFIX . 'min_notice:' . $clinicId;
                $keysToForget[] = self::CACHE_PREFIX . 'buffer:' . $clinicId;
            }

            foreach ($doctorIds as $docId) {
                $keysToForget[] = self::CACHE_PREFIX . 'min_notice:doctors:' . $docId;
                $keysToForget[] = self::CACHE_PREFIX . 'buffer:doctor:' . $docId;
            }

            if (!empty($doctorIds)) {
                $keysToForget[] = self::CACHE_PREFIX . 'min_notice:doctors:' . implode(',', $doctorIds);
            }

            if ($addressId && !empty($doctorIds)) {
                foreach ($doctorIds as $docId) {
                    for ($day = 1; $day <= 7; $day++) {
                        $keysToForget[] = self::CACHE_PREFIX . 'schedules:' . $addressId . ':' . $docId . ':' . $day;
                    }
                    for ($i = 0; $i < 14; $i++) {
                        $date = Carbon::now()->addDays($i)->toDateString();
                        $keysToForget[] = self::CACHE_PREFIX . 'unavailabilities:' . $docId . ':' . $date . ':' . $addressId;
                        $keysToForget[] = self::CACHE_PREFIX . 'unavailabilities:' . $docId . ':' . $date . ':all';
                        $keysToForget[] = self::CACHE_PREFIX . 'appointments:' . $docId . ':' . $addressId . ':' . $date;
                    }
                }
            }

            if ($addressId) {
                $keysToForget[] = self::CACHE_PREFIX . 'slot_duration:' . $addressId;
            }

            foreach ($keysToForget as $key) {
                Cache::forget($key);
            }

            Cache::tags(['availability'])->flush();
        } catch (\Exception $e) {
            Log::error('Error invalidando caché de disponibilidad', ['error' => $e->getMessage()]);
        }
    }
}