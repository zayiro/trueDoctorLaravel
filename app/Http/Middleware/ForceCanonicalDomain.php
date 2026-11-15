<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceCanonicalDomain
{
    /**
     * Fuerza redirección 301 hacia https://opendoctor.online (sin www).
     * Compatible con AWS EC2 y balanceadores de carga que usan X-Forwarded-Proto.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production')) {
            $host = $request->getHost();
            $isSecure = $request->secure() || $request->header('X-Forwarded-Proto') === 'https';
            
            // Si tiene www. o no es HTTPS, redirigir
            if (str_starts_with($host, 'www.') || !$isSecure) {
                $canonicalUrl = 'https://opendoctor.online' . $request->getRequestUri();
                return redirect($canonicalUrl, 301);
            }
        }

        return $next($request);
    }
}
