<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PatientHistoryAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PatientHistoryAttachmentController extends Controller
{
    /**
     *  Helper centralizado para validar permisos sobre un attachment.
     * 
     * Reglas de negocio:
     * - El paciente propietario puede ver/descargar/eliminar sus propios archivos.
     * - Doctores y clínicas pueden ver/descargar archivos de sus pacientes (con relación histórica).
     * - Admins pueden ver/descargar cualquier archivo.
     * - Solo el paciente propietario puede eliminar sus archivos.
     * 
     * @param PatientHistoryAttachment $attachment
     * @param bool $requireOwnership Si es true, solo el propietario puede acceder (para delete).
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    private function validateAttachmentAccess(PatientHistoryAttachment $attachment, bool $requireOwnership = false): void
    {
        $user = Auth::user();
        
        if (!$user) {
            abort(403, 'No autenticado.');
        }

        // Cargar la relación patient para evitar N+1
        $attachment->loadMissing('patient.user');
        
        $patient = $attachment->patient;
        
        if (!$patient) {
            abort(404, 'El paciente asociado a este documento no existe.');
        }

        // Caso 1: El usuario es el propietario del paciente
        if ($user->id === $patient->user_id) {
            return; // Acceso permitido
        }

        // Si requiere ownership estricta (para eliminar), no permitir más validaciones
        if ($requireOwnership) {
            abort(403, 'Solo el paciente propietario puede eliminar este documento.');
        }

        // Caso 2: Admin puede acceder a todo
        if ($user->role === 'admin') {
            return;
        }

        // Caso 3: Doctor o Clínica deben tener relación histórica con el paciente
        if (in_array($user->role, ['doctor', 'clinic'], true)) {
            $doctorId = $user->role === 'doctor' ? $user->doctor?->id : null;
            $clinicId = $user->role === 'clinic' ? $user->clinic?->id : null;

            $hasRelation = $patient->appointments()
                ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
                ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
                ->exists();

            if ($hasRelation) {
                return; // Acceso permitido
            }
        }

        abort(403, 'No tienes autorización para acceder a este documento médico.');
    }

    /**
     * Almacena un nuevo archivo adjunto en la historia clínica del paciente.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $patient = $user->patient;

        if (!$patient) {
            abort(404, 'No se encontró un perfil de paciente asociado a tu cuenta.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'], // 10MB
        ], [
            'file.max' => 'El archivo no debe superar los 10MB.',
            'file.mimes' => 'Solo se permiten archivos PDF o imágenes (JPG, PNG, WEBP).',
        ]);

        try {
            $file = $request->file('file');
            
            // Almacenar en disco privado con ruta organizada por paciente
            $path = $file->store("patients/{$patient->id}/attachments", 'private');

            PatientHistoryAttachment::create([
                'patient_id' => $patient->id,
                'name'       => trim($validated['name']),
                'file_path'  => $path,
                'file_type'  => $file->getMimeType(),
                'file_size'  => $file->getSize(),
            ]);

            return redirect()->back()->with('success', 'Tu reporte médico ha sido anexado correctamente al historial.');
        } catch (Throwable $e) {
            Log::error('Error al almacenar adjunto de historia clínica', [
                'patient_id' => $patient->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Ocurrió un error al guardar el archivo. Por favor, intenta nuevamente.');
        }
    }

    /**
     * Descarga un archivo adjunto de la historia clínica.
     */
    public function download(PatientHistoryAttachment $attachment): StreamedResponse
    {
        // Validar permisos de acceso
        $this->validateAttachmentAccess($attachment, requireOwnership: false);

        try {
            if (!Storage::disk('private')->exists($attachment->file_path)) {
                abort(404, 'El archivo solicitado no se encuentra en el servidor.');
            }

            return Storage::disk('private')->download(
                $attachment->file_path,
                $attachment->name
            );
        } catch (Throwable $e) {
            Log::error('Error al descargar adjunto de historia clínica', [
                'attachment_id' => $attachment->id,
                'error' => $e->getMessage(),
            ]);

            abort(500, 'Error al procesar la descarga del archivo.');
        }
    }

    /**
     * Muestra el contenido del archivo en el navegador (inline).
     */
    public function viewHistoryAttachment(PatientHistoryAttachment $attachment)
    {
        // Validar permisos de acceso
        $this->validateAttachmentAccess($attachment, requireOwnership: false);

        try {
            if (!Storage::disk('private')->exists($attachment->file_path)) {
                abort(404, 'El archivo solicitado no se encuentra en el servidor.');
            }

            // Retorna el archivo con headers correctos para renderizado inline
            return Storage::disk('private')->response($attachment->file_path);
        } catch (Throwable $e) {
            Log::error('Error al visualizar adjunto de historia clínica', [
                'attachment_id' => $attachment->id,
                'error' => $e->getMessage(),
            ]);

            abort(500, 'Error al visualizar el archivo.');
        }
    }

    /**
     * Elimina un archivo adjunto de la historia clínica.
     * Solo el paciente propietario puede eliminar sus propios archivos.
     */
    public function destroy(PatientHistoryAttachment $attachment): RedirectResponse
    {
        // Validar ownership estricta (solo el propietario puede eliminar)
        $this->validateAttachmentAccess($attachment, requireOwnership: true);

        try {
            // Eliminar el archivo físico del storage privado
            if (Storage::disk('private')->exists($attachment->file_path)) {
                Storage::disk('private')->delete($attachment->file_path);
            }

            // Eliminar el registro de la base de datos
            $attachment->delete();

            return redirect()->back()->with('success', 'El reporte médico ha sido eliminado correctamente de tu historial.');
        } catch (Throwable $e) {
            Log::error('Error al eliminar adjunto de historia clínica', [
                'attachment_id' => $attachment->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Ocurrió un error al eliminar el archivo. Por favor, intenta nuevamente.');
        }
    }
}