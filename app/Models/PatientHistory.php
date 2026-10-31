<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientHistory extends Model
{
    // 1. Campos permitidos para asignación masiva (Alineados con la nueva DB)
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'appointment_id',
        'entry_type',
        'status',
        'cie10_code',
        'ai_assisted',
        'soap_subjective',
        'soap_objective',
        'soap_assessment',
        'soap_plan',
    ];

    // 2. Conversiones de tipo (Casts)
    protected $casts = [
        'ai_assisted' => 'boolean',
        
        // ⚠️ NOTA SOBRE ENCRIPTACIÓN: 
        // Tu migración original tenía el comentario: "SOAP (se encriptan en el modelo con AES-256)".
        // Si deseas que Laravel encripte/desencripte estos campos automáticamente al guardar/leer, 
        // descomenta las siguientes 4 líneas. Si prefieres texto plano por ahora, déjalas comentadas.
        // 'soap_subjective' => 'encrypted',
        // 'soap_objective'  => 'encrypted',
        // 'soap_assessment' => 'encrypted',
        // 'soap_plan'       => 'encrypted',
    ];

    // 3. Relaciones
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    // ✅ RELACIÓN NUEVA Y CRÍTICA: 
    // Necesaria para que funcione la agrupación por cita en show.blade.php 
    // ($notes->first()->appointment)
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PatientHistoryAttachment::class);
    }
}