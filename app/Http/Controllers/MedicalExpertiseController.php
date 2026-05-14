<?php

namespace App\Http\Controllers;

use App\Models\MedicalExpertise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MedicalExpertiseController extends Controller
{
    // Listado de enfermedades del médico autenticado
    public function index()
    {
        $doctor = Auth::user()->doctor; // Asumiendo que User tiene relación 'doctor'
        $expertises = MedicalExpertise::where('doctor_id', $doctor->id)->get();
        
        return view('partner.expertises.index', compact('expertises'));
    }

    // Procesar el guardado de una nueva enfermedad
    public function store(Request $request)
    {
        $request->validate([
            'disease_name' => 'required|string|max:255',
            'symptoms_keywords' => 'required|string',
        ]);

        $doctor = Auth::user()->doctor;

        MedicalExpertise::create([
            'doctor_id' => $doctor->id,
            'disease_name' => $request->disease_name,
            'symptoms_keywords' => $request->symptoms_keywords,
        ]);

        return redirect()->route('partner.expertises.index')->with('success', 'Enfermedad agregada correctamente.');
    }

    // Formulario de edición
    public function edit(MedicalExpertise $expertise)
    {
        // Seguridad: Evitar que un médico edite el registro de otro médico
        if ($expertise->doctor_id !== Auth::user()->doctor->id) {
            abort(403, 'Acción no autorizada.');
        }

        return view('partner.expertises.edit', compact('expertise'));
    }

    // Procesar la actualización
    public function update(Request $request, MedicalExpertise $expertise)
    {
        if ($expertise->doctor_id !== Auth::user()->doctor->id) {
            abort(403, 'Acción no autorizada.');
        }

        $request->validate([
            'disease_name' => 'required|string|max:255',
            'symptoms_keywords' => 'required|string',
        ]);

        $expertise->update([
            'disease_name' => $request->disease_name,
            'symptoms_keywords' => $request->symptoms_keywords,
        ]);

        return redirect()->route('partner.expertises.index')->with('success', 'Información actualizada correctamente.');
    }

    // Eliminar registro
    public function destroy(MedicalExpertise $expertise)
    {
        if ($expertise->doctor_id !== Auth::user()->doctor->id) {
            abort(403, 'Acción no autorizada.');
        }

        $expertise->delete();

        return redirect()->route('partner.expertises.index')->with('success', 'Enfermedad eliminada correctamente.');
    }
}
