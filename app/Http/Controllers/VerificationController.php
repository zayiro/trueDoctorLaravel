<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VerificationController extends Controller
{
    /**
     * Procesa y almacena de forma segura los documentos de verificación para Doctores o Clínicas.
     */
    public function store(Request $request)
    {        
        $user = auth()->user();

        // 1. VALIDACIÓN MAESTRA DE ARCHIVOS INDEPENDIENTE DEL ROL
        $request->validate([
            'identity_card' => ['required', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:4096'],
            'professional_card' => ['required', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:4096'],
        ], [
            'identity_card.required' => 'El soporte del primer documento (Cédula/RUT) es obligatorio.',
            'identity_card.mimes' => 'El archivo debe ser en formato JPG, PNG o PDF.',
            'identity_card.max' => 'El archivo no debe pesar más de 4MB.',
            'professional_card.required' => 'El soporte del segundo documento (Tarjeta/REPS) es obligatorio.',
            'professional_card.mimes' => 'El archivo debe ser en formato JPG, PNG o PDF.',
            'professional_card.max' => 'El archivo no debe pesar más de 4MB.',
        ]);

        // 2. ABSTRACCIÓN DINÁMICA DEL TENANT (Inversión de Control)
        $profile = null;
        $firstField = 'identity_card_path';
        
        // Mapeamos dinámicamente las llaves según las migraciones de tu base de datos
        if ($user->role === 'doctor') {
            $profile = $user->doctor;
            $secondField = 'professional_card_path';
        } elseif ($user->role === 'clinic') {
            $profile = $user->clinic;
            $secondField = 'reps_code_card_path'; // Llave exacta de tu migración 'clinics'
        }

        // Blindaje de seguridad en caso de que el perfil no esté indexado por el Observer
        if (!$profile) {
            return redirect()->back()->with('error', 'No se encontró un perfil comercial válido asociado a esta cuenta.');
        }

        // 3. PROCESAMIENTO Y LIMPIEZA DE ARCHIVOS EN STORAGE
        $firstPath = $profile->{$firstField};
        if ($request->hasFile('identity_card')) {
            if ($profile->{$firstField}) {
                Storage::delete($profile->{$firstField});
            }
            // Guarda los documentos en el disco local protegido
            $firstPath = $request->file('identity_card')->store('verification_docs');
        }

        $secondPath = $profile->{$secondField};
        if ($request->hasFile('professional_card')) {
            if ($profile->{$secondField}) {
                Storage::delete($profile->{$secondField});
            }
            $secondPath = $request->file('professional_card')->store('verification_docs');
        }

        // 4. ACTUALIZACIÓN TRANSACCIONAL DEL ESTADO DE VERIFICACIÓN
        $profile->update([
            'validation_status' => 'pending_validation',
            $firstField         => $firstPath,
            $secondField        => $secondPath,
        ]);

        return redirect()->back()->with('success', 'Documentación institucional enviada correctamente. Tu perfil ha entrado en fase de revisión.');
    }
}
