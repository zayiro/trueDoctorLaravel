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
        // 1. Consultamos los usuarios corporativos con validaciones pendientes
        $partners = User::whereIn('role', ['doctor', 'clinic'])
            ->whereHas('doctor', function ($query) {
                $query->whereIn('validation_status', ['pending_validation', 'missing']);
            })
            ->orWhereHas('clinic', function ($query) {
                $query->whereIn('validation_status', ['pending_validation', 'missing']);
            })
            ->with(['doctor', 'clinic']) // Eager Loading preventivo
            ->latest()
            ->paginate(10);

        // 2. Mapeamos la colección para que la vista reciba un objeto uniforme
        $partners->getCollection()->transform(function ($user) {
            $profile = $user->role === 'clinic' ? $user->clinic : $user->doctor;
            
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
            'status' => ['required', 'in:approved,rejected'],
            'doctor_id' => ['required_without:clinic_id', 'exists:doctors,id'],
            'clinic_id' => ['required_without:doctor_id', 'exists:clinics,id'],
        ]);

        $status = $request->status;

        // 1. Identificar e inyectar dinámicamente las columnas asimétricas por Rol
        if ($request->has('clinic_id')) {
            $model = Clinic::findOrFail($request->clinic_id);
            $typeLabel = "La clínica";
            $secondField = 'reps_code_card_path'; // 🔒 Ajustado a la nomenclatura de tu BD de clínicas
        } else {
            $model = Doctor::findOrFail($request->doctor_id);
            $typeLabel = "El médico";
            $secondField = 'professional_card_path';
        }

        // 2. Procesar la lógica transaccional según el nuevo estado
        if ($status === 'rejected') {
            
            // 🔒 CORREGIDO: Eliminación usando el disco 'local' privado unificado
            if ($model->identity_card_path) {
                Storage::delete($model->identity_card_path);
            }
            if ($model->{$secondField}) {
                Storage::delete($model->{$secondField});
            }
            
            $model->update([
                'validation_status' => 'rejected',
                'identity_card_path' => null,
                $secondField         => null,
                'active'             => false // Oculto del directorio del SaaS
            ]);
            
            $message = "{$typeLabel} ha sido rechazado(a) y se le ha solicitado nueva documentación.";
        } else {
            // Activar perfil comercial en el ecosistema público
            $model->update([
                'validation_status' => 'approved',
                'active'            => true 
            ]);

            // Despierta la autogeneración de la Sede Virtual de Telemedicina
            if (method_exists($model, 'createVirtualAddress')) {
                $model->createVirtualAddress();
            }
            
            $message = "¡{$typeLabel} aprobado(a) con éxito! Su perfil ya es público.";
        }

        // 3. Notificación automatizada al usuario
        Mail::to($model->user->email)->send(new ValidationStatusNotification($model->user, $status));

        return redirect()->back()->with('success', $message);
    }

    /**
     * Sirve los archivos confidenciales forzando la visualización inline y segura en el navegador.
     */
    public function viewDocument(Request $request, $type)
    {
        $request->validate([
            'doctor_id' => ['required_without:clinic_id', 'exists:doctors,id'],
            'clinic_id' => ['required_without:doctor_id', 'exists:clinics,id'],
        ]);

        // 1. Resolver el modelo e inyectar el campo dinámico correcto
        if ($request->has('clinic_id')) {
            $model = Clinic::with('user')->findOrFail($request->clinic_id);
            $secondField = 'reps_code_card_path'; // 🔒 Mapeo exacto de la clínica
        } else {
            $model = Doctor::with('user')->findOrFail($request->doctor_id);
            $secondField = 'professional_card_path'; // 🔒 Mapeo exacto del médico
        }

        // 2. Extraer el path almacenado
        $relativePath = ($type === 'cedula') ? $model->identity_card_path : $model->{$secondField};

        // 3. Validar existencia en el disco 'local' privado
        if (!$relativePath || !Storage::exists($relativePath)) {
            abort(404, 'Lo sentimos, el archivo confidencial no se encuentra en el servidor.');
        }

        // 4. Obtención de propiedades físicas del storage protegido
        $fullPath = Storage::path($relativePath);
        $mimeType = Storage::mimeType($relativePath);
        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
        
        $cleanName = $type . '-' . Str::slug($model->user->name) . '.' . $extension;

        // 5. Cabeceras estrictas de inyección inline anti-malware
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $cleanName . '"',
            'X-Content-Type-Options' => 'nosniff', 
            'Content-Length' => filesize($fullPath),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ];

        return response()->file($fullPath, $headers);
    }
}
