<?php

namespace App\Listeners;

use App\Events\AppointmentCompleted;
use App\Services\ZoomService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Cierra la reunión de Zoom cuando una cita se completa.
 *
 * Importante: todas las reuniones se crean con el mismo usuario anfitrión y Zoom no permite que ese usuario
 * tenga varias reuniones simultáneas; una reunión que queda abierta puede impedir que otro doctor inicie la suya.
 *
 * Laravel lo descubre solo (app/Listeners con handle() tipado): no hay que registrarlo en AppServiceProvider.
 */
class EndZoomMeetingOnAppointmentCompleted implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;

    public $backoff = 30;

    public function __construct(protected ZoomService $zoomService)
    {
    }

    public function handle(AppointmentCompleted $event): void
    {
        $meetingId = $event->appointment->zoom_meeting_id;

        if (!$meetingId) {
            return;
        }

        // endMeeting() devuelve false si la reunión ya no está en curso; no es un error que merezca reintentos
        $closed = $this->zoomService->endMeeting($meetingId);

        Log::info("EndZoomMeetingOnAppointmentCompleted: cita {$event->appointment->id}, reunión {$meetingId} " . ($closed ? 'cerrada' : 'no estaba en curso'));
    }
}