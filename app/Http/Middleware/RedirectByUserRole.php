<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectByUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Si es Doctor o Clínica y está en una ruta que no es de admin
            if ($user->hasRole(['doctor', 'clinic']) && !$request->is('admin*')) {
                return redirect('/admin');
            }

            // Si es Paciente y trata de entrar a rutas de admin
            if ($user->hasRole('patient') && $request->is('admin*')) {
                return redirect('/home');
            }
        }

        return $next($request);
    }
}
