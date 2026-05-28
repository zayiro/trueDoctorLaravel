<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// 🔒 IMPORTACIONES MAESTRAS AÑADIDAS PARA EVITAR TYPEERRORS
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * Sedes físicas o virtuales en las cuales se presta este servicio.
     * Inyecta explícitamente las columnas transaccionales de la tabla intermedia.
     */
    public function addresses(): BelongsToMany
    {
        return $this->belongsToMany(Address::class, 'address_service')
                    ->withPivot(['price', 'duration'])
                    ->withTimestamps();
    }

    /**
     * Un servicio tiene muchas citas médicas registradas en el SaaS.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
