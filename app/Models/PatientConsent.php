<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientConsent extends Model
{
    protected $fillable = ['patient_id', 'doctor_id', 'type', 'signed_at', 'file_path', 'ip_address'];

    public function patient() {
        return $this->belongsTo(Patient::class);
    }
}
