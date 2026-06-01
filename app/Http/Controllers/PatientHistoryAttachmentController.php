<?php

namespace App\Http\Controllers;

use App\Models\PatientHistoryAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientHistoryAttachmentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);

        $patient = $request->user()->patient;        

        if (!$patient) {
            abort(404, 'No se encontró un perfil de paciente asociado a este usuario.');
        }

        $file = $request->file('file');
        
        // 1. CAMBIO AQUÍ: Usamos tu disco 'private'
        $path = $file->store("patients/{$patient->id}/attachments", 'private');

        PatientHistoryAttachment::create([
            'patient_id' => $patient->id,
            'name'       => $request->input('name'),
            'file_path'  => $path,
            'file_type'  => $file->getMimeType(),
            'file_size'  => $file->getSize(),
        ]);

        return redirect()->back()->with('success', 'Tu reporte médico ha sido anexado correctamente al historial.');
    }

    public function download(PatientHistoryAttachment $attachment): StreamedResponse
    {
        $patient = $attachment->history ? $attachment->history->patient : auth()->user()->patient;

        if (auth()->id() !== $patient->user_id && !auth()->user()->hasRole(['doctor', 'clinic', 'admin'])) {
            abort(403, 'No tienes autorización para acceder a este documento médico.');
        }

        // 2. CAMBIO AQUÍ: Validamos y descargamos usando tu disco 'private'
        if (!Storage::disk('private')->exists($attachment->file_path)) {
            abort(404, 'El archivo solicitado no se encuentra en el servidor.');
        }

        return Storage::disk('private')->download($attachment->file_path, $attachment->name);
    }

    /**
     * Muestra el contenido del archivo de forma segura en el navegador sin forzar la descarga.
     */
    public function viewHistoryAttachment(PatientHistoryAttachment $attachment)
    {
        // Validamos con la tabla users usando el patient_id del paciente
        $isOwner = auth()->id() === $attachment->patient_id;
        $hasAuthorizedRole = auth()->user()->hasAnyRole(['patient', 'doctor', 'clinic', 'admin']);

        if (!$isOwner && !$hasAuthorizedRole) {
            abort(403, 'No tienes autorización para visualizar este documento médico.');
        }

        // Retorna el archivo con los headers correctos para ser renderizado nativamente (inline)
        return Storage::disk('private')->response($attachment->file_path);
    }

    /**
     * Elimina físicamente el archivo del storage privado y el registro de la base de datos.
     * Valida de forma estricta que solo el paciente propietario pueda borrarlo.
     */
    public function destroy(PatientHistoryAttachment $attachment)
    {
        // Validamos con la tabla users usando el patient_id del paciente
        $isOwner = auth()->id() === $attachment->patient_id;
        $hasAuthorizedRole = auth()->user()->hasAnyRole(['patient']);

        if (!$isOwner && !$hasAuthorizedRole) {
            abort(403, 'No tienes autorización para eliminar este documento médico.');
        }

        // 1. Borrar el archivo físico del almacenamiento privado si existe
        if (Storage::disk('private')->exists($attachment->file_path)) {
            Storage::disk('private')->delete($attachment->file_path);
        }

        // 2. Eliminar el registro en la base de datos
        $attachment->delete();

        return redirect()->back()->with('success', 'El reporte médico ha sido eliminado correctamente de tu historial.');
    }
}
