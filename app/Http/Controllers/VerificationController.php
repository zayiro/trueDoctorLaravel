<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VerificationController extends Controller
{
    public function store(Request $request)
    {        
        // 1. Validaciones de archivos
        $request->validate([
            'identity_card' => ['required', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:4096'],
            'professional_card' => ['required', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:4096'],
        ], [
            'identity_card.required' => 'La foto de la cédula es obligatoria.',
            'identity_card.mimes' => 'La cédula debe ser JPG, PNG o PDF.',
            'identity_card.max' => 'La cédula no debe pesar más de 4MB.',
            'professional_card.required' => 'La tarjeta profesional es obligatoria.',
            'professional_card.mimes' => 'La tarjeta profesional debe ser JPG, PNG o PDF.',
            'professional_card.max' => 'La tarjeta profesional no debe pesar más de 4MB.',
        ]);

        // Obtener el registro de la tabla 'doctors' asociado al usuario autenticado
        $doctor = auth()->user()->doctor; 

        if (!$doctor) {
            return redirect()->back()->with('error', 'No se encontró un perfil de médico asociado a esta cuenta.');
        }

        // 2. Guardar archivos de forma segura en 'storage/app/verification_docs'
        if ($request->hasFile('identity_card')) {
            if ($doctor->identity_card_path) {
                Storage::delete($doctor->identity_card_path);
            }
            $cedulaPath = $request->file('identity_card')->store('verification_docs');
        }

        if ($request->hasFile('professional_card')) {
            if ($doctor->professional_card_path) {
                Storage::delete($doctor->professional_card_path);
            }
            $tarjetaPath = $request->file('professional_card')->store('verification_docs');
        }

        // 3. Actualizar el registro del médico con tu campo 'validation_status'
        $doctor->update([
            'validation_status' => 'pending_validation',
            'identity_card_path' => $cedulaPath ?? $doctor->identity_card_path,
            'professional_card_path' => $tarjetaPath ?? $doctor->professional_card_path,
        ]);

        return redirect()->back()->with('success', 'Documentación enviada correctamente. Tu perfil está en revisión.');
    }
}
