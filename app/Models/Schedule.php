<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = ['address_id', 'day', 'start_time', 'end_time'];

    // Esto convierte los strings de la DB en objetos Carbon automáticamente
    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function address() 
    {
        return $this->belongsTo(Address::class);
    }

    public function getDayNameAttribute()
    {
        $dias = [
            0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 
            3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'
        ];
        return $dias[$this->day] ?? 'Desconocido';
    }

    /**
     * Formatear horas para la vista (ej: 08:00 AM)
     */
    public function getRangeAttribute()
    {
        return Carbon::parse($this->start_time)->format('g:i A') . ' - ' . Carbon::parse($this->end_time)->format('g:i A');
    }
}
