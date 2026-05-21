<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use App\Mail\ValidationStatusNotification;
use Illuminate\Support\Facades\Mail;

class ValidationController extends Controller
{
    /**
     * Muestra la lista de médicos esperando validación.
     */
    public function index()
    {
        // Traemos los médicos pendientes junto con sus datos de usuario nativos
        $doctors = Doctor::with('user')
            ->where('validation_status', 'pending_validation')
            ->latest()
            ->paginate(10);

        return view('administrator.validation.index', compact('doctors'));
    }

    /**
     * Actualiza el estado del médico (Aprobado o Rechazado).
     */
    public function update(Request $request, Doctor $doctor)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $status = $request->status;

        // Si es rechazado y quieres borrar los documentos anteriores para que suba nuevos:
        if ($status === 'rejected') {
            if ($doctor->identity_card_path) Storage::delete($doctor->identity_card_path);
            if ($doctor->professional_card_path) Storage::delete($doctor->professional_card_path);
            
            $doctor->update([
                'validation_status' => 'rejected',
                'identity_card_path' => null,
                'professional_card_path' => null,
                'active' => false // Se mantiene oculto
            ]);
            
            $msg = "El médico ha sido rechazado y se le ha solicitado nueva documentación.";
        } else {
            // Si es aprobado, activamos su perfil en OpenDoctor
            $doctor->update([
                'validation_status' => 'approved',
                'active' => true // Ya aparece en las búsquedas
            ]);
            
            $msg = "¡Médico aprobado con éxito! Su perfil ya es público.";
        }

        // NOTA: Aquí podrías disparar un Evento/Mail para notificar al médico automáticamente.
        Mail::to($doctor->user->email)->send(new ValidationStatusNotification($doctor->user, $status));

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Sirve los archivos de forma segura forzando la visualización inline en el navegador.
     */
    public function viewDocument(Doctor $doctor, $type)
    {
        // 1. Como pusiste todo con 'Doctor $doctor', Laravel ya buscó por slug 
        // y te entregó el objeto del médico completo aquí.
        
        // 2. Obtenemos el path guardado en la base de datos (ej: "verification_docs/xxxx.jpg")
        $relativePath = ($type === 'cedula') ? $doctor->identity_card_path : $doctor->professional_card_path;

        // 3. Construimos la ruta física uniendo 'public' con el path de la base de datos
        $fullPath = storage_path('app/public/' . $relativePath);

        // 4. Validamos la existencia real en el disco duro
        if (!$relativePath || !file_exists($fullPath)) {
            abort(404, 'Lo sentimos, el archivo físico no se encuentra en el servidor.');
        }

        // 5. Obtenemos el tipo MIME usando el disco público de Laravel
        $mimeType = Storage::disk('public')->mimeType($relativePath);

        // 6. Retornamos el archivo inline de forma segura
        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($fullPath) . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
