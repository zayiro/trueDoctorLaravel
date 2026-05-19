<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'service_id',
        'address_id',
        'date',
        'start_time',
        'end_time',
        'duration',
        'price',
        'status',
        'meeting_link',     // Enlace para el paciente (o fallback interno)
        'zoom_meeting_id',  // ID identificador de Zoom (Nuevo)
        'zoom_start_url',   // Enlace de inicio para el Doctor (Nuevo)
        'notes',
    ];

    /**
     * Determina si la cita actual cuenta con una videollamada de Zoom activa
     */
    public function hasZoom(): bool
    {
        return !is_null($this->zoom_meeting_id);
    }

    /**
     * Relación con el Servicio
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Relación con la Sede (Dirección)
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * Relación con el Doctor
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    /**
     * Relación con el Paciente
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
