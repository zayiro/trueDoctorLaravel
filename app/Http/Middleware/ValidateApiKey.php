<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * 🔐 MIDDLEWARE DE VALIDACIÓN DE API KEY
 * 
 * Valida que las solicitudes externas incluyan una API Key válida.
 * Registra intentos de acceso no autorizado.
 */
class ValidateApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Permitir solicitudes sin API Key solo si están autenticadas internamente
        if (auth()->check()) {
            return $next($request);
        }

        // Validar presencia de API Key
        if (!$request->hasHeader('X-API-Key')) {
            Log::warning('API Gateway: Solicitud sin API Key', [
                'ip' => $request->ip(),
                'endpoint' => $request->path(),
                'method' => $request->method(),
            ]);

            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'API Key requerida en header X-API-Key',
            ], 401);
        }

        $apiKey = $request->header('X-API-Key');

        // Validar que la API Key sea válida
        $client = DB::table('api_clients')
            ->where('api_key', hash('sha256', $apiKey))
            ->where('is_active', true)
            ->first();

        if (!$client) {
            Log::warning('API Gateway: API Key inválida', [
                'ip' => $request->ip(),
                'endpoint' => $request->path(),
                'method' => $request->method(),
            ]);

            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'API Key inválida o inactiva',
            ], 401);
        }

        // Validar que el cliente tenga acceso al endpoint solicitado
        if (!$this->clientHasAccessToEndpoint($client->id, $request->path())) {
            Log::warning('API Gateway: Acceso denegado a endpoint', [
                'client_id' => $client->id,
                'ip' => $request->ip(),
                'endpoint' => $request->path(),
                'method' => $request->method(),
            ]);

            return response()->json([
                'error' => 'Forbidden',
                'message' => 'No tienes permiso para acceder a este endpoint',
            ], 403);
        }

        // Registrar acceso exitoso
        DB::table('api_client_logs')->insert([
            'api_client_id' => $client->id,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status_code' => 200,
            'created_at' => now(),
        ]);

        // Almacenar cliente en request para acceso posterior
        $request->attributes->set('api_client', $client);

        return $next($request);
    }

    /**
     * Verifica si el cliente tiene acceso al endpoint
     */
    private function clientHasAccessToEndpoint(int $clientId, string $endpoint): bool
    {
        // Si el cliente tiene acceso a todos los endpoints
        $hasFullAccess = DB::table('api_clients')
            ->where('id', $clientId)
            ->where('has_full_access', true)
            ->exists();

        if ($hasFullAccess) {
            return true;
        }

        // Verificar acceso específico al endpoint
        return DB::table('api_client_endpoints')
            ->where('api_client_id', $clientId)
            ->where('endpoint', $endpoint)
            ->where('is_allowed', true)
            ->exists();
    }
}
