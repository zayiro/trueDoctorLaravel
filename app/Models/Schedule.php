<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon; // 🔥 AGREGADO: Importación obligatoria para evitar fallos de clase no encontrada

class Schedule extends Model
{
    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'address_id', 
        'doctor_id', // 🔥 AGREGADO: Permite la asignación masiva de especialistas a franjas horarias
        'day', 
        'start_time', 
        'end_time'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time'   => 'datetime:H:i',
    ];

    /**
     * 🔥 NUEVA RELACIÓN: El médico especialista asignado a este bloque de tiempo.
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
        // Al estar casteado como datetime, convertimos de forma segura usando la instancia Carbon nativa
        $start = $this->start_time ? $this->start_time->format('g:i A') : '00:00';
        $end = $this->end_time ? $this->end_time->format('g:i A') : '00:00';
        
        return "{$start} - {$end}";
    }
}
