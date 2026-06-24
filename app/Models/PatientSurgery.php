<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientSurgery extends Model
{
    protected $fillable = [
        'patient_id',
        'name',
        'year',
        'observations',
        'anesthesia_complications', // Nuevo
        'anesthesia_details'        // Nuevo
    ];

    protected $casts = [
        'anesthesia_complications' => 'boolean',
    ];

    // Relación inversa: una cirugía pertenece a un paciente
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function surgeries() 
    { 
        return $this->hasMany(PatientSurgery::class); 
    }
}
