<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    
    protected $fillable = [
        'doctor_id', 
        'name', 
        'address',
        'phone',
        'city_id',
        'status',
        'type',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Relación con los horarios (schedules)
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class); 
        // Asegúrate de que el nombre del modelo sea 'Schedule'
    }

    public function services()
    {
        return $this->belongsToMany(
            Service::class,     // Modelo de destino
            'address_service',  // Tabla pivote
            'address_id',       // Llave foránea en pivote que apunta a Address
            'service_id'        // Llave foránea en pivote que apunta a Service
        )->withTimestamps();
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
