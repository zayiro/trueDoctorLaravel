<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Appointment;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class UserManagementController extends Controller
{
    /**
     * Muestra el directorio global de usuarios con filtros de búsqueda y rol.
     */
    public function index(Request $request)
    {
        // 1. Inicializar la consulta base excluyendo al administrador logueado por seguridad
        $query = User::where('id', '!=', Auth::id());

        // 2. Aplicar filtro por término de búsqueda (Nombre o Email)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 3. Aplicar filtro estricto por Rol (doctor, clinic, patient, admin)
        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        // 4. Paginación optimizada de registros
        $users = $query->latest()->paginate(15)->withQueryString();

        return view('administrator.users.index', compact('users'));
    }

    /**
     * Muestra los mensajes de contactenos.
     */
    public function contactMessages(Request $request)
    {
        $contactMessages = ContactMessage::orderBy('id', 'asc')->paginate(15);

        return view('administrator.conctact.index', compact('contactMessages'));
    }

    /**
     * Activa o desactiva manualmente el acceso de un usuario al SaaS (Acción Urgente).
     */
    public function toggleStatus(User $user)
    {
        // Evitar que el administrador se bloquee a sí mismo
        if ($user->id === Auth::id()) {
            return redirect()->back()->with('error', 'No puedes alterar tu propio estado de cuenta.');
        }

        $profile = null;
        $isPatient = false;

        if ($user->role === 'doctor') {
            $profile = $user->doctor;
        } elseif ($user->role === 'clinic') {
            $profile = $user->clinic;
        } elseif ($user->role === 'patient') {
            $profile = $user->patient;
            $isPatient = true;
        }

        // Invertimos el estado actual
        $newStatus = !($profile ? $profile->active : true);

        if ($profile) {
            $newStatus = !$profile->active;
            $profile->update(['active' => $newStatus]);
            
        }

        $statusText = $newStatus ? 'activada' : 'desactivada';
        return redirect()->back()->with('success', "La cuenta de {$user->name} ha sido {$statusText} correctamente.");
    }

    /**
     * Dispara manualmente un correo de restablecimiento de contraseña para soporte técnico.
     */
    public function sendResetLink(User $user)
    {
        $status = Password::broker()->sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return redirect()->back()->with('success', 'Enlace de restauración enviado al correo del usuario.');
        }

        return redirect()->back()->with('error', 'No se pudo procesar el envío del correo en este momento.');
    }

    /**
     * Muestra el formulario para crear un nuevo administrador.
     */
    public function createAdmin()
    {
        return view('administrator.users.create-admin');
    }

    /**
     * Guarda el nuevo administrador con el rol 'admin' predefinido.
     */
    public function storeAdmin(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => Hash::make($request->password),
            'role'              => 'admin', // Forzado a nivel de backend por seguridad
            'email_verified_at' => now(),   // Autoverificado al ser cuenta del staff
        ]);

        // ESTO ES LO QUE LE ASIGNA EL ROL EN SPATIE:
        $role = Role::firstOrCreate(['name' => 'admin']);        
        $user->assignRole($role);

        return redirect()->route('administrator.users.index')
            ->with('success', 'Nuevo administrador creado correctamente en el sistema.');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'No puedes eliminarte a ti mismo.');
        }

        try {
            DB::transaction(function () use ($user) {
                match ($user->role) {
                    'patient' => $this->deletePatient($user),
                    'doctor'  => $this->deleteDoctor($user),
                    'clinic'  => $this->deleteClinic($user),
                    default   => null,
                };

                $user->delete();
            });

            return back()->with('success', 'Usuario eliminado correctamente del sistema.');

        } catch (\Throwable $e) {
            \Log::error('Error eliminando usuario: ' . $e->getMessage());
            return back()->with('error', 'Ocurrió un error al eliminar el usuario.');
        }
    }

    private function deletePatient(User $user): void
    {
        $patient = $user->patient;
        if (!$patient) return;

        // RESTRICT → manejar manualmente
        Appointment::where('patient_id', $patient->id)
            ->update(['status' => 'cancelled', 'patient_id' => null]);

        DB::table('signed_documents')->where('patient_id', $patient->id)->delete();
        DB::table('patient_consents')->where('patient_id', $patient->id)->delete();

        // CASCADE automático al borrar patient:
        // patient_allergies, patient_family_histories, patient_histories,
        // patient_history_attachments, patient_medications, patient_surgeries,
        // affected_appointments, reviews, exam_analyses
        $patient->delete();
    }

    private function deleteDoctor(User $user): void
    {
        $doctor = $user->doctor;
        if (!$doctor) return;

        // RESTRICT → manejar manualmente
        Appointment::where('doctor_id', $doctor->id)
            ->update(['status' => 'cancelled', 'doctor_id' => null]);

        DB::table('signed_documents')->where('doctor_id', $doctor->id)->delete();
        DB::table('patient_consents')->where('doctor_id', $doctor->id)->delete();

        // CASCADE automático al borrar doctor:
        // addresses, campaigns, clinic_doctor, doctor_settings, doctor_specialty,
        // medical_expertises, patient_histories, schedules, subscriptions,
        // unavailabilities, affected_appointments, service_specialty
        $doctor->delete();
    }

    private function deleteClinic(User $user): void
    {
        $clinic = $user->clinic;
        if (!$clinic) return;

        // appointments.clinic_id es SET NULL, no necesita manejo manual
        // CASCADE automático al borrar clinic:
        // addresses, clinic_doctor, clinic_settings, clinic_specialty, schedules
        $clinic->delete();
    }
}
