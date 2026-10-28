<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Repositories\AvailabilityRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ContextDoctorController extends Controller
{
    /**
     * Alternar el espacio de trabajo del médico entre Consultorio Particular y Clínicas Corporativas.
     * 
     * 🔒 VALIDACIONES DE SEGURIDAD:
     * - El doctor debe estar activo y aprobado
     * - La clínica debe existir, estar activa y aprobada
     * - El doctor debe estar aprobado en la clínica (status = 'approved')
     * - Se invalida el caché de disponibilidad al cambiar de contexto
     * - Se registra el cambio en los logs de auditoría
     */
    public function switchContext(Request $request, AvailabilityRepository $cacheRepo)
    {
        $request->validate([
            'context_id' => ['nullable', 'string'],
        ]);

        $contextId = $request->input('context_id');
        $user = Auth::user();

        // 🔒 VALIDACIÓN 1: El usuario debe tener perfil médico
        if (!$user || !$user->doctor) {
            abort(403, 'Perfil médico no encontrado o no configurado.');
        }

        /** @var Doctor $doctor */
        $doctor = $user->doctor;

        // 🔒 VALIDACIÓN 2: El doctor debe estar activo y aprobado
        if (!$doctor->active || $doctor->validation_status !== 'approved') {
            abort(403, 'Tu perfil médico no está activo o aprobado. Contacta al soporte.');
        }

        $previousContext = session('doctor_context', ['type' => 'particular']);

        // CASO 1: Regresar al Consultorio Particular e Independiente
        if ($contextId === 'particular' || is_null($contextId)) {
            session(['doctor_context' => [
                'type'  => 'particular',
                'id'    => null,
                'name'  => 'Consultorio Particular',
                'photo' => $user->profile_photo_path,
            ]]);

            Log::info('ContextDoctorController: Cambio a contexto particular', [
                'user_id' => $user->id,
                'doctor_id' => $doctor->id,
                'previous_context' => $previousContext,
            ]);

            // Invalidar caché de disponibilidad del doctor en contexto particular
            $this->invalidateDoctorCache($cacheRepo, $doctor->id, null);

            return back()->with('success', 'Entorno cambiado correctamente a: Consultorio Particular');
        }

        // CASO 2: Cambiar al entorno institucional de una Clínica
        // 🔒 VALIDACIÓN 3: context_id debe ser numérico
        if (!is_numeric($contextId)) {
            abort(400, 'Identificador de clínica inválido.');
        }

        $clinicId = (int) $contextId;

        // 🔒 VALIDACIÓN 4: La clínica debe existir, estar activa y aprobada
        $clinic = Clinic::where('id', $clinicId)
            ->where('active', true)
            ->where('validation_status', 'approved')
            ->first();

        if (!$clinic) {
            abort(404, 'La clínica solicitada no existe, está inactiva o no ha sido aprobada.');
        }

        // 🔒 VALIDACIÓN 5: El doctor debe estar aprobado en esta clínica
        $belongsToClinic = $clinic->doctors()
            ->where('doctor_id', $doctor->id)
            ->where('clinic_doctor.status', 'approved')
            ->exists();

        if (!$belongsToClinic) {
            Log::warning('ContextDoctorController: Intento de acceso no autorizado a clínica', [
                'user_id' => $user->id,
                'doctor_id' => $doctor->id,
                'clinic_id' => $clinicId,
            ]);

            abort(403, 'No tienes acceso autorizado a esta clínica corporativa.');
        }

        // Guardar el contexto dinámico e institucional en la sesión activa
        session(['doctor_context' => [
            'type'  => 'clinic',
            'id'    => $clinicId,
            'name'  => $clinic->user->name ?? 'Clínica Corporativa',
            'photo' => $clinic->user->profile_photo_path ?? null,
        ]]);

        Log::info('ContextDoctorController: Cambio a contexto de clínica', [
            'user_id' => $user->id,
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'clinic_name' => $clinic->user->name,
            'previous_context' => $previousContext,
        ]);

        // Invalidar caché de disponibilidad del doctor en contexto de clínica
        $this->invalidateDoctorCache($cacheRepo, $doctor->id, $clinicId);

        return back()->with('success', 'Entorno cambiado correctamente a: ' . $clinic->user->name);
    }

    /**
     * Invalida el caché de disponibilidad para las sedes donde el doctor atiende.
     * Esto asegura que al cambiar de contexto, la búsqueda pública refleje inmediatamente
     * los cambios en la disponibilidad.
     */
    private function invalidateDoctorCache(AvailabilityRepository $cacheRepo, int $doctorId, ?int $clinicId): void
    {
        try {
            // Obtener las sedes donde el doctor tiene horarios activos
            $addressIds = \App\Models\Schedule::where('doctor_id', $doctorId)
                ->where('is_active', true)
                ->when($clinicId !== null, function ($query) use ($clinicId) {
                    $query->where('clinic_id', $clinicId);
                })
                ->when($clinicId === null, function ($query) {
                    $query->whereNull('clinic_id');
                })
                ->pluck('address_id')
                ->unique()
                ->all();

            if (!empty($addressIds)) {
                foreach ($addressIds as $addressId) {
                    $cacheRepo->invalidateAvailabilityCache($clinicId, [$doctorId], $addressId);
                }
            }
        } catch (\Exception $e) {
            Log::error('ContextDoctorController: Error al invalidar caché', [
                'error' => $e->getMessage(),
                'doctor_id' => $doctorId,
                'clinic_id' => $clinicId,
            ]);
        }
    }
}