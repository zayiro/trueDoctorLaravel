<?php

namespace App\Jobs;

use App\Models\ZoomCreationFailure; // <-- IMPORTANTE: Ruta de tu modelo nuevo
use App\Services\ZoomService;       // <-- IMPORTANTE: Ajusta esta ruta si tu servicio está en otra carpeta
use App\Mail\PatientZoomLinkMail;   // <-- IMPORTANTE: Tu clase de correo para el paciente
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessPendingZoomMeetings implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ZoomService $zoomService): void
    {
        // 1. Buscamos los fallidos pendientes que tengan menos de 5 intentos
        // Cargamos la relación 'appointment' de inmediato para no saturar la base de datos (Eager Loading)
        $fallidos = ZoomCreationFailure::where('status', 'pending')
            ->where('attempts', '<', 5)
            ->with('appointment') 
            ->get();

        foreach ($fallidos as $fallo) {
            $appointment = $fallo->appointment;
            
            // Si por alguna razón la cita ya no existe, saltamos al siguiente
            if (!$appointment) {
                $fallo->update(['status' => 'failed', 'last_error' => '[' . now()->toDateTimeString() . '] Cita no encontrada.']);
                continue;
            }

            // Incrementamos el contador de intentos antes de lanzar la petición
            $fallo->increment('attempts');

            try {
                // 2. Preparamos los parámetros reales para tu ZoomService
                $topic = "Teleconferencia Médica - Ref: " . $appointment->reference; 
                $startDateTime = $appointment->scheduled_at; // Ajusta 'scheduled_at' por el nombre real de tu columna de fecha/hora
                $duration = $appointment->duration ?? 45;    // Si no tiene duración, por defecto 45 minutos

                // Intentamos crear la reunión en Zoom
                $zoomMeeting = $zoomService->createMeeting($topic, $startDateTime, $duration);

                if ($zoomMeeting) {
                    // 3. SI CONECTA CON ZOOM: Actualizamos la tabla appointments con los datos encriptados
                    $appointment->update([
                        'zoom_meeting_id'       => $zoomMeeting['meeting_id'],                    
                        'zoom_start_url'        => Crypt::encryptString($zoomMeeting['url_partner']),   
                        'meeting_link'          => Crypt::encryptString($zoomMeeting['url_patient']), 
                        'meeting_link_password' => Crypt::encryptString($zoomMeeting['password']),                
                    ]);

                    // 4. Enviamos el correo asíncrono al paciente (Usamos queue para no frenar el bucle)
                    // Ajusta '$appointment->email' o '$appointment->patient->email' según tu estructura
                    Mail::to($appointment->email)->queue(
                        new PatientZoomLinkMail($appointment, $zoomMeeting['url_patient'], $zoomMeeting['password'])
                    );

                    // 5. Marcamos el registro de fallo como completado con éxito
                    $fallo->update([
                        'status' => 'completed',
                        'last_error' => '[' . now()->toDateTimeString() . '] Enlace creado y enviado con éxito en reintento #' . $fallo->attempts
                    ]);

                } else {
                    // Si el servicio responde falso o nulo sin lanzar excepción
                    $fallo->update([
                        'last_error' => '[' . now()->toDateTimeString() . '] ZoomService retornó una respuesta vacía.'
                    ]);
                }

            } catch (Exception $e) {
                // Si la API de Zoom se cae, da error de token, etc., capturamos el mensaje exacto
                Log::error("Error en Background reintentando Zoom (Ref: {$appointment->reference}): " . $e->getMessage());

                $fallo->update([
                    'last_error' => '[' . now()->toDateTimeString() . '] Excepción: ' . $e->getMessage()
                ]);
            }

            // Si ya alcanzó el límite máximo de 5 intentos fallidos, cerramos el caso como fallido definitivo
            if ($fallo->attempts >= 5 && $fallo->status !== 'completed') {
                $fallo->update(['status' => 'failed']);
                Log::alert("La cita con referencia {$appointment->reference} superó los 5 intentos de generación de Zoom y requiere atención manual.");
            }
        }
    }
}
