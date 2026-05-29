<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientHistory extends Model
{
    protected $fillable = [
        'patient_id', 
        'doctor_id', 
        'appointment_id', 
        'reason_for_consultation', 
        'entry_type',
        'symptoms', 
        'diagnosis', 
        'treatment_plan'
    ];
    
    public function patient() {
        return $this->belongsTo(Patient::class);
    }

    public function doctor() {
        return $this->belongsTo(Doctor::class);
    }

    public function attachments()
    {
        return $this->hasMany(PatientHistoryAttachment::class);
    }
}
