<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorSetting extends Model
{
    // Definimos el nombre exacto de tu tabla por seguridad
    protected $table = 'doctor_settings';

    protected $fillable = [
        'doctor_id',
        'plan_id',
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

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'id');
    }

    /**
     * 👇 NUEVA RELACIÓN: Cada configuración pertenece a un Plan
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'id');
    }
}
