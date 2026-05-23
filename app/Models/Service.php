<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $touches = ['addresses'];
    
    protected $fillable = [
        'name', 
        'price', 
        'type', 
        'duration',
        'active'
    ];

    public function addresses()
    {
        return $this->belongsToMany(Address::class, 'address_service')->withPivot('price', 'duration')->withTimestamps();
    }


        /**
     * Un servicio tiene muchas citas
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
