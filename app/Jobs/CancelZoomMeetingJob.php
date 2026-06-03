<?php

namespace App\Jobs;

use App\Services\ZoomService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;

class CancelZoomMeetingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Intentos máximos automáticos si la API de Zoom está lenta o caída temporalmente
    public $tries = 3;

    protected $zoomMeetingId;

    /**
     * Crea una nueva instancia del Job con el ID de la sala externa.
     */
    public function __construct(string $zoomMeetingId)
    {
        $this->zoomMeetingId = $zoomMeetingId;
    }

    /**
     * Ejecuta el trabajo de borrado de forma asíncrona.
     */
    public function handle(ZoomService $zoomService)
    {
        if (!$this->zoomMeetingId) {
            return;
        }

        // 🔄 Consumimos directamente el método deleteMeeting de tu clase ZoomService
        $success = $zoomService->deleteMeeting($this->zoomMeetingId);

        if (!$success) {
            // Si tu servicio reportó un fallo y guardó el Log, lanzamos la excepción para el reintento de la cola
            throw new Exception("Fallo asíncrono al intentar eliminar la reunión ID: {$this->zoomMeetingId} en Zoom.");
        }
    }
}
