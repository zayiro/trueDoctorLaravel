<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffectedAppointment extends Model
{
    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'unavailability_id',
        'appointment_id',
        'patient_id',
        'doctor_id',
        'original_date',
        'original_start_time',
        'original_end_time',
        'status',
        'notification_sent_at',
        'rescheduled_to_appointment_id',
        'notes',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'original_date' => 'date',
        'notification_sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con la inasistencia que causó el impacto.
     */
    public function unavailability(): BelongsTo
    {
        return $this->belongsTo(Unavailability::class, 'unavailability_id');
    }

    /**
     * Relación con la cita original afectada.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    /**
     * Relación con el paciente afectado.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * Relación con el doctor responsable.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    /**
     * Relación con la nueva cita si fue reagendada.
     */
    public function rescheduledAppointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'rescheduled_to_appointment_id');
    }
}
