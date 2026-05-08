<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\PatientAllergy;

class PatientController extends Controller
{
    public function limpiarAcentos($texto) 
    {
        $remplazos = [
            'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'Á'=>'A', 'É'=>'E', 'Í'=>'I', 'Ó'=>'O', 'Ú'=>'U',
            'ñ'=>'n', 'Ñ'=>'N'
        ];

        $cadena = str_replace(array_keys($remplazos), array_values($remplazos), $texto);

        return $cadena;
    }

    public function index()
    {
        $user = auth()->user();
        
        // Buscamos el paciente donde user_id sea igual al ID del usuario logueado
        $patient = Patient::with(['allergies' => function ($query) {
            $query->orderBy('created_at', 'desc')
            ->orderBy('type', 'asc'); // o 'desc'
        }])
        ->where('user_id', $user->id)
        ->firstOrFail();

        return view('patient.allergies.index', compact('patient'));
    }

    public function store(Request $request, $patientId)
    {
        $name = $this->limpiarAcentos($request->name);
        // Verificar si ya existe una alergia idéntica creada recientemente (evita duplicados por refresco)
        $exists = PatientAllergy::where('patient_id', $patientId)
            ->where('name', $name)
            ->where('created_at', '>', now()->subSeconds(10))
            ->exists();

        if ($exists) {
            return back()->with('error', 'Esta alergia ya fue registrada hace un momento.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:drug,food,environment,other',
            'severity' => 'required|in:mild,moderate,severe',
            'reaction' => 'nullable|string|max:500',
        ]);

        PatientAllergy::create([
            'patient_id' => $patientId,
            'name' => trim(ucfirst($name)),
            'type' => $request->type,
            'severity' => $request->severity,
            'reaction' => $request->reaction,
        ]);

        return back()->with('success', 'Alergia agregada al historial.');
    }

    public function destroy($id)
    {
        $allergy = PatientAllergy::findOrFail($id);

        // Seguridad: Verificar que el usuario sea el dueño o tenga permiso
        if ($allergy->patient_id !== auth()->user()->patient->id) { 
            abort(403); 
        }

        $allergy->delete();

        return back()->with('success', 'Alergia eliminada correctamente.');
    }

}
