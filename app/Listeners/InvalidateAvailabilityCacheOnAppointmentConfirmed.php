<?php

namespace App\Listeners;

use App\Events\AppointmentConfirmed;
use App\Services\AvailabilityService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * 🔄 LISTENER: Invalida caché de disponibilidad cuando se confirma una cita
 * 
 * Se ejecuta automáticamente cuando se dispara el evento AppointmentConfirmed
 */
class InvalidateAvailabilityCacheOnAppointmentConfirmed implements ShouldQueue
{
    /**
     * Instancia del servicio de disponibilidad
     */
    private AvailabilityService $availabilityService;

    /**
     * Constructor con inyección de dependencias
     */
    public function __construct(AvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    /**
     * Maneja el evento de confirmación de cita
     */
    public function handle(AppointmentConfirmed $event): void
    {
        try {
            $appointment = $event->appointment;

            // Invalidar caché para el doctor/clínica y sede específicos
            $this->availabilityService->invalidateAvailabilityCache(
                clinicId: $appointment->clinic_id,
                doctorIds: [$appointment->doctor_id],
                addressId: $appointment->address_id
            );

            Log::info('InvalidateAvailabilityCacheOnAppointmentConfirmed: Caché invalidado', [
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'clinic_id' => $appointment->clinic_id,
                'address_id' => $appointment->address_id
            ]);
        } catch (\Exception $e) {
            Log::error('InvalidateAvailabilityCacheOnAppointmentConfirmed: Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
