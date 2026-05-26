<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Mail\ValidationStatusNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ValidationController extends Controller
{
    /**
     * Muestra la lista unificada de médicos y clínicas esperando validación.
     */
    public function index()
    {
        // 1. Consultamos los usuarios que tengan rol de doctor o clínica
        // y que sus perfiles asociados tengan estados de validación pendientes
        $partners = User::whereIn('role', ['doctor', 'clinic'])
            ->whereHas('doctor', function ($query) {
                $query->whereIn('validation_status', ['pending_validation', 'missing']);
            })
            ->orWhereHas('clinic', function ($query) {
                $query->whereIn('validation_status', ['pending_validation', 'missing']);
            })
            ->with(['doctor', 'clinic']) // Carga previa de perfiles (Eager Loading)
            ->latest()
            ->paginate(10);

        // 2. Mapeamos la colección para que la vista reciba un objeto uniforme ($partner)
        $partners->getCollection()->transform(function ($user) {
            $profile = $user->role === 'clinic' ? $user->clinic : $user->doctor;
            
            // Inyectamos el objeto user dentro del perfil para mantener consistencia en la vista
            if ($profile) {
                $profile->user = $user;
            }
            return $profile;
        });

        return view('administrator.validation.index', compact('partners'));
    }

    /**
     * Actualiza el estado del perfil comercial (Aprobado o Rechazado).
     */
    public function update(Request $request)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'doctor_id' => 'required_without:clinic_id|exists:doctors,id',
            'clinic_id' => 'required_without:doctor_id|exists:clinics,id',
        ]);

        $status = $request->status;

        // 1. Identificar si estamos auditando un Doctor o una Clínica
        if ($request->has('clinic_id')) {
            $model = Clinic::findOrFail($request->clinic_id);
            $typeLabel = "La clínica";
        } else {
            $model = Doctor::findOrFail($request->doctor_id);
            $typeLabel = "El médico";
        }

        // 2. Procesar lógica según el nuevo estado de validación
        if ($status === 'rejected') {
            // Borrar archivos físicos del almacenamiento de forma segura
            if ($model->identity_card_path) Storage::disk('public')->delete($model->identity_card_path);
            if ($model->professional_card_path) Storage::disk('public')->delete($model->professional_card_path);
            
            $model->update([
                'validation_status' => 'rejected',
                'identity_card_path' => null,
                'professional_card_path' => null,
                'active' => false // Se mantiene oculto del directorio del SaaS
            ]);
            
            $message = "{$typeLabel} ha sido rechazado(a) y se le ha solicitado nueva documentación.";
        } else {
            // Activar perfil comercial en el ecosistema
            $model->update([
                'validation_status' => 'approved',
                'active' => true // Ya es visible en búsquedas del directorio
            ]);

            // Autogenerar su sede virtual por defecto (Soporta Doctor y Clinic vía Observers o Métodos directos)
            if (method_exists($model, 'createVirtualAddress')) {
                $model->createVirtualAddress();
            }
            
            $message = "¡{$typeLabel} aprobado(a) con éxito! Su perfil ya es público.";
        }

        // 3. Notificar automáticamente al usuario principal por correo electrónico
        Mail::to($model->user->email)->send(new ValidationStatusNotification($model->user, $status));

        return redirect()->back()->with('success', $message);
    }

    /**
     * Sirve los archivos de forma segura forzando la visualización inline en el navegador.
     */
    public function viewDocument(Request $request, $type)
    {
        $request->validate([
            'doctor_id' => 'required_without:clinic_id|exists:doctors,id',
            'clinic_id' => 'required_without:doctor_id|exists:clinics,id',
        ]);

        // 1. Resolver el modelo correspondiente
        if ($request->has('clinic_id')) {
            $model = Clinic::with('user')->findOrFail($request->clinic_id);
        } else {
            $model = Doctor::with('user')->findOrFail($request->doctor_id);
        }

        // 2. Extraer el path relativo guardado en la base de datos
        $relativePath = ($type === 'cedula') ? $model->identity_card_path : $model->professional_card_path;

        // 3. Validar la existencia real utilizando el disco configurado
        if (!$relativePath || !Storage::disk('public')->exists($relativePath)) {
            abort(404, 'Lo sentimos, el archivo físico no se encuentra en el servidor.');
        }

        // 4. Obtener rutas y propiedades del archivo de forma segura
        $fullPath = Storage::disk('public')->path($relativePath);
        $mimeType = Storage::disk('public')->mimeType($relativePath);
        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
        
        $cleanName = $type . '-' . Str::slug($model->user->name) . '.' . $extension;

        // 5. Encabezados de seguridad e inyección inline estricta para el navegador
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $cleanName . '"',
            'X-Content-Type-Options' => 'nosniff', // Previene sniffing de código malicioso
            'Content-Length' => filesize($fullPath),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ];

        return response()->file($fullPath, $headers);
    }
}
