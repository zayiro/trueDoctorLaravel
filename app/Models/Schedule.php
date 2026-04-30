<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = ['address_id', 'day', 'start_time', 'end_time', 'duration'];

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
        return $dias[$this->day];
    }
}
