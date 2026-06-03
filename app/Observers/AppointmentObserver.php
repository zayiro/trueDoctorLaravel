<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\DoctorSetting;
use Illuminate\Support\Facades\DB;
use App\Jobs\SyncZoomMeetingJob;

class AppointmentObserver
{
    /**
     * Escucha el evento de actualización antes de guardar en la BD.
     */
    public function updating(Appointment $appointment)
    {
        // =========================================================================
        // FLUJO A: DETECTAR REAGENDAMIENTO EN CITAS VIRTUALES
        // =========================================================================
        if ($appointment->isDirty(['date', 'start_time']) && $appointment->service?->type === 'virtual') {
            
            // Obtener la configuración del Tenant (Clínica o Doctor Independiente)
            $settings = $appointment->clinic_id 
                ? ClinicSetting::where('clinic_id', $appointment->clinic_id)->first()
                : DoctorSetting::where('doctor_id', $appointment->doctor_id)->first();

            // Si usa Zoom y ya tiene una videollamada asignada, procedemos asíncronamente
            if ($settings && $settings->virtual_meeting_platform === 'zoom' && $appointment->zoom_meeting_id) {
                
                // Registramos o reseteamos el estado de fallo en tu tabla nativa de contingencia
                DB::table('zoom_creation_failures')->updateOrInsert(
                    ['appointment_id' => $appointment->id],
                    [
                        'attempts' => 0,
                        'status' => 'pending',
                        'last_error' => 'Reagendamiento detectado. Pendiente de sincronización.',
                        'updated_at' => now(),
                        'created_at' => now()
                    ]
                );

                // Despachamos el Job a la cola en segundo plano pasándole el ID de la cita
                dispatch(new SyncZoomMeetingJob($appointment->id));
            }
        }

        // =========================================================================
        // FLUJO B: DETECTAR CANCELACIÓN DE CITAS VIRTUALES (NUEVA REGLA)
        // =========================================================================
        if ($appointment->isDirty('status') && $appointment->status === 'cancelled' && $appointment->service?->type === 'virtual') {
            
            if ($appointment->zoom_meeting_id) {
                // 🚀 Despachamos el Job asíncrono para borrar la reunión en los servidores de Zoom
                dispatch(new \App\Jobs\CancelZoomMeetingJob($appointment->zoom_meeting_id));
                
                // Limpiamos las columnas de telemedicina en la base de datos local de inmediato
                $appointment->zoom_meeting_id = null;
                $appointment->meeting_link = null;
                $appointment->zoom_start_url = null;
                $appointment->meeting_link_password = null;
            }
        }
    }
}
