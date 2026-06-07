<?php

namespace App\Listeners;

use App\Events\AppointmentRescheduled;
use App\Mail\AppointmentRescheduledMail;       // Plantilla para el Paciente
use App\Mail\ProviderAppointmentRescheduledMail; // Nueva plantilla para el Médico/Clínica
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendAppointmentRescheduledNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Procesa la notificación masiva de reprogramación de forma asíncrona.
     */
    public function handle(AppointmentRescheduled $event): void
    {
        $appointment = $event->appointment;
        
        // Aseguramos la carga fresca de relaciones para evitar campos nulos en las plantillas
        $appointment->loadMissing(['patient.user', 'doctor.user', 'clinic.user']);

        // -------------------------------------------------------------------------
        // RIEL A: ENVÍO AL PACIENTE
        // -------------------------------------------------------------------------
        $patientEmail = $appointment->patient?->user?->email ?? $appointment->patient?->email;
        
        if ($patientEmail) {
            Mail::to($patientEmail)->send(
                new AppointmentRescheduledMail($appointment, $event->previousDateTime)
            );
        }

        // -------------------------------------------------------------------------
        // RIEL B: ENVÍO AL PROVEEDOR DE SALUD (DOCTOR O CLÍNICA UNDER MULTI-TENANCY)
        // -------------------------------------------------------------------------
        $providerEmail = null;

        if ($appointment->clinic_id && $appointment->clinic) {
            // Caso Clínica: Se envía al correo de la cuenta institucional de la clínica
            $providerEmail = $appointment->clinic->user?->email;
        } elseif ($appointment->doctor && $appointment->doctor->user) {
            // Caso Doctor Independiente: Se envía a la cuenta directa del médico
            $providerEmail = $appointment->doctor->user->email;
        }

        if ($providerEmail) {
            Mail::to($providerEmail)->send(
                new ProviderAppointmentRescheduledMail($appointment, $event->previousDateTime)
            );
        }
    }
}
