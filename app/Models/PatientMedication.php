<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientMedication extends Model
{
    protected $fillable = [
        'patient_id',
        'name',
        'dosage',
        'frequency',
        'notes',
        'active'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
