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
use Exception;

class SyncZoomMeetingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Intentos máximos automáticos de Laravel Queue si hay fallos de red o de API
    public $tries = 3;
    
    protected $appointmentId;

    public function __construct($appointmentId)
    {
        $this->appointmentId = $appointmentId;
    }

    public function handle(ZoomService $zoomService)
    {
        $appointment = Appointment::find($this->appointmentId);
        
        // Si la cita fue borrada o cancelada entre tanto, salimos limpiamente
        if (!$appointment) {
            return;
        }

        // Registrar el intento actual en tu tabla nativa de contingencia
        DB::table('zoom_creation_failures')->updateOrInsert(
            ['appointment_id' => $this->appointmentId],
            ['updated_at' => now()]
        );
        DB::table('zoom_creation_failures')->where('appointment_id', $this->appointmentId)->increment('attempts');

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
                if ($result['action'] === 'recreated') {
                    $appointment->update([
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
            // Guardar el log del error directo en tu tabla para auditoría administrativa del SaaS
            DB::table('zoom_creation_failures')
                ->where('appointment_id', $this->appointmentId)
                ->update([
                    'status' => 'failed',
                    'last_error' => substr($e->getMessage(), 0, 500),
                    'updated_at' => now()
                ]);

            // Re-lanzar la excepción para que Laravel aplique los reintentos (tries) programados
            throw $e;
        }
    }
}
