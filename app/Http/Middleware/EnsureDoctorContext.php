<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;

class EnsureDoctorContext
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->role === 'doctor') {            
            // 1. Obtener todas las clínicas activas/aprobadas del médico
            $doctorId = auth()->user()->doctor?->id;

            $myClinics = !$doctorId ? collect() : DB::table('clinic_doctor')
                ->join('clinics', 'clinic_doctor.clinic_id', '=', 'clinics.id')
                ->join('users AS clinic_user', 'clinics.user_id', '=', 'clinic_user.id') // Alias asignado
                ->where('clinic_doctor.doctor_id', $doctorId)
                ->where('clinic_doctor.status', 'approved')
                ->select(
                    'clinics.id', 
                    'clinic_user.name', 
                    'clinic_user.profile_photo_path AS clinic_photo' // Extraemos la foto de la clínica
                )
                ->get(); 

            // 2. Bandera regulatoria: ¿Debe ver el selector de entorno?
            $showContextSelector = $myClinics->isNotEmpty();

            // 3. Inicializar la sesión según corresponda
            //    Se revalida en CADA petición: si el contexto guardado es de una clínica y el médico
            //    ya no es miembro aprobado (lo suspendieron o lo desvincularon), vuelve a particular.
            $context = session('doctor_context');
            $keepContext = $showContextSelector && is_array($context) && (
                ($context['type'] ?? null) === 'particular'
                || (
                    ($context['type'] ?? null) === 'clinic'
                    && $myClinics->contains(fn ($clinic) => (int) $clinic->id === (int) ($context['id'] ?? 0))
                )
            );

            if (!$keepContext) {
                session(['doctor_context' => [
                    'type'  => 'particular',
                    'id'    => null,
                    'name'  => 'Consultorio Particular',
                    'photo' => auth()->user()->profile_photo_path
                ]]);
            }

            // 4. Compartir de forma segura con todas las vistas Blade
            View::share('currentContext', session('doctor_context'));
            View::share('myClinicsList', $myClinics);
            View::share('showContextSelector', $showContextSelector);
        } else {
            // Valores nulos de respaldo para otros roles (Clínicas, Pacientes, Admin)
            View::share('currentContext', ['type' => 'other', 'id' => null, 'name' => '']);
            View::share('myClinicsList', collect());
            View::share('showContextSelector', false);
        }

        return $next($request);
    }
}
