<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Appointment;
use Throwable;

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
     * 
     * 🔄 PATRÓN SAGA: Si falla, dispara compensación automática
     * @param string $topic
     * @param string $startDateTime
     * @param int $durationMinutes
     * @param Appointment|null $appointment Cita asociada para compensación automática
     * @return array|null
     */
    public function createMeeting($topic, $startDateTime, $durationMinutes, ?Appointment $appointment = null)
    {
        try {
            $token = $this->getAccessToken();

            if (!$token) {
                $errorMsg = 'No se pudo proceder sin un token válido.';
                Log::error('ZoomService Error: ' . $errorMsg, []);
                
                // Disparar compensación si hay cita asociada
                if ($appointment) {
                    ZoomSagaCompensation::compensateCreateMeetingFailure($appointment, $errorMsg);
                }
                
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
                $errorMsg = "La API no devolvió el código 201. Status: {$response->status()}";
                Log::error('ZoomService Meeting Error: ' . $errorMsg, [
                    'status' => $response->status(),
                    'body'   => $response->body()
                ]);
                
                // Disparar compensación si hay cita asociada
                if ($appointment) {
                    ZoomSagaCompensation::compensateCreateMeetingFailure($appointment, $errorMsg);
                }
                
                return null;
            }

            $data = $response->json();

            // Verificación estricta incluyendo la llave 'password' que exige el SDK
            if (!is_array($data) || !isset($data['id']) || !isset($data['start_url']) || !isset($data['join_url'])) {
                $errorMsg = 'El JSON no contiene las llaves requeridas.';
                Log::error('ZoomService Payload Error: ' . $errorMsg, [
                    'payload' => is_array($data) ? $data : 'Formato corrupto'
                ]);
                
                // Disparar compensación si hay cita asociada
                if ($appointment) {
                    ZoomSagaCompensation::compensateCreateMeetingFailure($appointment, $errorMsg);
                }
                
                return null;
            }

            return [
                'meeting_id'   => $data['id'],
                'password'     => $data['password'] ?? '', // Almacenamos la clave autogenerada de la sala
                'url_partner'   => $data['start_url'], 
                'url_patient' => $data['join_url'],  
            ];
        } catch (Throwable $e) {
            $errorMsg = "Excepción: {$e->getMessage()}";
            Log::error('ZoomService Exception en createMeeting: ' . $errorMsg, [
                'trace' => $e->getTraceAsString()
            ]);
            
            // Disparar compensación si hay cita asociada
            if ($appointment) {
                ZoomSagaCompensation::compensateCreateMeetingFailure($appointment, $errorMsg);
            }
            
            return null;
        }
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
     * 
     * 🔄 PATRÓN SAGA: Si falla, dispara compensación automática
     * @param string|int $meetingId
     * @param Appointment|null $appointment Cita asociada para compensación automática
     * @return bool
     */
    public function deleteMeeting($meetingId, ?Appointment $appointment = null)
    {
        try {
            $token = $this->getAccessToken();

            if (!$token || !$meetingId) {
                $errorMsg = 'Token inválido o ID de reunión vacío.';
                
                if ($appointment) {
                    ZoomSagaCompensation::compensateDeleteMeetingFailure($appointment, $errorMsg);
                }
                
                return false;
            }

            $url = "{$this->apiBaseUrl}/meetings/{$meetingId}";

            $response = Http::withToken($token)->delete($url);

            if ($response->failed()) {
                $errorMsg = "No se pudo eliminar la reunión. Status: {$response->status()}";
                Log::error('ZoomService Delete Error: ' . $errorMsg, [
                    'meeting_id' => $meetingId,
                    'status'     => $response->status(),
                    'body'       => $response->body()
                ]);
                
                if ($appointment) {
                    ZoomSagaCompensation::compensateDeleteMeetingFailure($appointment, $errorMsg);
                }
                
                return false;
            }

            return true;
        } catch (Throwable $e) {
            $errorMsg = "Excepción: {$e->getMessage()}";
            Log::error('ZoomService Exception en deleteMeeting: ' . $errorMsg, [
                'meeting_id' => $meetingId,
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($appointment) {
                ZoomSagaCompensation::compensateDeleteMeetingFailure($appointment, $errorMsg);
            }
            
            return false;
        }
    }

    /**
     * Fuerza el cierre de una reunión en vivo en Zoom.
     * 
     * 🔄 PATRÓN SAGA: Si falla, dispara compensación automática
     * @param string|int $meetingId
     * @param Appointment|null $appointment Cita asociada para compensación automática
     * @return bool
     */
    public function endMeeting($meetingId, ?Appointment $appointment = null)
    {
        try {
            $token = $this->getAccessToken();
            if (!$token || !$meetingId) {
                $errorMsg = 'Token inválido o ID de reunión vacío.';
                
                if ($appointment) {
                    ZoomSagaCompensation::compensateEndMeetingFailure($appointment, $errorMsg);
                }
                
                return false;
            }

            // Endpoint oficial para modificar el estado de una reunión en vivo
            $url = "{$this->apiBaseUrl}/meetings/{$meetingId}/status";

            $response = Http::withToken($token)->put($url, [
                'action' => 'end' // Fuerza la expulsión de todos y cierra la sala
            ]);

            if (!$response->successful()) {
                $errorMsg = "No se pudo cerrar la reunión. Status: {$response->status()}";
                Log::error('ZoomService End Meeting Error: ' . $errorMsg, [
                    'meeting_id' => $meetingId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                if ($appointment) {
                    ZoomSagaCompensation::compensateEndMeetingFailure($appointment, $errorMsg);
                }
                
                return false;
            }

            return true;
        } catch (Throwable $e) {
            $errorMsg = "Excepción: {$e->getMessage()}";
            Log::error('ZoomService Exception en endMeeting: ' . $errorMsg, [
                'meeting_id' => $meetingId,
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($appointment) {
                ZoomSagaCompensation::compensateEndMeetingFailure($appointment, $errorMsg);
            }
            
            return false;
        }
    }

    /**
     * 👇 BLOQUE NUEVO 1: REAGENDAR O RECREAR LA REUNIÓN EXISTENTE
     * Intenta actualizar la sala de Zoom actual. Si el ID ya no existe en Zoom (404),
     * automáticamente genera una sala nueva para evitar atascar el flujo del paciente.
     * 
     * 🔄 PATRÓN SAGA: Si falla, dispara compensación automática
     * @param string|int $meetingId
     * @param string $topic
     * @param string $startDateTime
     * @param int $durationMinutes
     * @param Appointment|null $appointment Cita asociada para compensación automática
     * @param array $originalData Datos originales para revertir en caso de fallo
     * @return array|null
     */
    public function updateMeeting($meetingId, $topic, $startDateTime, $durationMinutes, ?Appointment $appointment = null, array $originalData = [])
    {
        try {
            $token = $this->getAccessToken();

            if (!$token || !$meetingId) {
                $errorMsg = 'No se pudo actualizar sin un token válido.';
                Log::error('ZoomService Error: ' . $errorMsg);
                
                if ($appointment) {
                    ZoomSagaCompensation::compensateUpdateMeetingFailure($appointment, $errorMsg, $originalData);
                }
                
                return null;
            }

            $url = "{$this->apiBaseUrl}/meetings/{$meetingId}";

            // Intentar actualizar la reunión existente en la API de Zoom (PATCH)
            $response = Http::withToken($token)->patch($url, [
                'topic'      => $topic,
                'start_time' => $startDateTime,
                'duration'   => $durationMinutes,
                'timezone'   => config('app.timezone', 'America/Bogota')
            ]);

            // Caso Exitoso 204 No Content: La sala fue reprogramada correctamente
            if ($response->status() === 204) {
                return [
                    'action'     => 'updated',
                    'meeting_id' => $meetingId // El ID original sigue siendo totalmente válido
                ];
            }

            // Plan B: Si la reunión fue eliminada manualmente en Zoom o ya expiró por tiempo
            if ($response->status() === 404) {
                Log::warning("ZoomService Warning: La reunión ID {$meetingId} no existe. Creando una nueva.");
                
                $newMeeting = $this->createMeeting($topic, $startDateTime, $durationMinutes, $appointment);
                
                if ($newMeeting) {
                    return array_merge(['action' => 'recreated'], $newMeeting);
                }
            }

            // Si falla por otra razón (ej: 400 Bad Request, límites de cuenta)
            $errorMsg = "Falló la modificación de la reunión. Status: {$response->status()}";
            Log::error('ZoomService Update Error: ' . $errorMsg, [
                'status' => $response->status(),
                'body'   => $response->body()
            ]);
            
            if ($appointment) {
                ZoomSagaCompensation::compensateUpdateMeetingFailure($appointment, $errorMsg, $originalData);
            }
            
            return null;
        } catch (Throwable $e) {
            $errorMsg = "Excepción: {$e->getMessage()}";
            Log::error('ZoomService Exception en updateMeeting: ' . $errorMsg, [
                'meeting_id' => $meetingId,
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($appointment) {
                ZoomSagaCompensation::compensateUpdateMeetingFailure($appointment, $errorMsg, $originalData);
            }
            
            return null;
        }
    }
}
