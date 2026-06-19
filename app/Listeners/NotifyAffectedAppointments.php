<?php

namespace App\Listeners;

use App\Events\DoctorUnavailabilityCreated;
use App\Models\AffectedAppointment;
use App\Models\Appointment;
use App\Notifications\AppointmentRescheduleRequiredNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotifyAffectedAppointments implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Tiempo de espera antes de reintentar (segundos).
     */
    public int $delay = 5;

    /**
     * Número máximo de intentos.
     */
    public int $tries = 3;

    /**
     * Maneja el evento de inasistencia creada.
     */
    public function handle(DoctorUnavailabilityCreated $event): void
    {
        try {
            DB::beginTransaction();

            $unavailability = $event->unavailability;
            $affectedAppointments = $event->affectedAppointments;

            // 1. REGISTRAR EN AUDITORÍA (Event Sourcing)
            foreach ($affectedAppointments as $appointment) {
                AffectedAppointment::create([
                    'unavailability_id' => $unavailability->id,
                    'appointment_id' => $appointment->id,
                    'patient_id' => $appointment->patient_id,
                    'doctor_id' => $unavailability->doctor_id,
                    'original_date' => $appointment->date,
                    'original_start_time' => $appointment->start_time,
                    'original_end_time' => $appointment->end_time,
                    'status' => 'pending_reschedule',
                    'notification_sent_at' => now(),
                ]);

                Log::info('Cita afectada registrada en auditoría', [
                    'appointment_id' => $appointment->id,
                    'unavailability_id' => $unavailability->id,
                    'patient_id' => $appointment->patient_id,
                ]);
            }

            // 2. NOTIFICAR A PACIENTES
            foreach ($affectedAppointments as $appointment) {
                if ($appointment->patient && $appointment->patient->user) {
                    $appointment->patient->user->notify(
                        new AppointmentRescheduleRequiredNotification($appointment, $unavailability)
                    );

                    Log::info('Notificación de reagendamiento enviada al paciente', [
                        'patient_id' => $appointment->patient_id,
                        'appointment_id' => $appointment->id,
                    ]);
                }
            }

            // 3. NOTIFICAR A CLÍNICAS (si aplica)
            if ($unavailability->address && $unavailability->address->clinic) {
                $clinic = $unavailability->address->clinic;
                $clinic->user->notify(
                    new ClinicAppointmentRescheduleNotification($unavailability, $affectedAppointments)
                );

                Log::info('Notificación de reagendamiento enviada a clínica', [
                    'clinic_id' => $clinic->id,
                    'unavailability_id' => $unavailability->id,
                    'affected_count' => $affectedAppointments->count(),
                ]);
            }

            DB::commit();

            Log::info('Evento DoctorUnavailabilityCreated procesado exitosamente', [
                'unavailability_id' => $unavailability->id,
                'affected_appointments_count' => $affectedAppointments->count(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al procesar DoctorUnavailabilityCreated', [
                'unavailability_id' => $event->unavailability->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Relanzar la excepción para que el job se reintente
            throw $e;
        }
    }

    /**
     * Maneja el fallo del job después de agotar los reintentos.
     */
    public function failed(DoctorUnavailabilityCreated $event, \Throwable $exception): void
    {
        Log::critical('Job NotifyAffectedAppointments falló después de reintentos', [
            'unavailability_id' => $event->unavailability->id,
            'error' => $exception->getMessage(),
        ]);

        // Aquí podrías enviar una alerta a administradores
    }
}
