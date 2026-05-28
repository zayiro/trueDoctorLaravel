<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Clinic;
use App\Models\User;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Notifications\ClinicInvitationNotification;
use App\Notifications\DirectDoctorWelcomeNotification;

class ClinicDoctorController extends Controller
{
    /**
     * Muestra la nómina de médicos vinculados a la clínica actual.
     */
    public function index()
    {
        if (auth()->user()->role !== 'clinic') {
            abort(403, 'Acceso exclusivo para centros médicos.');
        }

        $clinic = auth()->user()->clinic;

        // Listamos los doctores cruzando la tabla pivote clinic_doctor
        $doctors = $clinic->doctors()
            ->with('user') 
            ->withPivot('status', 'created_at')
            ->orderBy('clinic_doctor.created_at', 'desc')
            ->get();

        return view('partner.clinic_doctors.index', compact('doctors', 'clinic'));
    }

    /**
     * Procesa la vinculación: Busca y envía invitación a existentes, o registra desde cero.
     */
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'clinic') {
            abort(403);
        }

        $clinic = auth()->user()->clinic;

        // 🛡️ BLINDAJE MULTI-TENANCY: Control de límites del plan asignado
        if (!$clinic->canAddMoreDoctors()) {
            return back()->with('error', 'Has alcanzado el límite máximo de médicos permitidos en tu plan actual. Considera mejorar tu suscripción.');
        }

        // Determinar el flujo mediante el campo 'action_type' enviado desde el formulario
        $actionType = $request->input('action_type', 'invite');

        if ($actionType === 'invite') {
            return $this->handleInvitationFlow($request, $clinic);
        }

        if ($actionType === 'register_direct') {
            return $this->handleDirectRegistrationFlow($request, $clinic);
        }

        abort(400, 'Acción no soportada.');
    }

    /**
     * FLUJO 1: Invitar a un doctor existente por su número de identificación.
     */
    protected function handleInvitationFlow(Request $request, Clinic $clinic)
    {
        $request->validate([
            'identification' => 'required|string',
        ], [
            'identification.required' => 'Debes ingresar el número de cédula o identificación del médico.',
        ]);

        // Buscamos al médico en la base de datos global del SaaS
        $doctor = Doctor::where('identification', trim($request->identification))->first();

        if (!$doctor) {
            return back()->with('error', 'No se encontró ningún médico registrado en la plataforma con ese número de identificación. Si es un médico propio tuyo, usa la opción de "Registrar Nuevo Médico".')->withInput();
        }

        // Validar si ya está vinculado o tiene solicitud en proceso
        $alreadyLinked = $clinic->doctors()->where('doctor_id', $doctor->id)->exists();
        
        if ($alreadyLinked) {
            return back()->with('error', 'El especialista seleccionado ya se encuentra en tu nómina o tiene una solicitud en proceso.');
        }

        // Entra con estado 'pending' esperando la aprobación del médico independiente
        $clinic->doctors()->attach($doctor->id, [
            'status' => 'pending' 
        ]);

        // Notificación al médico invitado
        $doctor->user->notify(new ClinicInvitationNotification($clinic));

        return redirect()->route('partner.clinic_doctors.index')
            ->with('success', "Se ha enviado una solicitud de vinculación al Dr/a. {$doctor->user->name}. Aparecerá como pendiente hasta que la acepte.");
    }

    /**
     * FLUJO 2: Registrar un médico nuevo que no existe en el SaaS e incorporarlo inmediatamente.
     */
    protected function handleDirectRegistrationFlow(Request $request, Clinic $clinic)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'identification' => 'required|string|unique:doctors,identification',
        ], [
            'name.required' => 'El nombre del médico es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.unique' => 'Este correo electrónico ya está registrado por otro usuario en la plataforma.',
            'identification.required' => 'La identificación o cédula del médico es obligatoria.',
            'identification.unique' => 'Ya existe un médico registrado en el SaaS con este número de identificación.',
        ]);

        try {
            DB::transaction(function () use ($request, $clinic) {
                // Crear usuario base con contraseña aleatoria segura
                $user = User::create([
                    'name' => trim($request->name),
                    'email' => trim($request->email),
                    'password' => Hash::make(Str::random(16)), 
                    'role' => 'doctor',
                ]);

                // Asignación de rol de Spatie
                $user->assignRole('doctor');

                // Crear el perfil del médico (Dispara el Observer para configurar DoctorSetting y VirtualAddress)
                $doctor = Doctor::create([
                    'user_id' => $user->id,
                    'identification' => trim($request->identification),
                ]);

                // Vincular directamente a la clínica como 'approved'
                $clinic->doctors()->attach($doctor->id, [
                    'status' => 'approved'
                ]);

                // Notificación con enlace de activación de cuenta
                $user->notify(new DirectDoctorWelcomeNotification($clinic));
            });

            return redirect()->route('partner.clinic_doctors.index')
                ->with('success', "El médico ha sido registrado en la plataforma e incorporado a tu nómina exitosamente.");

        } catch (\Exception $e) {
            return back()->with('error', 'Ocurrió un error inesperado al procesar el registro del médico. Inténtalo de nuevo.')->withInput();
        }
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

        $pivot = $clinic->doctors()->where('doctor_id', $doctor->id)->first()?->pivot;

        if (!$pivot) {
            abort(404, 'Relación de nómina no encontrada.');
        }

        // Si el estado es 'pending', no permitimos cambiarlo desde la clínica de forma manual
        if ($pivot->status === 'pending') {
            return back()->with('error', 'No puedes alterar el estado de un médico cuya solicitud aún está pendiente de aprobación.');
        }

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

        // 🛡️ CONTROL DE INTEGRIDAD: Validar si el médico tiene citas vigentes en esta clínica antes de removerlo
        $hasPendingAppointments = Appointment::where('clinic_id', $clinic->id)
            ->where('doctor_id', $doctor->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('date', '>=', now()->toDateString())
            ->exists();

        if ($hasPendingAppointments) {
            return back()->with('error', 'No puedes desvincular a este médico porque actualmente tiene citas agendadas con pacientes en tus sedes. Debes cancelar o reagendar las citas primero.');
        }

        $clinic->doctors()->detach($doctor->id);

        return back()->with('success', 'El especialista ha sido removido de la nómina de la clínica correctamente.');
    }

    /**
     * Reenvía el correo electrónico de invitación a un médico aliado.
     */
    public function resendInvitation(Doctor $doctor)
    {
        $clinic = auth()->user()->clinic;
        
            // Validar que el médico realmente esté vinculado a esta clínica
            $isLinked = $clinic->doctors()
                ->where('doctor_id', $doctor->id)
                ->where('clinic_doctor.status', 'pending') // Solo si aún está pendiente
                ->exists();

            if (!$isLinked) {
                return redirect()->back()
                ->with('error', 'No se encontró ninguna invitación pendiente para este especialista.');
            }

            // Disparar la notificación cargando la relación del usuario
            $doctor->user->notify(new ClinicInvitationNotification($clinic));

            return redirect()->back()->with('success', 'La invitación ha sido reenviada con éxito al médico.');
    }
}
