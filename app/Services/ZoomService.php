<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZoomService
{
    protected $accountId;
    protected $clientId;
    protected $clientSecret;
    protected $baseUrl = 'https://zoom.us';

    public function __construct()
    {
        // Extrae los valores correctamente desde el archivo .env
        // (Por seguridad, ya no escribas tus tokens directamente aquí)
        $this->accountId = env('ZOOM_ACCOUNT_ID');
        $this->clientId = env('ZOOM_CLIENT_ID');
        $this->clientSecret = env('ZOOM_CLIENT_SECRET');
    }

    /**
     * Obtiene el token de acceso Server-to-Server OAuth de Zoom
     */
    private function getAccessToken()
    {
        // Endpoint oficial de Zoom para flujo Server-to-Server
        $url = "https://zoom.us";

        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}")
            ])->post($url, [
                'grant_type' => 'account_credentials',
                'account_id' => $this->accountId
            ]);

        if ($response->failed()) {
            Log::error('Error de autenticación con la API de Zoom: ' . $response->body());
            return null;
        }

        return $response->json()['access_token'] ?? null;
    }

    /**
     * Envía la solicitud a Zoom para crear una nueva videollamada única
     * 
     * @param string $topic Nombre de la cita (ej: Consulta con Dr. House)
     * @param string $startDateTime Fecha y hora en formato ISO 8601 (YYYY-MM-DDTHH:MM:SS)
     * @param int $durationMinutes Duración del servicio en minutos
     * @return array|null Retorna un arreglo con las URLs de doctor y paciente, o null si falla
     */
    public function createMeeting($topic, $startDateTime, $durationMinutes)
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return null;
        }

        // Endpoint correcto para crear reuniones al usuario dueño de la App
        $url = "{$this->baseUrl}/users/me/meetings";

        $response = Http::withToken($token)->post($url, [
            'topic'      => $topic,
            'type'       => 2, // 2 = Reunión agendada
            'start_time' => $startDateTime,
            'duration'   => $durationMinutes,
            'timezone'   => config('app.timezone', 'America/Bogota'),
            'settings'   => [
                'host_video'         => true,
                'participant_video'  => true,
                'join_before_host'   => false, 
                'jbh_custom_minutes' => 0, 
                'mute_upon_entry'    => false,
                'waiting_room'       => true, // Seguridad 1 a 1: El doctor admite manualmente al paciente
            ]
        ]);

        if ($response->failed()) {
            Log::error('Fallo al crear la reunión en la API de Zoom: ' . $response->body());
            return null;
        }

        $data = $response->json();

        // Retornamos ambos enlaces esenciales para el flujo de telemedicina
        return [
            'meeting_id'  => $data['id'] ?? null,
            'url_doctor'  => $data['start_url'] ?? null, // Enlace de inicio (Anfitrión)
            'url_paciente'=> $data['join_url'] ?? null,  // Enlace de invitado
        ];
    }

    /**
     * Elimina una reunión existente en la API de Zoom
     * 
     * @param string|int $meetingId ID de la reunión de Zoom
     * @return bool Retorna true si se eliminó con éxito o false si falló
     */
    public function deleteMeeting($meetingId)
    {
        $token = $this->getAccessToken();

        if (!$token || !$meetingId) {
            return false;
        }

        // Endpoint oficial de Zoom para eliminar reuniones
        $url = "{$this->baseUrl}/meetings/{$meetingId}";

        $response = Http::withToken($token)->delete($url);

        if ($response->failed()) {
            Log::error("Fallo al eliminar la reunión {$meetingId} en la API de Zoom: " . $response->body());
            return false;
        }

        return true;
    }
}
