<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Doctor;
use Illuminate\Support\Str;

class SeoOptimizationMiddleware
{
    /**
     * Intercepta la petición para calcular e inyectar Metatags dinámicos en las vistas.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Valores por defecto para todo tu SaaS (Home, login, etc.)
        $title = 'OpenDoctor | Agenda tu Cita Médica Virtual o Presencial';
        $description = 'Encuentra médicos especialistas calificados en minutos. Triage inteligente por Inteligencia Artificial y agenda de citas en tiempo real.';
        $robots = 'index, follow'; // Permitir indexación general

        // 2. REGLA: Si el usuario está en el Perfil Público del Doctor (/medical-partner/{slug})
        if ($request->routeIs('partner.public.profile') || Str::contains($request->getRequestUri(), '/medical-partner/')) {
            
            // Capturamos el slug directamente del parámetro de tu ruta real
            $slug = $request->route('partner') ?? basename($request->getRequestUri());
            
            // Buscamos el médico en la base de datos con todas sus relaciones
            $doctor = Doctor::with(['user', 'specialties', 'addresses.city'])
                ->where('slug', $slug)
                ->first();

            if ($doctor && $doctor->validation_status === 'approved' && $doctor->active) {
                $nombre = $doctor->user->name;
                $especialidad = $doctor->specialties->first()->name ?? 'Especialista';
                $ciudad = $doctor->addresses->first()->city->name ?? '';

                // Formateamos títulos idénticos al estándar premium de Doctoralia
                $title = "Dr(a). {$nombre} - Citas en {$especialidad} {$ciudad} | OpenDoctor";
                $description = "Reserva tu cita médica con el Dr(a). {$nombre} ({$especialidad}) en {$ciudad}. Revisa opiniones de pacientes reales, horarios disponibles y agenda en línea.";
            }
        }

        // 3. REGLA: Si el usuario está en el Buscador de Síntomas por IA
        if ($request->routeIs('search.symptom.view') || Str::contains($request->getRequestUri(), '/search-symptom')) {
            $title = 'Asistente de Orientación Médica por Síntomas (IA) | OpenDoctor';
            $description = 'Describe cómo te sientes en lenguaje natural. Nuestro motor de Inteligencia Artificial analizará tus síntomas y te guiará gratis hacia el especialista adecuado.';
        }

        // 4. REGLA: Blindaje de Paneles Privados (Evita que Google indexe dashboards o datos confidenciales)
        if (Str::contains($request->getRequestUri(), ['/dashboard', '/admin', '/administrator', '/panel', '/livewire'])) {
            $robots = 'noindex, nofollow';
        }

        // 5. Compartimos las variables de forma global con todas las vistas Blade
        view()->share('meta_title', $title);
        view()->share('meta_description', $description);
        view()->share('meta_robots', $robots);

        return $next($request);
    }
}
