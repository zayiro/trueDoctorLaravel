<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class PartnerPatientController extends Controller
{
    public function index(Request $request)
    {
        $doctor = auth()->user();
        $plan = auth()->user()->doctor->settings->plan;
        
        $querySearch = $request->input('query');

        // 1. Base de la consulta: Pacientes que han tenido citas con este doctor
        $query = Patient::whereHas('appointments', function ($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id);
        });

        // 2. Aplicar Restricción según Plan
        if ($plan->can_search_patients && $querySearch) {
            $query->where(function ($q) use ($querySearch) {
                // Buscamos el nombre en la tabla USERS vinculada
                $q->whereHas('user', function ($qu) use ($querySearch) {
                    $qu->where('name', 'LIKE', "%{$querySearch}%");
                })
                // Buscamos el documento en la tabla PATIENTS (asegúrate de que el campo sea 'identification' o 'documento')
                ->orWhere('identification', 'LIKE', "%{$querySearch}%");
            });
        } else {
            // Plan FREE: Forzamos que solo vea pacientes que tengan cita HOY
            $query->whereHas('appointments', function ($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id)
                  ->whereDate('date', now());
            });
        }

        // 3. Ejecutar con paginación y límite del plan
        $patients = $query->with('appointments') // Eager loading para evitar el problema N+1
            ->limit($plan->max_patients_list)
            ->paginate(15);

        return view('partner.patients.index', compact('patients', 'plan'));
    }

    /*public function show($id)
    {    
        // Cargamos las relaciones necesarias para no hacer consultas extra en la vista
        $patient = Patient::with(['user', 'appointments.service'])
        ->where('id', $id)
        ->firstOrFail();
        
        return view('partner.patients.show', compact('patient'));
    }*/

    public function show($id)
    {    
        $doctor = auth()->user();

        // 1. Cargamos al paciente con sus relaciones
        // Incluimos citas ordenadas para ver el historial clínico correctamente
        $patient = Patient::with(['user', 'appointments' => function($query) use ($doctor) {
            $query->where('doctor_id', $doctor->id)
                ->orderBy('date', 'desc');
        }, 'appointments.service'])
        ->where('id', $id)
        ->firstOrFail();

        // 2. SEGURIDAD: Verificar que el paciente tiene relación con este doctor
        // Evita que un doctor vea datos de pacientes ajenos cambiando el ID en la URL
        $tieneAcceso = $patient->appointments()->where('doctor_id', $doctor->id)->exists();

        if (!$tieneAcceso) {
            abort(403, 'No tienes permiso para acceder a la ficha de este paciente.');
        }

        // 3. LÓGICA DE PLAN: ¿Puede ver el historial completo?
        // Si es Plan Free, podrías filtrar para que solo vea la cita de hoy
        if (!$doctor->canDo('can_export_history')) {
            // Opcional: Podrías pasar una variable para limitar qué se muestra en el Blade
            $limitado = true;
        }

        return view('partner.patients.show', compact('patient', 'doctor'));
    }
}
