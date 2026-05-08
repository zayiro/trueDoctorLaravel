<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientFamilyHistory extends Model
{
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

}
