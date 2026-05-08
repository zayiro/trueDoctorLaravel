<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = [
        'user_id',
        'identification',
        'phone',      
        'blood_type',  
        'birth_date',
        'gender',
        'weight',
        'height',
        'insurance_id',
        'permanent_conditions',        
        'department_id',
        'city_id',
        'residence_zone',
        'occupation',
        'civil_status',
        'ethnicity',
        'affiliation_type',
        'regime_type',
        'sisben_level',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); 
    }
    
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }
    
    public function allergies() {
        return $this->hasMany(PatientAllergy::class);
    }

    public function surgeries()
    {
        return $this->hasMany(PatientSurgery::class);
    }

    public function familyHistories()
    {
        return $this->hasMany(PatientFamilyHistory::class);
    }

    public function medications()
    {
        return $this->hasMany(PatientMedication::class);
    }

    public function histories()
    {
        return $this->hasMany(PatientHistory::class)->latest(); // Siempre las más recientes primero
    }

    // app/Models/Patient.php

    public function consents()
    {
        // Un paciente puede tener múltiples consentimientos (Telemedicina, Cirugía, etc.)
        return $this->hasMany(PatientConsent::class);
    }
}
