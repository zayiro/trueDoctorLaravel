<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Clinic;
use App\Models\User;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClinicDoctorController extends Controller
{
    /**
     * Muestra la nómina de médicos vinculados a la clínica actual.
     */
    public function index()
    {
        // Forzamos que solo las cuentas con rol de clínica puedan ver este panel
        if (auth()->user()->role !== 'clinic') {
            abort(403, 'Acceso exclusivo para centros médicos.');
        }

        $clinic = auth()->user()->clinic;

        // Listamos los doctores de su nómina cruzando la tabla pivote clinic_doctor
        $doctors = $clinic->doctors()
            ->with('user') // Cargamos el usuario para obtener el nombre y foto de perfil
            ->withPivot('status', 'created_at')
            ->orderBy('clinic_doctor.created_at', 'desc')
            ->get();

        return view('partner.clinic_doctors.index', compact('doctors', 'clinic'));
    }

    /**
     * Procesa la invitación o vinculación directa de un médico usando su número de identificación.
     */
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'clinic') {
            abort(403);
        }

        $clinic = auth()->user()->clinic;

        $request->validate([
            'identification' => 'required|string',
        ], [
            'identification.required' => 'Debes ingresar el número de cédula o identificación del médico.',
        ]);

        // 1. Buscamos al médico en la base de datos global del SaaS
        $doctor = Doctor::where('identification', trim($request->identification))->first();

        if (!$doctor) {
            return back()->with('error', 'No se encontró ningún médico registrado en la plataforma con ese número de identificación. El especialista debe registrarse primero en el SaaS.')->withInput();
        }

        // 2. Validamos si el médico ya se encuentra vinculado o invitado en esta clínica
        $alreadyLinked = $clinic->doctors()->where('doctor_id', $doctor->id)->exists();
        
        if ($alreadyLinked) {
            return back()->with('error', 'El especialista seleccionado ya se encuentra en tu nómina o tiene una solicitud en proceso.');
        }

        // 3. Registramos la vinculación en la tabla pivote clinic_doctor
        // Por defecto el estado entra como 'approved' (vinculación directa) o puedes cambiarlo a 'pending' si requiere aprobación del médico
        $clinic->doctors()->attach($doctor->id, [
            'status' => 'approved' 
        ]);

        return redirect()->route('partner.clinic_doctors.index')
            ->with('success', "El Dr/a. {$doctor->user->name} ha sido vinculado exitosamente a tu centro médico.");
    }

    /**
     * Modifica el estado operativo del médico dentro de la clínica (Activar/Desactivar).
     */
    public function toggleStatus(Doctor $doctor)
    {
        if (auth()->user()->role !== 'clinic') {
            abort(403);
        }

        $clinic = auth()->user()->clinic;

        // Buscamos el registro pivote exacto
        $pivot = $clinic->doctors()->where('doctor_id', $doctor->id)->first()?->pivot;

        if (!$pivot) {
            abort(404, 'Relación de nómina no encontrada.');
        }

        // Cambiamos el estado entre 'approved' (activo) e 'inactive' (inactivo)
        $newStatus = $pivot->status === 'approved' ? 'inactive' : 'approved';

        $clinic->doctors()->updateExistingPivot($doctor->id, [
            'status' => $newStatus
        ]);

        $mensajeText = $newStatus === 'approved' ? 'reactivado en' : 'suspendido temporalmente de';

        return back()->with('success', "El especialista ha sido {$mensajeText} la nómina corporativa.");
    }

    /**
     * Remueve por completo a un médico de la nómina de la clínica.
     */
    public function destroy(Doctor $doctor)
    {
        if (auth()->user()->role !== 'clinic') {
            abort(403);
        }

        $clinic = auth()->user()->clinic;

        // 🛡️ CONTROL DE INTEGRIDAD: Validar si el médico tiene citas confirmadas en esta clínica antes de despedirlo
        $hasPendingAppointments = Appointment::where('clinic_id', $clinic->id)
            ->where('doctor_id', $doctor->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('date', '>=', now()->toDateString())
            ->exists();

        if ($hasPendingAppointments) {
            return back()->with('error', 'No puedes desvincular a este médico porque actualmente tiene citas agendadas con pacientes en tus sedes. Debes cancelar o reagendar las citas primero.');
        }

        // Desvinculación atómica de la tabla intermedia clinic_doctor
        $clinic->doctors()->detach($doctor->id);

        return back()->with('success', 'El especialista ha sido removido de la nómina de la clínica correctamente.');
    }
}
