<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\Appointment;
use Illuminate\Support\Facades\Log;

class ZoomWebhookController extends Controller
{
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
            $secretToken = env('ZOOM_WEBHOOK_SECRET_TOKEN', 'sN36U7zuSVaigcJL0FmUBw');
            //$secretToken = env('ZOOM_WEBHOOK_SECRET_TOKEN');

            // Generamos la encriptación SHA256 exigida por Zoom
            $encryptedToken = hash_hmac('sha256', $plainToken, $secretToken);

            // Retornamos la estructura JSON cruda exacta que Zoom espera para validar
            return response()->json([
                'plainToken' => $plainToken,
                'encryptedToken' => $encryptedToken
            ], 200);
        }

        // 🔵 2. LOGICA NORMAL PARA PROCESAR EL CIERRE DE REUNIONES
        if ($request->input('event') === 'meeting.ended') {
            $payload = $request->input('payload.object');
            $meetingId = $payload['id'] ?? null;

            if ($meetingId) {
                $appointment = Appointment::where('zoom_meeting_id', $meetingId)->first();
                if ($appointment && $appointment->status !== 'completed') {
                    $appointment->update(['status' => 'completed']);
                    Log::info("OpenDoctor Webhook: Cita Ref: {$appointment->reference} completada.");
                }
            }
        }

        return response()->json(['status' => 'success'], 200);
    }
}
