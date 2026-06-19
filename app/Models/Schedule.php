<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Schedule extends Model
{
    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'address_id', 
        'doctor_id', 
        'clinic_id',
        'day', 
        'start_time', 
        'end_time',
        'is_active'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time'   => 'datetime:H:i',
        'is_active'  => 'boolean',
    ];

    /**
     * 🔒 SCOPE: Filtra los bloques de horario de forma automatizada según el espacio de trabajo activo.
     */
    public function scopeForCurrentContext(Builder $query): Builder
    {
        $user = Auth::user();
        $context = session('doctor_context');

        if (!$user) {
            return $query;
        }

        // Si es Clínica Corporativa Pura: Filtramos todos los horarios de sus sedes institucionales
        if ($user->role === 'clinic') {
            return $query->whereHas('address', function ($q) use ($user) {
                $q->where('clinic_id', $user->clinic->id);
            });
        }

        // Si es Doctor: Evaluamos su conmutador de entorno activo
        if ($user->role === 'doctor') {
            $doctorProfileId = $user->doctor->id;

            // Caso A: Contexto Institucional de Clínica Aliada
            if (($context['type'] ?? 'particular') === 'clinic') {
                return $query->where('doctor_id', $doctorProfileId)
                             ->whereHas('address', function ($q) use ($context) {
                                 $q->where('clinic_id', $context['id']);
                             });
            }

            // Caso B: Contexto Consultorio Particular (Producción estándar)
            return $query->where('doctor_id', $doctorProfileId)
                         ->whereHas('address', function ($q) {
                             $q->whereNull('clinic_id');
                         });
        }

        return $query;
    }

    /**
     * El médico especialista asignado a este bloque de tiempo.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    /**
     * Relación inversa con la sede o consultorio físico/virtual.
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    /**
     * 🆕 La clínica corporativa propietaria de este bloque (NULL = consultorio particular).
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'clinic_id');
    }

    /**
     * Atributo dinámico para obtener el nombre del día de la semana.
     */
    public function getDayNameAttribute(): string
    {
        $dias = [
            0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 
            3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'
        ];
        return $dias[$this->day] ?? 'Desconocido';
    }

    /**
     * Formatear rango de horas para la vista (ej: 8:00 AM - 5:00 PM).
     */
    public function getRangeAttribute(): string
    {
        $start = $this->start_time ? $this->start_time->format('g:i A') : '00:00';
        $end = $this->end_time ? $this->end_time->format('g:i A') : '00:00';
        
        return "{$start} - {$end}";
    }
}
