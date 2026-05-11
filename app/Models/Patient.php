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

    public function insurance()
    {
        return $this->belongsTo(Insurance::class);
    }

    /**
     * Calcula la edad exacta del paciente.
     */
    public function getAgeAttribute()
    {
        return $this->birth_date ? Carbon::parse($this->birth_date)->age : 'N/A';
    }

    /**
     * Calcula el Índice de Masa Corporal (IMC).
     */
    public function getImcAttribute()
    {
        if ($this->weight && $this->height && $this->height > 0) {
            return round($this->weight / ($this->height * $this->height), 2);
        }
        return null;
    }

    /**
     * Clasificación del IMC según la OMS.
     */
    public function getImcStatusAttribute()
    {
        $imc = $this->imc;
        if (!$imc) return 'N/A';
        if ($imc < 18.5) return 'Bajo peso';
        if ($imc < 25) return 'Normal';
        if ($imc < 30) return 'Sobrepeso';
        return 'Obesidad';
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

    public function consents()
    {
        // Un paciente puede tener múltiples consentimientos (Telemedicina, Cirugía, etc.)
        return $this->hasMany(PatientConsent::class);
    }

    /**
     * Obtener el historial médico del paciente.
     */
    public function patientHistories()
    {
        return $this->hasMany(PatientHistory::class);
    }

    //usando un Mutator. Así, si alguien escribe "172", el sistema lo convierte a "1.72" antes de guardar
    protected function height(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value) => $value > 10 ? $value / 100 : $value,
        );
    }

    /**
     * Convierte automáticamente cm a m si el valor es mayor a 10.
     * Ejemplo: 172 -> 1.72
     */
    protected function setHeightAttribute($value)
    {
        $this->attributes['height'] = $value > 10 ? $value / 100 : $value;
    }
}
