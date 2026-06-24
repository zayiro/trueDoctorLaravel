<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientFamilyHistory extends Model
{
    protected $fillable = [
        'patient_id', 
        'condition', 
        'relationship', 
        'notes'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }    
}
