<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\ZoomService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\MaxAttemptsExceededException;
use Exception;
use Throwable;

class SyncZoomMeetingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Intentos máximos automáticos de Laravel Queue si hay fallos de red o de API.
     */
    public $tries = 3;
    
    /**
     * El número de segundos que el Job puede ejecutarse antes de cerrarse por Timeout.
     * Si la API de Zoom tarda más de 30 segundos, Laravel abortará el Job de inmediato.
     */
    public $timeout = 30; 

    /**
     * Tiempo de espera exponencial entre reintentos (en segundos).
     * Primer fallo reintenta a los 5s, segundo fallo a los 15s.
     */
    public $backoff = [5, 15];

    protected $appointmentId;
    
    public function __construct($appointmentId)
    {
        $this->appointmentId = $appointmentId;
    }

    public function handle(ZoomService $zoomService)
    {
        $appointment = Appointment::find($this->appointmentId);
        
        // Si la cita fue borrada entre tanto, salimos limpiamente sin arrojar error
        if (!$appointment) {
            return;
        }

        // Registrar e incrementar el intento en una sola transacción atómica eficiente
        DB::table('zoom_creation_failures')->updateOrInsert(
            ['appointment_id' => $this->appointmentId],
            [
                'attempts' => DB::raw('attempts + 1'),
                'updated_at' => now(),
                'created_at' => now()
            ]
        );

        try {
            // Formatear los datos exactos requeridos por tu ZoomService existente
            $startDateTime = $appointment->date . 'T' . $appointment->start_time;
            $topic = "Cita Médica Ref: {$appointment->reference}";

            // 🔄 Invocar tu método del servicio para actualizar el cronograma en la API de Zoom
            $result = $zoomService->updateMeeting(
                $appointment->zoom_meeting_id,
                $topic,
                $startDateTime,
                $appointment->duration
            );

            if ($result) {
                // Si la reunión vieja no existía (404) y tu servicio gatilló el Plan B (recreated)
                if (isset($result['action']) && $result['action'] === 'recreated') {
                    
                    // 🚀 CRÍTICO: Usamos updateQuietly para guardar los nuevos links en la BD
                    // SIN volver a detonar el AppointmentObserver (Evita el bucle infinito del Timeout)
                    $appointment->updateQuietly([
                        'zoom_meeting_id'       => $result['meeting_id'],
                        'meeting_link'          => $result['url_patient'],
                        'zoom_start_url'        => $result['url_partner'],
                        'meeting_link_password' => $result['password'],
                    ]);
                }

                // Sincronización exitosa: Marcamos la contingencia como completada
                DB::table('zoom_creation_failures')
                    ->where('appointment_id', $this->appointmentId)
                    ->update([
                        'status' => 'completed',
                        'last_error' => null,
                        'updated_at' => now()
                    ]);
            } else {
                throw new Exception("El ZoomService retornó una respuesta nula durante el reagendamiento.");
            }

        } catch (Exception $e) {
            $this->logJobFailure($e->getMessage());
            throw $e;
        }
    }

    /**
     * Ciclo de vida de Laravel Queue: Se ejecuta si el Job falla definitivamente
     * o si es interrumpido abruptamente por el TIMEOUT del Worker.
     */
    public function failed(Throwable $exception): void
    {
        $errorMessage = $exception instanceof MaxAttemptsExceededException
            ? 'El Job superó el tiempo límite de espera (Timeout de 30s) esperando respuesta de la API de Zoom.'
            : $exception->getMessage();

        $this->logJobFailure($errorMessage, 'failed');
    }

    /**
     * Centraliza el volcado de logs de errores en tu tabla de contingencia.
     */
    protected function logJobFailure(string $message, string $status = 'pending'): void
    {
        DB::table('zoom_creation_failures')
            ->where('appointment_id', $this->appointmentId)
            ->update([
                'status' => $status, 
                'last_error' => substr($message, 0, 500),
                'updated_at' => now()
            ]);
    }
}
