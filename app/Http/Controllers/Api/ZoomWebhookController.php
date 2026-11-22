<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\Appointment;
use Illuminate\Support\Facades\Log;

class ZoomWebhookController extends Controller
{
    /**
     * Zoom firma cada evento: x-zm-signature = "v0=" . HMAC_SHA256("v0:{timestamp}:{cuerpo crudo}", secret token).
     * Se rechaza si falta la firma, no coincide, o el timestamp se aleja más de 5 minutos (evita repetir llamadas viejas).
     */
    private function hasValidZoomSignature(Request $request): bool
    {
        $secret    = (string) config('services.zoom.webhook_secret_token');
        $timestamp = (string) $request->header('x-zm-request-timestamp', '');
        $signature = (string) $request->header('x-zm-signature', '');

        if ($secret === '' || $timestamp === '' || $signature === '' || !ctype_digit($timestamp)) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $expected = 'v0=' . hash_hmac('sha256', "v0:{$timestamp}:" . $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    public function handle(Request $request)
    {
        // 🟢 BLINDAJE: Si entran por navegador (GET), retornamos un mensaje amigable en vez de un error
        if ($request->isMethod('get')) {
            return response()->json([
                'status' => 'online',
                'message' => 'El endpoint de telemedicina de OpenDoctor está activo. Solo se permiten peticiones POST de Zoom.'
            ], 200);
        }
        
        // 🟢 1. VALIDACIÓN CRC (Esto procesa el POST de validación de Zoom)
        if ($request->input('event') === 'endpoint.url_validation') {
            $plainToken = $request->input('payload.plainToken');
            
            // ⚠️ COPIA AQUÍ TU SECRET TOKEN REAL DE LA PESTAÑA FEATURE DE ZOOM:
            $secretToken = config('services.zoom.webhook_secret_token');

            // Generamos la encriptación SHA256 exigida por Zoom
            $encryptedToken = hash_hmac('sha256', $plainToken, $secretToken);

            // Retornamos la estructura JSON cruda exacta que Zoom espera para validar
            return response()->json([
                'plainToken' => $plainToken,
                'encryptedToken' => $encryptedToken
            ], 200);
        }

        // Seguridad: todo evento (salvo el desafío de validación de arriba) debe venir firmado por Zoom.
        if (!$this->hasValidZoomSignature($request)) {
            Log::warning('OpenDoctor Webhook: evento de Zoom rechazado por firma inválida o ausente.', ['ip' => $request->ip()]);

            return response()->json(['status' => 'unauthorized'], 401);
        }

        // Sala iniciada: Zoom avisa cuando el anfitrión inicia la reunión o entra un participante.
        // Solo marca zoom_started (evidencia de que la consulta ocurrió): no cambia el estado de la cita ni envía correos.
        if (in_array($request->input('event'), ['meeting.started', 'meeting.participant_joined'], true)) {
            $startedMeetingId = $request->input('payload.object.id');

            if ($startedMeetingId) {
                $marked = Appointment::where('zoom_meeting_id', (string) $startedMeetingId)
                    ->where(fn ($q) => $q->where('zoom_started', false)->orWhereNull('zoom_started'))
                    ->update(['zoom_started' => true]);

                if ($marked) {
                    Log::info("OpenDoctor Webhook: sala de Zoom iniciada (reunión {$startedMeetingId}).");
                }
            }

            return response()->json(['status' => 'success'], 200);
        }

        // 🔵 2. LOGICA NORMAL PARA PROCESAR EL CIERRE DE REUNIONES
        if ($request->input('event') === 'meeting.ended') {
            $payload = $request->input('payload.object');
            $meetingId = $payload['id'] ?? null;

            if ($meetingId) {
                $appointment = Appointment::where('zoom_meeting_id', $meetingId)->first();
                // Solo se completa una cita confirmada: si ya está «no asistió», cancelada, expirada o completada, el fin de la reunión no la cambia.
                $currentStatus = $appointment?->status instanceof \BackedEnum ? $appointment->status->value : (string) ($appointment?->status ?? '');
                if ($appointment && $currentStatus === 'confirmed') {
                    $appointment->update(['status' => 'completed']);
                    Log::info("OpenDoctor Webhook: Cita Ref: {$appointment->reference} completada.");
                }
            }
        }

        return response()->json(['status' => 'success'], 200);
    }
}
