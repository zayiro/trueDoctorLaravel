<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'doctor_id', 
        'name', 
        'price', 
        'type', 
        'duration',
        'active'
    ];

    public function addresses() 
    {
        return $this->belongsToMany(Address::class);
    }
}
