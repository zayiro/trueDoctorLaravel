<?php

namespace App\Jobs;

use App\Services\ZoomService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Elimina de Zoom la reunión de una cita cancelada.
 *
 * Lo despacha AppointmentObserver (con afterCommit) cuando la cita pasa a "cancelled". El observer limpia
 * las columnas de Zoom de la cita, por eso el job recibe el ID de la reunión (y el ID de la cita solo para logs).
 */
class CancelZoomMeetingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Intentos máximos si la API de Zoom está lenta o caída temporalmente
    public $tries = 3;

    // Segundos máximos por intento (la API de Zoom no debe bloquear el worker)
    public $timeout = 30;

    // Espera entre reintentos: 10 s tras el primer fallo y 60 s tras el segundo
    public $backoff = [10, 60];

    protected string $zoomMeetingId;

    protected ?int $appointmentId = null;

    /**
     * @param string|int $zoomMeetingId ID de la reunión en Zoom (la columna puede llegar como int o string)
     * @param int|null   $appointmentId Solo para trazabilidad en los logs
     */
    public function __construct(string|int $zoomMeetingId, ?int $appointmentId = null)
    {
        $this->zoomMeetingId = (string) $zoomMeetingId;
        $this->appointmentId = $appointmentId;
    }

    /**
     * Ejecuta el borrado de forma asíncrona.
     */
    public function handle(ZoomService $zoomService): void
    {
        if ($this->zoomMeetingId === '') {
            return;
        }

        // deleteMeeting() considera éxito una reunión que ya no existe (404), así que el borrado es idempotente
        // y los reintentos solo ocurren con fallos reales (token, red, Zoom caído).
        if (!$zoomService->deleteMeeting($this->zoomMeetingId)) {
            throw new Exception("Fallo al intentar eliminar la reunión de Zoom {$this->zoomMeetingId} (cita {$this->appointmentId}).");
        }

        Log::info("CancelZoomMeetingJob: reunión de Zoom {$this->zoomMeetingId} eliminada (cita {$this->appointmentId}).");
    }

    /**
     * Se ejecuta cuando se agotaron los reintentos. La cita ya no guarda el ID de la reunión,
     * así que este log es el único rastro para eliminarla a mano en Zoom.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('CancelZoomMeetingJob: no se pudo eliminar la reunión de Zoom tras todos los reintentos. Elimínala manualmente.', [
            'zoom_meeting_id' => $this->zoomMeetingId,
            'appointment_id'  => $this->appointmentId,
            'error'           => $exception->getMessage(),
        ]);
    }
}