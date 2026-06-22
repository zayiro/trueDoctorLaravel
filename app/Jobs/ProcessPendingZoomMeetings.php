<?php

namespace App\Jobs;

use App\Models\ZoomCreationFailure;
use App\Services\ZoomService;
use App\Mail\PatientZoomLinkMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessPendingZoomMeetings implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function handle(ZoomService $zoomService): void
    {
        $fallidos = ZoomCreationFailure::where('status', 'pending')
            ->where('attempts', '<', 5)
            ->with('appointment')
            ->get();

        foreach ($fallidos as $fallo) {

            // RESERVA ATÓMICA: marcamos el registro como "processing" ANTES de
            // tocar la API de Zoom. Si otra ejecución (solapada o de otro worker)
            // ya lo tomó, este update() afecta 0 filas y saltamos el registro.
            // Esto cierra el hueco de tiempo en el que dos ejecuciones podían ver
            // el mismo registro como 'pending' a la vez.
            $reserved = ZoomCreationFailure::where('id', $fallo->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'processing',
                    'attempts' => $fallo->attempts + 1,
                ]);

            if ($reserved === 0) {
                // Otra ejecución ya está procesando (o procesó) este registro.
                continue;
            }

            $fallo->refresh();
            $appointment = $fallo->appointment;

            if (! $appointment) {
                $fallo->update([
                    'status' => 'failed',
                    'last_error' => '[' . now()->toDateTimeString() . '] Cita no encontrada.',
                ]);
                continue;
            }

            try {
                $topic = "Teleconferencia Médica - Ref: " . $appointment->reference;
                $startDateTime = $appointment->scheduled_at;
                $duration = $appointment->duration ?? 45;

                $zoomMeeting = $zoomService->createMeeting($topic, $startDateTime, $duration);

                if ($zoomMeeting) {
                    $appointment->update([
                        'zoom_meeting_id'       => $zoomMeeting['meeting_id'],
                        'zoom_start_url'        => Crypt::encryptString($zoomMeeting['url_partner']),
                        'meeting_link'          => Crypt::encryptString($zoomMeeting['url_patient']),
                        'meeting_link_password' => Crypt::encryptString($zoomMeeting['password']),
                    ]);

                    Mail::to($appointment->email)->queue(
                        new PatientZoomLinkMail($appointment, $zoomMeeting['url_patient'], $zoomMeeting['password'])
                    );

                    $fallo->update([
                        'status' => 'completed',
                        'last_error' => '[' . now()->toDateTimeString() . '] Enlace creado y enviado con éxito en reintento #' . $fallo->attempts,
                    ]);
                } else {
                    // Sin éxito pero sin excepción: regresa a 'pending' para reintentar
                    // en la próxima corrida (si no superó el máximo de intentos).
                    $fallo->update([
                        'status' => 'pending',
                        'last_error' => '[' . now()->toDateTimeString() . '] ZoomService retornó una respuesta vacía.',
                    ]);
                }

            } catch (Exception $e) {
                Log::error("Error en Background reintentando Zoom (Ref: {$appointment->reference}): " . $e->getMessage());

                $fallo->update([
                    'status' => 'pending', // vuelve a pending para reintentar después
                    'last_error' => '[' . now()->toDateTimeString() . '] Excepción: ' . $e->getMessage(),
                ]);
            }

            if ($fallo->attempts >= 5 && $fallo->status !== 'completed') {
                $fallo->update(['status' => 'failed']);
                Log::alert("La cita con referencia {$appointment->reference} superó los 5 intentos de generación de Zoom y requiere atención manual.");
            }
        }
    }
}
