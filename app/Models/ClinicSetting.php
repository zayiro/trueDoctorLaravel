<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicSetting extends Model
{
    use HasFactory;

    // Nombre de la tabla explícito (por si Laravel busca clinic_settings en plural automático)
    protected $table = 'clinic_settings';

    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'clinic_id', // OBLIGATORIO: Permite la inyección automática desde el Observer
        'plan_id',   // OBLIGATORIO: Permite asignar el plan de suscripción corporativo
        'accepts_online_payments',
        'currency',
        'min_notice_hours',
        'max_advance_days',
        'requires_approval',
        'buffer_time_minutes',
        'max_appointments_per_day',
        'allow_patient_cancellation',
        'cancellation_notice_hours',
        'allow_patient_rescheduling',
        'virtual_meeting_platform',
        'google_calendar_sync',
        'email_notifications',
        'whatsapp_notifications',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'accepts_online_payments'    => 'boolean',
        'min_notice_hours'           => 'integer',
        'max_advance_days'           => 'integer',
        'requires_approval'          => 'boolean',
        'buffer_time_minutes'        => 'integer',
        'max_appointments_per_day'   => 'integer', // null si es ilimitado
        'allow_patient_cancellation' => 'boolean',
        'cancellation_notice_hours'  => 'integer',
        'allow_patient_rescheduling' => 'boolean',
        'google_calendar_sync'       => 'boolean',
        'email_notifications'        => 'boolean',
        'whatsapp_notifications'     => 'boolean',
    ];

    /**
     * Relación inversa uno a uno con la Clínica dueña de la configuración.
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'clinic_id');
    }

    /**
     * Relación con el Plan de suscripción asignado a la clínica.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }
}
