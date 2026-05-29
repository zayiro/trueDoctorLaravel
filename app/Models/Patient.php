<?php

namespace App\Models;
use Carbon\Carbon;

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

    // 1. Asegúrate de que Laravel trate el campo como una fecha
    protected $casts = [
        'birth_date' => 'date',
    ];

    // 2. Crea un atributo virtual (Accessor)
    public function getAgeAttribute()
    {
        // Valida que exista la fecha antes de calcular la edad
        if (!$this->birth_date) {
            return 'No registrada';
        }

        return $this->birth_date->age;
    }

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

    /**
     * Convierte automáticamente cm a m si el valor es mayor a 10.
     * Ejemplo: 172 -> 1.72
     */
    protected function setHeightAttribute($value)
    {
        $this->attributes['height'] = $value > 10 ? $value / 100 : $value;
    }

    /**
     * Obtener el departamento del paciente.
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Obtener la ciudad del paciente.
     */
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    /**
     * Obtener todos los reportes y anexos clínicos subidos por el paciente.
     * Conectado mediante el campo 'patient_id' indexado.
     */
    public function attachments()
    {
        return $this->hasMany(PatientHistoryAttachment::class, 'patient_id')->latest();
    }
}
