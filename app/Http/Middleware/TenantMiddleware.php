<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Doctor;
use App\Models\Clinic;
use App\Models\Patient;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si el usuario no está autenticado, permitir que continúe
        // (otros middlewares como 'auth' manejarán la redirección)
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
                
        $tenantId = null;
        $tenantType = null;

        // Validar si el usuario es Administrador del Sistema        
        if ($user->role === 'admin') {
            $tenantId = $user->id;
            $tenantType = $user->role;
        }

        // Validar si el usuario es un Doctor
        $doctor = Doctor::where('user_id', $user->id)->first();
        if ($doctor) {
            $tenantId = $doctor->id;
            $tenantType = $user->role;
        }
                
        // Validar si el usuario es una Clinic
        if (!$tenantId) {
            $clinic = Clinic::where('user_id', $user->id)->first();
            if ($clinic) {
                $tenantId = $clinic->id;
                $tenantType = $user->role;
            }
        }

        // Validar si el usuario es un Patient
        if (!$tenantId) {
            $patient = Patient::where('user_id', $user->id)->first();
            if ($patient) {
                $tenantId = $patient->id;
                $tenantType = $user->role;
            }
        }

        // Si no se encontró un tenant válido, abortar con 403
        if (!$tenantId || !$tenantType) {
            abort(403, 'No valid tenant found for this user.');
        }

        // Registrar el tenant en el contenedor de servicios
        app()->instance('tenant.id', $tenantId);
        app()->instance('tenant.type', $tenantType);
        app()->instance('tenant.user', $user);

        // Opcionalmente, registrar en el request para acceso fácil
        $request->attributes->set('tenant_id', $tenantId);
        $request->attributes->set('tenant_type', $tenantType);

        return $next($request);
    }
}
