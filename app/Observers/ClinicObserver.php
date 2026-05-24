<?php

namespace App\Observers;

use App\Models\Clinic;
use App\Models\Plan;
use App\Models\ClinicSetting;

class ClinicObserver
{
    /**
     * Handle the Clinic "created" event.
     * Se ejecuta automáticamente tras persistir la clínica en la base de datos.
     */
    public function created(Clinic $clinic): void
    {
        // 1. Buscar el plan gratuito por defecto para centros médicos
        $defaultPlan = Plan::where('plan', 'free')->first();

        // 2. Inicializar la configuración por defecto de la clínica corporativa
        ClinicSetting::create([
            'clinic_id'                  => $clinic->id, 
            'plan_id'                    => 1,
            'accepts_online_payments'    => false,
            'currency'                   => 'COP',
            'min_notice_hours'           => 2,
            'max_advance_days'           => 30,
            'requires_approval'          => false,
            'buffer_time_minutes'        => 0,
            'max_appointments_per_day'   => null,
            'allow_patient_cancellation' => true,
            'cancellation_notice_hours'  => 2,
            'allow_patient_rescheduling' => true,
            'virtual_meeting_platform'   => 'internal',
            'google_calendar_sync'       => false,
            'email_notifications'        => true,
            'whatsapp_notifications'     => false,
        ]);

        // Autogenera la sede virtual institucional sin duplicados
        $clinic->createVirtualAddress();
    }
}
