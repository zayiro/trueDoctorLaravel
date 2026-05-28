<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoctorClinicController extends Controller
{
    /**
     * Muestra la bandeja de invitaciones y centros médicos vinculados al doctor actual.
     */
    public function index()
    {
        // El Middleware asignado en las rutas ya garantiza que el usuario sea un 'doctor'
        $doctor = Auth::user()->doctor;

        // Listamos todas las clínicas cruzando la tabla intermedia clinic_doctor
        $clinics = $doctor->clinics()
            ->with('user') // Trae los detalles del perfil de usuario de la clínica de forma eficiente
            ->withPivot('status', 'created_at')
            ->orderBy('clinic_doctor.created_at', 'desc')
            ->get();

        return view('partner.doctor_clinics.index', compact('clinics', 'doctor'));
    }

    /**
     * El médico aprueba la invitación de un centro médico corporativo.
     */
    public function accept(Clinic $clinic)
    {
        $doctor = Auth::user()->doctor;

        // Buscamos el registro pivote exacto
        $pivot = $doctor->clinics()->where('clinic_id', $clinic->id)->first()?->pivot;

        if (!$pivot || $pivot->status !== 'pending') {
            return back()->with('error', 'No tienes ninguna solicitud de vinculación pendiente de este centro médico.');
        }

        // Actualizamos el estado a 'approved' en la tabla intermedia
        $doctor->clinics()->updateExistingPivot($clinic->id, [
            'status' => 'approved'
        ]);

        // Cargamos la relación de usuario si el nombre está en la tabla users
        $clinicName = $clinic->user->name ?? $clinic->name;

        return back()->with('success', "Te has vinculado formalmente al cuerpo médico de: {$clinicName}. Desde ahora podrán coordinar tu agenda en sus sedes.");
    }

    /**
     * El médico rechaza la invitación o decide revocar de forma voluntaria su vinculación activa.
     */
    public function reject(Clinic $clinic)
    {
        $doctor = Auth::user()->doctor;

        $pivot = $doctor->clinics()->where('clinic_id', $clinic->id)->first()?->pivot;

        if (!$pivot) {
            abort(404, 'Relación con el centro médico no encontrada.');
        }

        // 🛡️ CONTROL DE INTEGRIDAD OPERATIVA:
        // Si el médico ya estaba activo e intenta retirarse, validamos que no deje citas colgadas
        if ($pivot->status === 'approved') {
            $hasPendingAppointments = Appointment::where('clinic_id', $clinic->id)
                ->where('doctor_id', $doctor->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->where('date', '>=', now()->toDateString())
                ->exists();

            if ($hasPendingAppointments) {
                return back()->with('error', 'No puedes desvincularte de este centro médico porque actualmente tienes citas agendadas con sus pacientes. Debes coordinar la cancelación o reagendamiento con la administración de la clínica primero.');
            }
        }

        // Si es un rechazo de invitación pendiente o una salida limpia, removemos la fila de la tabla pivote
        $doctor->clinics()->detach($clinic->id);

        $mensaje = $pivot->status === 'pending' 
            ? 'La invitación del centro médico ha sido rechazada.' 
            : 'Te has retirado de la nómina de la clínica correctamente.';

        return back()->with('success', $mensaje);
    }
}
