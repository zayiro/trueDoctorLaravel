<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unavailability extends Model
{
    // Añade esta propiedad
    protected $fillable = [
        'doctor_id',
        'address_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'reason'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Opcional: Relaciones útiles
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }
}
