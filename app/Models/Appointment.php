<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'service_id',
        'address_id',
        'date',
        'start_time',
        'end_time',
        'duration',
        'price',
        'status',
        'meeting_link',
    ];

    public function patient()
    {
        // Indicamos que patient_id es la llave foránea que apunta a la tabla users
        return $this->belongsTo(User::class, 'patient_id');
    }
}
