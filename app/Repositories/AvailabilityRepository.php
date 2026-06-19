<?php

namespace App\Repositories;

use App\Models\Schedule;
use App\Models\Appointment;
use App\Models\Unavailability;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 📦 REPOSITORIO DE DISPONIBILIDAD
 * 
 * Centraliza todas las consultas de cálculo de slots y turnos disponibles.
 * Implementa estrategia de caché para optimizar consultas repetidas.
 * Invalida caché automáticamente cuando se confirman nuevas reservas.
 */
class AvailabilityRepository
{
    /**
     * Prefijo para las claves de caché
     */
    private const CACHE_PREFIX = 'availability:';
    
    /**
     * Duración del caché en minutos (30 minutos por defecto)
     */
    private const CACHE_TTL = 30;

    /**
     * Obtiene el margen mínimo de aviso (colchón logístico)
     * 
     * @param int|null $clinicId ID de la clínica
     * @param array $doctorIds IDs de los doctores
     * @return int Horas de aviso mínimo
     */
    public function getMinNoticeHours(?int $clinicId = null, array $doctorIds = []): int
    {
        $cacheKey = self::CACHE_PREFIX . 'min_notice:' . ($clinicId ?? 'doctors:' . implode(',', $doctorIds));

        return Cache::remember($cacheKey, self::CACHE_TTL * 60, function () use ($clinicId, $doctorIds) {
            if (!is_null($clinicId)) {
                return DB::table('clinic_settings')
                    ->where('clinic_id', $clinicId)
                    ->value('min_notice_hours') ?? 2;
            } else {
                return DB::table('doctor_settings')
                    ->whereIn('doctor_id', $doctorIds)
                    ->value('min_notice_hours') ?? 2;
            }
        });
    }

    /**
     * Obtiene la duración del servicio en una sede
     * 
     * @param int $addressId ID de la sede
     * @return int Duración en minutos
     */
    public function getSlotDurationMinutes(int $addressId): int
    {
        $cacheKey = self::CACHE_PREFIX . 'slot_duration:' . $addressId;

        return Cache::remember($cacheKey, self::CACHE_TTL * 60, function () use ($addressId) {
            return DB::table('address_service')
                ->where('address_id', $addressId)
                ->value('duration') ?? 20;
        });
    }

