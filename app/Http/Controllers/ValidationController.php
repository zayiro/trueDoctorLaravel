<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Mail\ValidationStatusNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ValidationController extends Controller
{
    /**
     * Muestra la lista de médicos esperando validación.
     */
    public function index()
    {
        // Traemos los médicos pendientes junto con sus datos de usuario nativos
        $doctors = Doctor::with('user')
            ->whereIn('validation_status', ['pending_validation', 'missing'])
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

            //crea la sede virtual por defecto en la cuenta del doctor
            $doctor->createVirtualAddress();
            
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
        // 1. Obtenemos el path relativo directo guardado en la base de datos
        $relativePath = ($type === 'cedula') ? $doctor->identity_card_path : $doctor->professional_card_path;

        // 2. Validamos la existencia real utilizando el sistema de archivos oficial de Laravel
        if (!$relativePath || !Storage::disk('public')->exists($relativePath)) {
            abort(404, 'Lo sentimos, el archivo físico no se encuentra en el servidor.');
        }

        // 3. Obtenemos la ruta física absoluta de manera segura
        $fullPath = Storage::disk('public')->path($relativePath);

        // 4. Calculamos el tipo MIME real del archivo
        $mimeType = Storage::disk('public')->mimeType($relativePath);

        // 5. 💡 EXTRAEMOS LA EXTENSIÓN REAL DEL ARCHIVO (Fijación crítica contra alertas)
        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
        $nombreLimpio = $type . '-' . Str::slug($doctor->user->name) . '.' . $extension;

        // 6. Encabezados de seguridad e inyección inline estricta
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $nombreLimpio . '"',
            'X-Content-Type-Options' => 'nosniff', // Prohibir sniffing de código
            'Content-Length' => filesize($fullPath),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ];

        // 7. Retornamos el archivo
        return response()->file($fullPath, $headers);
    }
}
