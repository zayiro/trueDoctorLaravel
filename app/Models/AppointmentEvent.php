<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * 📋 MODELO DE AUDITORÍA: Event Sourcing para Citas
 * 
 * Registra detalladamente cada cambio en el flujo de citas.
 * Permite reconstruir el estado completo de una cita en cualquier momento.
 */
class AppointmentEvent extends Model
{
    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'appointment_id',
        'event_type',
        'payload',
        'metadata',
        'user_id',
        'user_type',
        'ip_address',
        'user_agent',
        'description',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'payload' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con la cita asociada
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    /**
     * Relación con el usuario que realizó la acción
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope para filtrar eventos por tipo
     */
    public function scopeByEventType(Builder $query, string $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope para filtrar eventos por rango de fechas
     */
    public function scopeByDateRange(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope para filtrar eventos por usuario
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para filtrar eventos por tipo de usuario
     */
    public function scopeByUserType(Builder $query, string $userType): Builder
    {
        return $query->where('user_type', $userType);
    }

    /**
     * Obtiene el evento más reciente de una cita
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Obtiene todos los eventos de una cita en orden cronológico
     */
    public static function getAppointmentTimeline(int $appointmentId)
    {
        return self::where('appointment_id', $appointmentId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Reconstruye el estado de una cita en un momento específico
     */
    public static function reconstructAppointmentState(int $appointmentId, ?Carbon $atTime = null)
    {
        $query = self::where('appointment_id', $appointmentId);

        if ($atTime) {
            $query->where('created_at', '<=', $atTime);
        }

        return $query->orderBy('created_at', 'asc')->get();
    }

    /**
     * Obtiene estadísticas de eventos para una cita
     */
    public static function getAppointmentEventStats(int $appointmentId): array
    {
        $events = self::where('appointment_id', $appointmentId)->get();

        return [
            'total_events' => $events->count(),
            'event_types' => $events->groupBy('event_type')->map->count(),
            'first_event' => $events->first()?->created_at,
            'last_event' => $events->last()?->created_at,
            'users_involved' => $events->pluck('user_id')->unique()->count(),
        ];
    }
}