    /**
     * Obtiene los horarios programados para un día específico
     * 
     * @param int $addressId ID de la sede
     * @param array $doctorIds IDs de los doctores
     * @param int $dayOfWeek Día de la semana (1-7)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSchedulesForDay(int $addressId, array $doctorIds, int $dayOfWeek)
    {
        $cacheKey = self::CACHE_PREFIX . 'schedules:' . $addressId . ':' . implode(',', $doctorIds) . ':' . $dayOfWeek;

        return Cache::remember($cacheKey, self::CACHE_TTL * 60, function () use ($addressId, $doctorIds, $dayOfWeek) {
            return Schedule::with(['doctor', 'address'])
                ->where('address_id', $addressId)
                ->whereIn('doctor_id', $doctorIds)
                ->where('day', $dayOfWeek)
                ->get();
        });
    }

    /**
     * Obtiene las indisponibilidades de los doctores para una fecha específica
     * 
     * @param array $doctorIds IDs de los doctores
     * @param Carbon $date Fecha a evaluar
     * @param int|null $addressId ID de la sede (opcional)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUnavailabilitiesForDate(array $doctorIds, Carbon $date, ?int $addressId = null)
    {
        $dateStr = $date->toDateString();
        $cacheKey = self::CACHE_PREFIX . 'unavailabilities:' . implode(',', $doctorIds) . ':' . $dateStr . ':' . ($addressId ?? 'all');

        return Cache::remember($cacheKey, self::CACHE_TTL * 60, function () use ($doctorIds, $dateStr, $addressId) {
            return Unavailability::whereIn('doctor_id', $doctorIds)
                ->where(function ($q) use ($dateStr, $addressId) {
                    $q->where('start_date', '<=', $dateStr)
                      ->where('end_date', '>=', $dateStr)
                      ->where(function ($sub) use ($addressId) {
                          $sub->whereNull('address_id')->orWhere('address_id', $addressId);
                      });
                })
                ->get();
        });
    }

    /**
     * Obtiene las citas confirmadas/pendientes para una fecha específica
     * 
     * @param array $doctorIds IDs de los doctores
     * @param int $addressId ID de la sede
     * @param Carbon $date Fecha a evaluar
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOccupiedAppointmentsForDate(array $doctorIds, int $addressId, Carbon $date)
    {
        $dateStr = $date->toDateString();
        $cacheKey = self::CACHE_PREFIX . 'appointments:' . implode(',', $doctorIds) . ':' . $addressId . ':' . $dateStr;

        return Cache::remember($cacheKey, self::CACHE_TTL * 60, function () use ($doctorIds, $addressId, $dateStr) {
            return Appointment::with(['doctor'])
                ->whereIn('doctor_id', $doctorIds)
                ->where('address_id', $addressId)
                ->where('date', $dateStr)
                ->whereIn('status', ['pending', 'confirmed'])
                ->get(['id', 'start_time', 'end_time', 'doctor_id']);
        });
    }

    /**
     * Invalida el caché de disponibilidad para un doctor/clínica específico
     * Se ejecuta automáticamente cuando se confirma una nueva cita
     * 
     * @param int|null $clinicId ID de la clínica
     * @param array $doctorIds IDs de los doctores
     * @param int|null $addressId ID de la sede
     * @return void
     */
    public function invalidateAvailabilityCache(?int $clinicId = null, array $doctorIds = [], ?int $addressId = null): void
    {
        try {
            // Invalidar caché de margen mínimo
            $minNoticeKey = self::CACHE_PREFIX . 'min_notice:' . ($clinicId ?? 'doctors:' . implode(',', $doctorIds));
            Cache::forget($minNoticeKey);

            // Invalidar caché de duración de slots
            if ($addressId) {
                $slotDurationKey = self::CACHE_PREFIX . 'slot_duration:' . $addressId;
                Cache::forget($slotDurationKey);
            }

            // Invalidar caché de horarios para todos los días de la semana
            if ($addressId && !empty($doctorIds)) {
                for ($day = 1; $day <= 7; $day++) {
                    $scheduleKey = self::CACHE_PREFIX . 'schedules:' . $addressId . ':' . implode(',', $doctorIds) . ':' . $day;
                    Cache::forget($scheduleKey);
                }
            }

            // Invalidar caché de indisponibilidades para los próximos 14 días
            if (!empty($doctorIds)) {
                for ($i = 0; $i < 14; $i++) {
                    $date = Carbon::now()->addDays($i)->toDateString();
                    $unavailabilityKey = self::CACHE_PREFIX . 'unavailabilities:' . implode(',', $doctorIds) . ':' . $date . ':' . ($addressId ?? 'all');
                    Cache::forget($unavailabilityKey);
                }
            }

            // Invalidar caché de citas ocupadas para los próximos 14 días
            if (!empty($doctorIds) && $addressId) {
                for ($i = 0; $i < 14; $i++) {
                    $date = Carbon::now()->addDays($i)->toDateString();
                    $appointmentKey = self::CACHE_PREFIX . 'appointments:' . implode(',', $doctorIds) . ':' . $addressId . ':' . $date;
                    Cache::forget($appointmentKey);
                }
            }

            Log::info('AvailabilityRepository: Caché invalidado', [
                'clinic_id' => $clinicId,
                'doctor_ids' => $doctorIds,
                'address_id' => $addressId
            ]);
        } catch (\Exception $e) {
            Log::error('AvailabilityRepository: Error al invalidar caché', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Invalida TODO el caché de disponibilidad (operación nuclear)
     * Usar solo en casos excepcionales
     * 
     * @return void
     */
    public function flushAllAvailabilityCache(): void
    {
        try {
            Cache::tags(['availability'])->flush();
            Log::warning('AvailabilityRepository: Caché completo de disponibilidad limpiado');
        } catch (\Exception $e) {
            Log::error('AvailabilityRepository: Error al limpiar caché completo', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtiene estadísticas de caché para debugging
     * 
     * @return array
     */
    public function getCacheStats(): array
    {
        return [
            'prefix' => self::CACHE_PREFIX,
            'ttl_minutes' => self::CACHE_TTL,
            'timestamp' => now()
        ];
    }
}
