<?php

namespace App\Observers;

use App\Models\Doctor;
use App\Models\Plan;
use App\Models\DoctorSetting;

class DoctorObserver
{    
    /**
     * Handle the Doctor "created" event.
     * Se ejecuta después de que el doctor es creado.
     */
    public function created(Doctor $doctor): void
    {
        // 🚀 DETECCIÓN SAAS: Verificamos si el usuario que está operando e iniciando la creación es una clínica
        $isCreatedByClinic = auth()->check() && auth()->user()->role === 'clinic';

        // Si fue creado por una clínica, el plan_id va explícitamente en NULL. 
        // Si se registró solo, busca el Plan Free por defecto.
        $planId = null;
        
        if (!$isCreatedByClinic) {
            $defaultPlan = Plan::where('plan', 'free')->first();
            $planId = $defaultPlan ? $defaultPlan->id : 1;
        }

        // Creamos la configuración con el plan_id condicional
        DoctorSetting::create([
            'doctor_id'                  => $doctor->id,
            'plan_id'                    => $planId, // 🔒 NULL para staff, ID para particulares
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
        $doctor->createVirtualAddress();
    }

    /**
     * Handle the Doctor "updated" event.
     */
    public function updated(Doctor $doctor): void
    {
        //
    }

    /**
     * Handle the Doctor "deleted" event.
     */
    public function deleted(Doctor $doctor): void
    {
        //
    }

    /**
     * Handle the Doctor "restored" event.
     */
    public function restored(Doctor $doctor): void
    {
        //
    }

    /**
     * Handle the Doctor "force deleted" event.
     */
    public function forceDeleted(Doctor $doctor): void
    {
        //
    }
}
