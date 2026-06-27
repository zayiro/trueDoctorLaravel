<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\Specialty; 
use Illuminate\Support\Facades\Auth;

class ProfileDoctorController extends Controller
{
    public function edit()
    {              
        // Traer solo los planes para doctores que estén activos
        $plans = Plan::where('applicable_role', 'doctor')
             ->where('active', true) // Añadimos el filtro por active
             ->orderBy('price', 'asc')
             ->get();
        
        // Obtenemos el doctor actual y sus configuraciones
        $doctor = auth()->user()->doctor;
        
        $doctor->load(['settings']);
//dd($plans);
        $allSpecialties = Specialty::orderBy('name', 'asc')->get();
        
        return view('partner.profile.edit', compact('doctor', 'plans', 'allSpecialties'));
    }

    /**
     * Procesa la actualización de los datos profesionales del doctor
     */
    public function update(Request $request)
    {
        // 1. Recuperar el perfil del doctor vinculado al usuario autenticado
        $doctor = Auth::user()->doctor;

        if (!$doctor) {
            return back()->with('error', 'No se encontró un perfil profesional asociado a tu cuenta.');
        }

        // 2. Reglas de validación estrictas
        $request->validate([
            'identification'   => 'required|string|max:30|unique:doctors,identification,' . $doctor->id,
            'medical_license'  => 'nullable|string|max:50',
            'phone'            => 'nullable|string|max:20',
            'experience_years' => 'nullable|integer|min:0|max:60',
            'gender'           => 'required|in:male,female,other',
            'languages'         => 'required|array|min:1', // Exige al menos un idioma
            'languages.*'       => 'string|in:es,en,pt,fr,de', // Valida que pertenezcan a la lista permitida
            'bio'              => 'nullable|string|max:1000',
            'specialties'      => 'required|array|min:1',
            'specialties.*'    => 'integer|exists:specialties,id',
        ], [
            // Mensajes personalizados en español para una mejor experiencia de usuario
            'identification.required' => 'El documento de identidad es obligatorio.',
            'identification.unique'   => 'Este documento de identidad ya se encuentra registrado.',
            'gender.required'          => 'Debes seleccionar un género.',
            'languages.required'        => 'Debes seleccionar al menos un idioma para tu atención.',
            'specialties.required' => 'Debes seleccionar al menos una especialidad médica.',
        ]);

        $cleanPhone = preg_replace('/[^0-9]/', '', trim($request->phone));
        $fullPhone = $request->country_code ? $request->country_code . $cleanPhone : '+57' . $cleanPhone;

        // 3. Persistencia de datos de forma masiva
        // Nota: Laravel se encarga de convertir el array 'languages' en una cadena JSON de forma automática 
        // debido al cast que añadimos previamente en el modelo Doctor.
        $doctor->update([
            'identification'   => $request->identification,
            'medical_license'  => $request->medical_license,
            'phone'            => $fullPhone,
            'experience_years' => $request->experience_years,
            'gender'           => $request->gender,
            'languages'        => $request->languages, 
            'bio'              => $request->bio,
        ]);

        // 4. AQUÍ SE GUARDAN LAS ESPECIALIDADES (Sincronización masiva de la tabla pivot)
        $doctor->specialties()->sync($request->specialties);

        // 4. Redirección con mensaje de sesión usando el prefijo de tus breadcrumbs (partner.profile.edit)
        return redirect()->route('partner.profile.edit')
            ->with('success', 'Tu perfil profesional ha sido actualizado correctamente.');
    }
}
