<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\Specialty; 
use Illuminate\Support\Facades\Auth;
use App\Models\DoctorSetting;

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

        $allSpecialties = Specialty::orderBy('name', 'asc')->get();
        $user = Auth::user();
        
        return view('partner.profile.edit', compact('doctor', 'plans', 'allSpecialties', 'user'));
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
        // Habilitación del prestador (incapacidades): se limpian puntos, espacios y letras antes de validar.
        $request->merge([
            'habilitation_nit'  => \App\Services\Incapacity\Habilitation::cleanNit($request->input('habilitation_nit')),
            'habilitation_reps' => \App\Services\Incapacity\Habilitation::cleanReps($request->input('habilitation_reps')),
        ]);
        
        $request->validate([
            'identification'    => 'required|string|max:30|unique:doctors,identification,' . $doctor->id,
            'medical_license'   => 'nullable|string|max:50',
            'identification_type' => ['sometimes', 'required', 'in:' . implode(',', array_keys((array) config('rethus.document_types', [])))],
            'phone'             => 'nullable|string|max:20',
            'country_code'      => 'nullable|string|alpha|size:2',
            'experience_years'  => 'nullable|integer|min:0|max:60',
            'gender'            => 'required|in:male,female,other',
            'languages'         => 'required|array|min:1', // Exige al menos un idioma
            'languages.*'       => 'string|in:es,en,pt,fr,de', // Valida que pertenezcan a la lista permitida
            'bio'               => 'nullable|string|max:1000',
            'specialties'       => 'required|array|min:1',
            'specialties.*'     => 'integer|exists:specialties,id',
            'habilitation_nit'  => ['nullable', 'string', 'max:20', 'regex:' . \App\Services\Incapacity\Habilitation::NIT_REGEX, 'required_with:habilitation_reps'],
            'habilitation_reps' => ['nullable', 'string', 'max:12', 'regex:' . \App\Services\Incapacity\Habilitation::REPS_REGEX, 'required_with:habilitation_nit'],
        ], [
            // Mensajes personalizados en español para una mejor experiencia de usuario
            'identification.required' => 'El documento de identidad es obligatorio.',
            'habilitation_nit.regex'  => 'El NIT debe tener solo números, con el dígito de verificación opcional (ej: 900123456-7).',
            'habilitation_reps.regex' => 'El código REPS debe tener entre 8 y 12 dígitos, sin espacios ni letras.',
            'habilitation_nit.required_with'  => 'Si escribes el código REPS, escribe también el NIT.',
            'habilitation_reps.required_with' => 'Si escribes el NIT, escribe también el código REPS.',
            'identification.unique'   => 'Este documento de identidad ya se encuentra registrado.',
            'identification_type.required' => 'Selecciona el tipo de documento.',
            'identification_type.in'       => 'El tipo de documento no es válido.',
            'gender.required'          => 'Debes seleccionar un género.',
            'languages.required'        => 'Debes seleccionar al menos un idioma para tu atención.',
            'specialties.required' => 'Debes seleccionar al menos una especialidad médica.',
        ]);

        $cleanPhone = preg_replace('/[^0-9]/', '', trim($request->phone));
        $fullPhone = $request->phone_code ? $request->phone_code . $cleanPhone : '+57' . $cleanPhone;

        // 3. Persistencia de datos de forma masiva
        // Nota: Laravel se encarga de convertir el array 'languages' en una cadena JSON de forma automática 
        // debido al cast que añadimos previamente en el modelo Doctor.
        // Tipo de documento: editable mientras la verificación ReTHUS no esté vigente ni en revisión.
        if ($request->filled('identification_type') && !in_array($doctor->rethus_status, ['verified', 'requested'], true)) {
            $doctor->identification_type = $request->identification_type;
        }
        
        $doctor->update([
            'identification'   => $request->identification,
            'medical_license'  => $request->medical_license,
            'phone'            => $fullPhone,
            'country_code'     => $request->country_code,
            'experience_years' => $request->experience_years,
            'gender'           => $request->gender,
            'languages'        => $request->languages, 
            'bio'              => $request->bio,
        ]);

        // 4. AQUÍ SE GUARDAN LAS ESPECIALIDADES (Sincronización masiva de la tabla pivot)
        $doctor->specialties()->sync($request->specialties);

        // Habilitación del prestador (NIT y código REPS) para las incapacidades.
        $doctor->forceFill([
            'habilitation_nit'  => $request->input('habilitation_nit') ?: null,
            'habilitation_reps' => $request->input('habilitation_reps') ?: null,
        ])->save();

        // 4. Redirección con mensaje de sesión usando el prefijo de tus breadcrumbs (partner.profile.edit)
        return redirect()->route('partner.profile.edit')
            ->with('success', 'Tu perfil profesional ha sido actualizado correctamente.');
    }
}
