<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Symfony\Component\HttpFoundation\Response;

/**
 * 🔐 MIDDLEWARE DE RATE LIMITING PARA API GATEWAY
 * 
 * Protege el API de citas contra abuso y saturación.
 * Implementa límites diferenciados por tipo de cliente.
 */
class ApiRateLimiter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Determinar el identificador único del cliente
        $clientId = $this->getClientIdentifier($request);
        
        // Aplicar límite según el tipo de endpoint
        $limit = $this->getApplicableLimit($request, $clientId);
        
        if (RateLimiter::tooManyAttempts($limit->key, $limit->maxAttempts)) {
            return $this->buildRateLimitResponse($request, $limit);
        }

        RateLimiter::hit($limit->key, $limit->decayMinutes * 60);

        return $next($request)
            ->header('X-RateLimit-Limit', $limit->maxAttempts)
            ->header('X-RateLimit-Remaining', RateLimiter::remaining($limit->key, $limit->maxAttempts))
            ->header('X-RateLimit-Reset', RateLimiter::resetAfter($limit->key));
    }

    /**
     * Obtiene el identificador único del cliente
     */
    private function getClientIdentifier(Request $request): string
    {
        // Prioridad: API Key > IP Address
        if ($request->hasHeader('X-API-Key')) {
            return 'api_key:' . $request->header('X-API-Key');
        }

        return 'ip:' . $request->ip();
    }

    /**
     * Determina el límite aplicable según el endpoint y cliente
     */
    private function getApplicableLimit(Request $request, string $clientId): Limit
    {
        // Límites diferenciados por endpoint
        if ($request->is('api/appointments/slots')) {
            // Búsqueda de slots: 60 requests por minuto
            return Limit::perMinute(60)->by($clientId);
        }

        if ($request->is('api/appointments') && $request->isMethod('post')) {
            // Creación de citas: 10 requests por minuto
            return Limit::perMinute(10)->by($clientId);
        }

        if ($request->is('api/appointments/*/cancel')) {
            // Cancelación de citas: 20 requests por minuto
            return Limit::perMinute(20)->by($clientId);
        }

        if ($request->is('api/appointments/*/status')) {
            // Consulta de estado: 120 requests por minuto (polling frecuente)
            return Limit::perMinute(120)->by($clientId);
        }

        // Límite por defecto: 100 requests por minuto
        return Limit::perMinute(100)->by($clientId);
    }

    /**
     * Construye la respuesta de Rate Limit excedido
     */
    private function buildRateLimitResponse(Request $request, Limit $limit): Response
    {
        $retryAfter = RateLimiter::resetAfter($limit->key);

        return response()->json([
            'error' => 'Too many requests',
            'message' => 'Ha excedido el límite de solicitudes. Por favor, intente más tarde.',
            'retry_after' => $retryAfter,
            'limit' => $limit->maxAttempts,
            'window' => 'per minute',
        ], 429)
            ->header('Retry-After', $retryAfter)
            ->header('X-RateLimit-Limit', $limit->maxAttempts)
            ->header('X-RateLimit-Remaining', 0)
            ->header('X-RateLimit-Reset', now()->addSeconds($retryAfter)->timestamp);
    }
}
