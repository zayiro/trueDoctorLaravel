<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    public function patient()
    {
        // Indicamos que patient_id es la llave foránea que apunta a la tabla users
        return $this->belongsTo(User::class, 'patient_id');
    }
}
