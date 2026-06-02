<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ZoomService
{
    protected $accountId;
    protected $clientId;
    protected $clientSecret;
    protected $apiBaseUrl = 'https://api.zoom.us/v2';
    protected $hostEmail;

    public function __construct()
    {
        // Usamos config() si ya están mapeados, o un fallback directo en caso de caché en AWS
        $this->accountId    = config('services.zoom.account_id');
        $this->clientId     = config('services.zoom.client_id');
        $this->clientSecret = config('services.zoom.client_secret');
        $this->hostEmail    = config('services.zoom.host_email');
    }

    /**
     * Obtiene el token de acceso Server-to-Server OAuth de Zoom
     */
    private function getAccessToken()
    {
        if (Cache::has('zoom_s2s_access_token')) {
            return Cache::get('zoom_s2s_access_token');
        }

        $url = "https://zoom.us/oauth/token";

        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}")
            ])->post($url, [
                'grant_type' => 'account_credentials',
                'account_id' => $this->accountId
            ]);

        if ($response->failed()) {
            Log::error('ZoomService Auth Error: Falló la obtención del token.', [
                'status' => $response->status(),
                'body'   => $response->body()
            ]);
            return null;
        }

        $data = $response->json();
        $token = $data['access_token'] ?? null;

        if ($token) {
            Cache::put('zoom_s2s_access_token', $token, now()->addMinutes(55));
        }

        return $token;
    }

    /**
     * Envía la solicitud a Zoom para crear una nueva videollamada única
     * Configurada con contraseña obligatoria para compatibilidad con el SDK.
     */
    public function createMeeting($topic, $startDateTime, $durationMinutes)
    {
        $token = $this->getAccessToken();

        if (!$token) {
            Log::error('ZoomService Error: No se pudo proceder sin un token válido.', []);
            return null;
        }

        $url = "{$this->apiBaseUrl}/users/{$this->hostEmail}/meetings";

        $response = Http::withToken($token)->post($url, [
            'topic'      => $topic,
            'type'       => 2, 
            'start_time' => $startDateTime,
            'duration'   => $durationMinutes,
            'timezone'   => config('app.timezone', 'America/Bogota'),
            'settings'   => [
                'host_video'         => true,
                'participant_video'  => true,
                'join_before_host'   => false, 
                'jbh_custom_minutes' => 0, 
                'mute_upon_entry'    => false,
                'waiting_room'       => true, 
                'meeting_authentication' => false // Evita que pida login de cuentas Zoom personales
            ]
        ]);

        if ($response->status() !== 201) {
            Log::error('ZoomService Meeting Error: La API no devolvió el código 201.', [
                'status' => $response->status(),
                'body'   => $response->body()
            ]);
            return null;
        }

        $data = $response->json();

        // Verificación estricta incluyendo la llave 'password' que exige el SDK
        if (!is_array($data) || !isset($data['id']) || !isset($data['start_url']) || !isset($data['join_url'])) {
            Log::error('ZoomService Payload Error: El JSON no contiene las llaves requeridas.', [
                'payload' => is_array($data) ? $data : 'Formato corrupto'
            ]);
            return null;
        }

        return [
            'meeting_id'   => $data['id'],
            'password'     => $data['password'] ?? '', // Almacenamos la clave autogenerada de la sala
            'url_partner'   => $data['start_url'], 
            'url_patient' => $data['join_url'],  
        ];
    }

    /**
     * Genera la firma segura (JWT) requerida por el SDK de Zoom en el Frontend
     * 
     * @param string|int $meetingNumber ID de la reunión de Zoom
     * @param int $role 0 para Paciente (Asistente), 1 para Médico (Anfitrión)
     * @return string
     */
    public function generateSdkSignature($meetingNumber, $role = 0)
    {
        $iat = time() - 30;
        $exp = $iat + 7200; // Válida por 2 horas de consulta

        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        
        $payload = json_encode([
            'sdkKey'   => $this->clientId,
            'mn'       => $meetingNumber,
            'role'     => $role,
            'iat'      => $iat,
            'exp'      => $exp,
            'tokenExp' => $exp
        ]);

        // Algoritmo manual Base64Url compatible con JWT estándar
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->clientSecret, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Elimina una reunión existente en la API de Zoom
     */
    public function deleteMeeting($meetingId)
    {
        $token = $this->getAccessToken();

        if (!$token || !$meetingId) {
            return false;
        }

        $url = "{$this->apiBaseUrl}/meetings/{$meetingId}";

        $response = Http::withToken($token)->delete($url);

        if ($response->failed()) {
            Log::error('ZoomService Delete Error: No se pudo eliminar la reunión.', [
                'meeting_id' => $meetingId,
                'status'     => $response->status(),
                'body'       => $response->body()
            ]);
            return false;
        }

        return true;
    }

    /**
     * Fuerza el cierre de una reunión en vivo en Zoom.
     * @param string|int $meetingId
     */
    public function endMeeting($meetingId)
    {
        $token = $this->getAccessToken();
        if (!$token || !$meetingId) return false;

        // Endpoint oficial para modificar el estado de una reunión en vivo
        $url = "{$this->apiBaseUrl}/meetings/{$meetingId}/status";

        $response = Http::withToken($token)->put($url, [
            'action' => 'end' // Fuerza la expulsión de todos y cierra la sala
        ]);

        return $response->successful();
    }
}
