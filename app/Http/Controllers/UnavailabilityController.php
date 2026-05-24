<?php

namespace App\Http\Controllers;

use App\Models\Unavailability;
use App\Models\Appointment;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnavailabilityController extends Controller
{
    /**
     * Resuelve el doctor_id objetivo aplicando filtros estrictos de seguridad.
     */
    private function resolveDoctorId(Request $request, $user)
    {
        if ($user->role === 'clinic') {
            $request->validate(['doctor_id' => 'required|exists:doctors,id']);
            
            // 🛡️ Seguridad: Validar que el médico esté en la nómina de esta clínica
            $isLinked = $user->clinic->doctors()->where('doctor_id', $request->doctor_id)->exists();
            if (!$isLinked) {
                abort(403, 'El especialista seleccionado no trabaja para esta clínica.');
            }
            return $request->doctor_id;
        }

        // Si es doctor, el ID es el suyo de forma directa
        return $user->doctor->id;
    }

    /**
     * Valida la pertenencia de la sede si se envía en la petición.
     */
    private function checkAddressOwnership($addressId, $user)
    {
        if ($addressId) {
            $address = Address::findOrFail($addressId);
            if ($user->role === 'clinic' && $address->clinic_id !== $user->clinic->id) {
                abort(403, 'La sede seleccionada no pertenece a tu clínica.');
            }
            if ($user->role === 'doctor' && $address->doctor_id !== $user->doctor->id) {
                abort(403, 'La sede seleccionada no te pertenece.');
            }
        }
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'address_id' => 'nullable|exists:addresses,id,deleted_at,NULL',
            'reason'     => 'nullable|string|max:255',
        ]);

        // 1. Resolver y blindar el ID del médico afectado
        $doctorId = $this->resolveDoctorId($request, $user);

        // 2. Blindar la propiedad física de la sede seleccionada
        $this->checkAddressOwnership($request->address_id, $user);

        // 🔍 3. BUSCAR CONFLICTOS ANTES DE GUARDAR (Adaptado)
        $conflicts = Appointment::where('doctor_id', $doctorId)
            ->whereBetween('date', [$request->start_date, $request->end_date])
            ->whereIn('status', ['confirmed', 'pending'])
            ->when($request->address_id, function($q) use ($request) {
                return $q->where('address_id', $request->address_id);
            })
            ->with('patient.user')
            ->get();

        // Si hay conflictos y no se ha confirmado forzosamente mediante el flag
        if ($conflicts->count() > 0 && !$request->has('force_save')) {
            return back()->with([
                'conflict_appointments' => $conflicts,
                'old_data' => $request->all()
            ])->withInput();
        }

        // 💾 4. Guardar si no hay conflictos o si se aceptó el riesgo
        Unavailability::create([
            'doctor_id'  => $doctorId,
            'address_id' => $request->address_id,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'reason'     => $request->reason,
        ]);

        return back()->with('success', 'Ausencia registrada correctamente.');
    }

    public function destroy(Unavailability $unavailability)
    {
        $user = Auth::user();

        // 🛡️ 1. Validación estricta de borrado Multi-inquilino
        if ($user->role === 'clinic') {
            // La clínica solo puede borrar ausencias de los médicos de su equipo
            $isLinked = $user->clinic->doctors()->where('doctor_id', $unavailability->doctor_id)->exists();
            if (!$isLinked) {
                return back()->with('error', 'No tienes permiso para modificar las ausencias de este especialista.');
            }
        } else {
            // El doctor independiente solo modifica sus propios registros autónomos
            if ($unavailability->doctor_id !== $user->doctor->id) {
                return back()->with('error', 'No tienes permiso para eliminar esta ausencia.');
            }
        }

        // 2. Ejecutar la eliminación
        $unavailability->delete();

        return back()->with('success', 'El periodo de ausencia ha sido eliminado y el horario vuelve a estar disponible.');
    }
}
