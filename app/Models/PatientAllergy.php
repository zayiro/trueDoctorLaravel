<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientAllergy extends Model
{
    // Forzamos el nombre de la tabla que creaste en la migración
    protected $table = 'patient_allergies';
    
    protected $fillable = [
        'patient_id', 
        'name', 
        'type', 
        'severity', 
        'reaction'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
