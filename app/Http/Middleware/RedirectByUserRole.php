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
    /*public function handle(Request $request, Closure $next)
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
    }*/

    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();

            // 1. Si es Administrador global (puedes omitirlo si usas el mismo para clínicas)
            if ($user->hasRole('admin') && !$request->is('admin*')) {
                return redirect('/admin/dashboard');
            }

            // 2. Si es Doctor o Clínica, despacharlo a su panel dedicado
            if ($user->hasRole(['doctor', 'clinic'])) {
                // Si intenta entrar a la raíz, al dashboard común o a rutas de pacientes
                if ($request->is('/') || $request->is('dashboard') || $request->is('patient*')) {
                    return redirect('/admin/dashboard');
                }
            }

            // 3. Si es Paciente, restringir acceso a zonas médicas/administrativas
            if ($user->hasRole('patient')) {
                // Si intenta entrar a escondidas a rutas de admin o doctor
                if ($request->is('admin*') || $request->is('doctor*') || $request->is('dashboard') || $request->is('/')) {
                    return redirect('/patient/dashboard');
                }
            }
        }

        return $next($request);
    }

}
