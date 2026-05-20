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
        $defaultPlan = Plan::where('plan', 'free')->first();

        // Creamos la configuración por defecto
        DoctorSetting::create([
            'doctor_id' => $doctor->id,
            'plan_id' => $defaultPlan->id ?? null,
            'accepts_online_payments' => false,
            'currency' => 'COP',
            'min_notice_hours' => 2,
            'max_advance_days' => 30,
            'requires_approval' => false,
            'email_notifications' => true,
            'whatsapp_notifications' => false,
        ]);
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
