<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Specialty; // Catálogo global de especialidades

class ProfileClinicController extends Controller
{
    /**
     * Renderiza el formulario de edición del perfil institucional de la clínica.
     * Garantiza el aislamiento estricto y la inyección segura de datos.
     */
    public function edit()
    {
        $user = auth()->user();

        // 🛡️ BLINDAJE DE PRODUCCIÓN: Validar rol y existencia del perfil institucional
        if (!$user || $user->role !== 'clinic' || !$user->clinic) {
            abort(403, 'Acceso denegado. Este panel está reservado para perfiles de clínicas configurados.');
        }

        // Cargar la clínica con sus especialidades ya vinculadas (Previene consultas N+1)
        $clinic = $user->clinic()->with('specialties')->first();

        // Traer todas las especialidades activas del sistema para el catálogo de checkboxes
        $allSpecialties = Specialty::where('status', true)->orderBy('name', 'asc')->get();

        // Traer todos los planes disponibles para pintar las tarjetas de suscripción si aplica
        $plans = DB::table('plans')->where('applicable_role', 'clinic')->orderBy('price', 'asc')->get();

        return view('partner.clinic.profile.edit', [
            'user'           => $user,
            'clinic'         => $clinic,
            'allSpecialties' => $allSpecialties,
            'plans'          => $plans,
        ]);
    }
    /**
     * Procesa la actualización del perfil comercial e institucional de la clínica.
     * Implementa base de datos transaccional para garantizar consistencia absoluta.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        // 🛡️ BLINDAJE DE SEGURIDAD INTERNA: Validar sesión y pertenencia de rol
        if (!$user || $user->role !== 'clinic' || !$user->clinic) {
            abort(403, 'Operación no autorizada.');
        }

        $clinic = $user->clinic;

        // 1. Reglas estrictas de validación de producción con excepciones de unicidad
        $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone'            => 'required|string|max:10',
            'country_code'     => 'nullable|string|alpha|size:2',
            'nit'              => 'required|string|max:20|unique:clinics,nit,' . $clinic->id,
            'reps_code'        => 'required|string|max:12|unique:clinics,reps_code,' . $clinic->id,
            'experience_years' => 'required|integer|min:0|max:150',
            'languages'        => 'nullable|array',
            'languages.*'      => 'string|in:es,en,pt,fr,de', // Valida que pertenezcan a la lista permitida
            'specialties'      => 'nullable|array',
            'specialties.*'    => 'exists:specialties,id',
            'bio'              => 'nullable|string|max:1500',
        ]);

        // 2. Procesamiento transaccional de datos cruzados
        DB::transaction(function () use ($request, $user, $clinic) {
            
            // Actualizar credenciales básicas de acceso a la plataforma
            $user->update([
                'name'  => trim($request->input('name')),
                'email' => $request->input('email'),
            ]);

            // Reconstruir el número telefónico concatenando el indicativo de país de forma estandarizada            
            $cleanPhone = preg_replace('/[^0-9]/', '', trim($request->phone));
            $fullPhone = $request->country_code ? $request->country_code . $cleanPhone : '+57' . $cleanPhone;
            
            // Preservar el slug actual de producción si ya existe para proteger el SEO de Google
            $stableSlug = $clinic->slug ?: Str::slug($request->input('name'));

            // Evaluar el estado de verificación comercial del tenant
            $currentStatus = $clinic->validation_status;
            if ($currentStatus === 'missing') {
                $currentStatus = 'pending_validation';
            }

            // Actualizar la tabla comercial e institucional de la clínica
            $clinic->update([
                'nit'               => $request->input('nit'),
                'reps_code'         => $request->input('reps_code'),
                'phone'             => $fullPhone,
                'experience_years'  => $request->input('experience_years'),
                'languages'         => $request->languages, // Guardar el array de idiomas directamente en la columna JSON
                'bio'               => $request->input('bio'),
                'slug'              => $stableSlug,
                'validation_status' => $currentStatus,
            ]);

            // 🔒 VINCULACIÓN MULTI-TENANT: Sincronizar especialidades en la tabla intermedia clinic_specialty
            // El método sync() elimina los registros antiguos que no se marquen y añade los nuevos de forma limpia
            $clinic->specialties()->sync($request->input('specialties', []));
        });

        return back()->with('success', 'El perfil institucional y el catálogo de especialidades han sido actualizados con éxito.');
    }
}
