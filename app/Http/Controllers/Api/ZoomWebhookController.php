<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Appointment;

class ZoomWebhookController extends Controller
{
    /**
     * Procesa las notificaciones y webhooks enviados por Zoom
     */
    public function handleNotification(Request $request)
    {
        $payload = $request->all();
        $event = $request->input('event');

        Log::info("Webhook de Zoom recibido. Evento: {$event}", $payload);

        // 👇 REQUISITO OBLIGATORIO DE ZOOM: Validación del Endpoint al activar el Webhook
        if ($event === 'endpoint.url_validation') {
            $plainToken = $payload['data']['plainToken'] ?? '';
            $secretToken = env('ZOOM_SECRET_TOKEN', 'McBdMTGJToCf0EFgQ5JQxQ'); // El token que tenías en tus comentarios

            // Encriptamos el token usando HMAC SHA256 como exige Zoom
            $hash = hash_hmac('sha256', $plainToken, $secretToken);

            return response()->json([
                'plainToken' => $plainToken,
                'encryptedToken' => $hash
            ], 200);
        }

        // Procesar otros eventos de negocio (Ejemplo: Reunión eliminada desde la App de Zoom)
        switch ($event) {
            case 'meeting.deleted':
                $meetingId = $payload['data']['object']['id'] ?? null;
                if ($meetingId) {
                    // Si el doctor borra la cita desde su Zoom personal, la cancelamos en nuestro SaaS
                    Appointment::where('zoom_meeting_id', $meetingId)->update([
                        'status' => 'cancelled'
                    ]);
                    Log::info("Cita cancelada localmente debido a eliminación en Zoom de la reunión: {$meetingId}");
                }
                break;

            case 'meeting.participant_joined':
                // Aquí puedes meter la lógica si deseas contar participantes (Seguridad Doctor-Paciente)
                break;
        }

        // Siempre responder con un estado 200 para confirmar la recepción a Zoom
        return response()->json(['status' => 'success'], 200);
    }
}
