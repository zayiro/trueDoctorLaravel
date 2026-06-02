<?php

namespace App\Listeners;

use App\Events\AppointmentCancelled;
use App\Mail\AppointmentCancelledMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\ZoomCreationFailure;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\ZoomService;

class SendCancellationEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Instancia del servicio de Zoom.
     */
    protected $zoomService;

    /**
     * El número de veces que se puede reintentar el trabajo si falla el servidor de correos.
     */
    public $tries = 3;

    /**
     * El número de segundos que espera la cola antes de reintentar.
     */
    public $backoff = 60;

    /**
     * Create the event listener e inyectar el servicio de Zoom de forma automática.
     */
    public function __construct(ZoomService $zoomService)
    {
        $this->zoomService = $zoomService;
    }

    /**
     * Handle the event.
     */
    public function handle(AppointmentCancelled $event): void
    {
        // 1. Forzar la carga de relaciones para evitar consultas N+1 en segundo plano
        // Cargamos al paciente y al doctor con sus respectivos usuarios
        $appointment = $event->appointment->load([
            'doctor.user',
            'patient.user',
            'clinic',
            'service',
            'address'
        ]);

        // ==========================================
        // FRENTE 1: ELIMINACIÓN DE REUNIÓN EN ZOOM VIA ZOOMSERVICE
        // ==========================================
        if ($appointment->zoom_meeting_id) {
            try {
                // Ejecutamos tu método encapsulado
                $isDeleted = $this->zoomService->deleteMeeting($appointment->zoom_meeting_id);

                if (!$isDeleted) {
                    // Si retorna false (por error en respuesta o falta de token), registramos la contingencia
                    $this->registerZoomFailure($appointment->id, 'ZoomService retornó FALSE al intentar eliminar la reunión, desde el listener de cancelacion');
                } else {
                    Log::info("Reunión de Zoom {$appointment->zoom_meeting_id} removida exitosamente mediante ZoomService.");
                }
            } catch (\Exception $e) {
                Log::error("Fallo crítico al invocar ZoomService para la cita {$appointment->reference}: " . $e->getMessage());
                $this->registerZoomFailure($appointment->id, $e->getMessage());
            }
        }

        try {
            // 2. ENVIAR AL DOCTOR
            if ($appointment->doctor?->user?->email) {
                Mail::to($appointment->doctor->user->email)
                    ->send(new AppointmentCancelledMail($appointment));
            }

            // 3. ENVIAR AL PACIENTE
            // Accedemos a la relación 'patient', luego a su 'user' y extraemos el 'email'
            if ($appointment->patient?->user?->email) {
                Mail::to($appointment->patient->user->email)
                    ->send(new AppointmentCancelledMail($appointment));
            }

        } catch (\Exception $e) {
            Log::error("Error en cola de envío de correos para la cita {$appointment->reference}: " . $e->getMessage());
            
            // Lanzamos la excepción para que el motor de colas intente de nuevo según tus propiedades $tries
            throw $e;
        }        
    }

    /**
     * Inserta o incrementa los intentos en tu tabla de contingencia zoom_creation_failures
     */
    private function registerZoomFailure(int $appointmentId, string $errorMessage)
    {
        ZoomCreationFailure::updateOrCreate(
            ['appointment_id' => $appointmentId],
            [
                'attempts' => DB::raw('attempts + 1'),
                'status' => 'failed',
                'last_error' => substr($errorMessage, 0, 500),                
                'updated_at' => now()
            ]
        );
    }
}
