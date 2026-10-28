<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Schedule extends Model
{
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
     * 🔒 CORRECCIÓN CRÍTICA: start_time y end_time deben ser strings nativos
     * para evitar el bug de "Double date specification" al parsearlos con Carbon.
     * La columna en MySQL es TIME (ej: "06:00:00"), no DATETIME.
     */
    protected $casts = [
        'start_time' => 'string',  // ✅ String nativo (ej: "06:00:00")
        'end_time'   => 'string',  // ✅ String nativo (ej: "18:00:00")
        'is_active'  => 'boolean',
        'day'        => 'integer',
    ];

    /**
     * 🔒 SCOPE: Filtra los bloques de horario según el espacio de trabajo activo.
     */
    public function scopeForCurrentContext(Builder $query): Builder
    {
        $user = Auth::user();
        $context = session('doctor_context');

        if (!$user) {
            return $query;
        }

        if ($user->role === 'clinic') {
            return $query->whereHas('address', function ($q) use ($user) {
                $q->where('clinic_id', $user->clinic->id);
            });
        }

        if ($user->role === 'doctor') {
            $doctorProfileId = $user->doctor->id;

            if (($context['type'] ?? 'particular') === 'clinic') {
                return $query->where('doctor_id', $doctorProfileId)
                    ->whereHas('address', function ($q) use ($context) {
                        $q->where('clinic_id', $context['id']);
                    });
            }

            return $query->where('doctor_id', $doctorProfileId)
                ->whereHas('address', function ($q) {
                    $q->whereNull('clinic_id');
                });
        }

        return $query;
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'clinic_id');
    }

    public function getDayNameAttribute(): string
    {
        $dias = [
            0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes',
            3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'
        ];
        return $dias[$this->day] ?? 'Desconocido';
    }

    /**
     * 🔒 CORRECCIÓN: Usar createFromFormat para parsear strings TIME correctamente
     */
    public function getRangeAttribute(): string
    {
        $start = $this->start_time 
            ? Carbon::createFromFormat('H:i:s', $this->start_time)->format('g:i A') 
            : '00:00';
        $end = $this->end_time 
            ? Carbon::createFromFormat('H:i:s', $this->end_time)->format('g:i A') 
            : '00:00';
        return "{$start} - {$end}";
    }
}