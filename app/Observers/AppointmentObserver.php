<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\DoctorSetting;
use App\Jobs\SyncZoomMeetingJob;
use App\Jobs\CancelZoomMeetingJob;
use App\Events\AppointmentRescheduled;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentObserver
{
    /**
     * EVENTO ANTES DE GUARDAR: Ideal para preparar datos internos y despachar Jobs de API.
     */
    public function updating(Appointment $appointment)
    {
        // LOG DE CONTROL DE ENTRADA
        Log::info('¡Observer detectó una actualización en la cita ID: ' . $appointment->id . '!');
        Log::info('¿Cambió la fecha o la hora?: ' . ($appointment->isDirty(['date', 'start_time']) ? 'SÍ' : 'NO'));
        
        // =========================================================================
        // FLUJO A: PREPARAR AGENDA EN CITAS VIRTUALES (ZOOM)
        // =========================================================================
        if ($appointment->isDirty(['date', 'start_time']) && $appointment->service?->type === 'virtual') {
            
            $settings = $appointment->clinic_id 
                ? ClinicSetting::where('clinic_id', $appointment->clinic_id)->first()
                : DoctorSetting::where('doctor_id', $appointment->doctor_id)->first();

            if ($settings && $settings->virtual_meeting_platform === 'zoom' && $appointment->zoom_meeting_id) {
                
                DB::table('zoom_creation_failures')->updateOrInsert(
                    ['appointment_id' => $appointment->id],
                    [
                        'attempts' => 0,
                        'status' => 'pending',
                        'last_error' => 'Reagendamiento detectado. Sincronizando con Zoom API.',
                        'updated_at' => now(),
                        'created_at' => now()
                    ]
                );

                dispatch(new SyncZoomMeetingJob($appointment->id));
            }
        }

        // =========================================================================
        // FLUJO B: DETECTAR CANCELACIÓN DE CITAS VIRTUALES
        // =========================================================================
        if ($appointment->isDirty('status') && $appointment->status === 'cancelled' && $appointment->service?->type === 'virtual') {
            
            if ($appointment->zoom_meeting_id) {
                dispatch(new CancelZoomMeetingJob($appointment->zoom_meeting_id));
                
                // Limpieza directa de columnas antes de que la query se ejecute
                $appointment->zoom_meeting_id = null;
                $appointment->meeting_link = null;
                $appointment->zoom_start_url = null;
                $appointment->meeting_link_password = null;
            }
        }
    }

    /**
     * EVENTO DESPUÉS DE GUARDAR: El lugar correcto y seguro para enviar correos y alertas.
     */
    public function updated(Appointment $appointment)
    {
        // =========================================================================
        // FLUJO UNIFICADO DE NOTIFICACIÓN DE REAGENDAMIENTO (FÍSICO Y VIRTUAL)
        // =========================================================================
        // Tu controlador fuerza 'email_sent' => false al reprogramar. 
        // Validamos si el campo es falso y si se alteraron las coordenadas de tiempo de la cita.
        if ($appointment->email_sent == false && $appointment->isDirty(['date', 'start_time'])) {
            
            // 1. Capturamos los valores históricos reales que estaban en la BD antes del cambio
            $originalDate = $appointment->getOriginal('date');
            $originalStartTime = $appointment->getOriginal('start_time');

            // 2. Formateo preventivo multilenguaje seguro de Carbon
            $dateStr = $originalDate instanceof Carbon ? $originalDate->format('Y-m-d') : $originalDate;
            $timeStr = $originalStartTime instanceof Carbon ? $originalStartTime->format('H:i') : $originalStartTime;
            $previousDateTime = Carbon::parse("{$dateStr} {$timeStr}")->format('d/m/Y H:i');

            // 3. Despachamos el evento que enviará el Mail asíncrono
            event(new AppointmentRescheduled($appointment, $previousDateTime));

            // 4. Seteamos el flag a true y guardamos en la BD de forma silenciosa.
            // Al ejecutarse en 'updated' e interactuar de forma aislada, es 100% inmune a loops infinitos.
            $appointment->email_sent = true;
            $appointment->saveQuietly();
        }
    }
}
