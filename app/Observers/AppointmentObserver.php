<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\DoctorSetting;
use App\Jobs\SyncZoomMeetingJob;
use App\Jobs\CancelZoomMeetingJob;
use App\Events\AppointmentRescheduled;
use App\Repositories\AvailabilityRepository;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;

class AppointmentObserver
{
    private AvailabilityRepository $cacheRepo;

    public function __construct(AvailabilityRepository $cacheRepo)
    {
        $this->cacheRepo = $cacheRepo;
    }

    /**
     * Handle the Appointment "created" event.
     * 
     * 🔒 CORRECCIÓN: Se eliminó el código comentado. Si necesitas crear Zoom
     * al crear la cita, hazlo desde el controlador con afterCommit().
     */
    public function created(Appointment $appointment): void
    {
        Log::info('AppointmentObserver: Cita creada', [
            'appointment_id' => $appointment->id,
            'service_type' => $appointment->service?->type,
        ]);
    }

    /**
     * Handle the Appointment "updating" event.
     * 
     * 🔒 CORRECCIONES:
     * - Eliminado DB::transaction() redundante (el observer ya está dentro de la transacción)
     * - Jobs despachados con afterCommit() para evitar leer datos no confirmados
     * - Validación de existencia de servicio antes de acceder a ->type
     */
    public function updating(Appointment $appointment): void
    {
        try {
            // FLUJO A: REAGENDAMIENTO DE CITAS VIRTUALES (ZOOM)
            if ($appointment->isDirty(['date', 'start_time']) && $appointment->service?->type === 'virtual') {
                $settings = $this->getSettings($appointment);
                
                if ($settings && $settings->virtual_meeting_platform === 'zoom' && $appointment->zoom_meeting_id) {
                    // 🔒 CORRECCIÓN: Usar afterCommit() para que el job se ejecute DESPUÉS del commit
                    dispatch(new SyncZoomMeetingJob($appointment->id))->afterCommit();
                    
                    Log::info('AppointmentObserver: Reagendamiento detectado, Zoom actualizado', [
                        'appointment_id' => $appointment->id,
                    ]);
                }
            }

            // FLUJO B: CANCELACIÓN DE CITAS VIRTUALES
            if ($appointment->isDirty('status') && $appointment->status === 'cancelled' && $appointment->service?->type === 'virtual') {
                if ($appointment->zoom_meeting_id) {
                    // 🔒 CORRECCIÓN: afterCommit() para evitar cancelar Zoom si la transacción falla
                    dispatch(new CancelZoomMeetingJob($appointment->zoom_meeting_id))->afterCommit();
                    
                    // Limpiar columnas de Zoom (se guardarán en el update)
                    $appointment->zoom_meeting_id = null;
                    $appointment->meeting_link = null;
                    $appointment->zoom_start_url = null;
                    $appointment->meeting_link_password = null;
                    
                    Log::info('AppointmentObserver: Cita cancelada, Zoom eliminado', [
                        'appointment_id' => $appointment->id,
                    ]);
                }
            }
        } catch (Throwable $e) {
            Log::error('AppointmentObserver: Error en updating', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
            // No lanzamos la excepción para no romper el flujo de actualización
        }
    }

    /**
     * Handle the Appointment "updated" event.
     * 
     * 🔒 CORRECCIONES:
     * - Eliminado DB::transaction() redundante
     * - Evento AppointmentRescheduled con afterCommit()
     * - Eliminado saveQuietly() (riesgo de loop infinito)
     * - Invalidación de caché al cambiar estado
     * - email_sent se maneja con update() explícito, no saveQuietly()
     */
    public function updated(Appointment $appointment): void
    {
        try {
            // FLUJO: NOTIFICACIÓN DE REAGENDAMIENTO
            if ($appointment->email_sent == false && $appointment->isDirty(['date', 'start_time'])) {
                $originalDate = $appointment->getOriginal('date');
                $originalStartTime = $appointment->getOriginal('start_time');
                
                // 🔒 CORRECCIÓN: Manejo seguro de strings (no Carbon)
                $dateStr = $originalDate instanceof Carbon ? $originalDate->format('Y-m-d') : (string) $originalDate;
                $timeStr = $originalStartTime instanceof Carbon ? $originalStartTime->format('H:i') : substr((string) $originalStartTime, 0, 5);
                
                $previousDateTime = Carbon::parse("{$dateStr} {$timeStr}")->format('d/m/Y H:i');
                
                // 🔒 CORRECCIÓN: afterCommit() para que el email se envíe DESPUÉS del commit
                event(new AppointmentRescheduled($appointment, $previousDateTime))->afterCommit();
                
                // 🔒 CORRECCIÓN: Usar update() explícito en lugar de saveQuietly()
                // Esto evita el riesgo de loop infinito si las condiciones cambian
                Appointment::where('id', $appointment->id)->update(['email_sent' => true]);
                
                Log::info('AppointmentObserver: Cita reagendada, notificación enviada', [
                    'appointment_id' => $appointment->id,
                    'previous_datetime' => $previousDateTime,
                ]);
            }

            // 🔥 INVALIDAR CACHÉ DE DISPONIBILIDAD
            // Crucial: al cancelar/reagendar una cita, el turno debe reflejarse inmediatamente
            $this->invalidateCacheForAppointment($appointment);
        } catch (Throwable $e) {
            Log::error('AppointmentObserver: Error en updated', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Invalida el caché de disponibilidad para la sede de la cita.
     * 🔒 CRÍTICO: Sin esto, los turnos cancelados/reagendados siguen apareciendo como ocupados.
     */
    private function invalidateCacheForAppointment(Appointment $appointment): void
    {
        try {
            $addressId = $appointment->address_id;
            $doctorId = $appointment->doctor_id;
            $clinicId = $appointment->clinic_id;

            if ($addressId && $doctorId) {
                $this->cacheRepo->invalidateAvailabilityCache(
                    $clinicId,
                    [$doctorId],
                    $addressId
                );
                
                Log::info('AppointmentObserver: Caché invalidado', [
                    'appointment_id' => $appointment->id,
                    'address_id' => $addressId,
                    'doctor_id' => $doctorId,
                ]);
            }
        } catch (Throwable $e) {
            Log::error('AppointmentObserver: Error al invalidar caché', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Obtiene la configuración (clínica o doctor) de forma segura.
     */
    private function getSettings(Appointment $appointment)
    {
        if ($appointment->clinic_id) {
            return ClinicSetting::where('clinic_id', $appointment->clinic_id)->first();
        }
        
        return DoctorSetting::where('doctor_id', $appointment->doctor_id)->first();
    }
}