<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Address extends Model
{
    protected $fillable = [
        'doctor_id', 
        'name', 
        'address',
        'phone',
        'status',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }


    /**
     * Relación con los horarios (schedules)
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class); 
        // Asegúrate de que el nombre del modelo sea 'Schedule'
    }
}
