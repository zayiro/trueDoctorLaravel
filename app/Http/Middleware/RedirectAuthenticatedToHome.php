<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Páginas pensadas solo para visitantes (registro de doctor, clínica, elección de registro):
 * si ya hay una sesión iniciada, se devuelve a la home en lugar de mostrar el formulario.
 */
class RedirectAuthenticatedToHome
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
