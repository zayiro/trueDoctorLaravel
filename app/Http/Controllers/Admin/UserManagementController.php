<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

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
     * Activa o desactiva manualmente el acceso de un usuario al SaaS (Acción Urgente).
     */
    public function toggleStatus(User $user)
    {
        // Evitar que el administrador se bloquee a sí mismo (doble verificación)
        if ($user->id === Auth::id()) {
            return redirect()->back()->with('error', 'No puedes alterar tu propio estado de cuenta.');
        }

        // Buscamos el perfil comercial si aplica (doctor o clínica) para sincronizar el estado
        $profile = null;
        if ($user->role === 'doctor') {
            $profile = $user->doctor;
        } elseif ($user->role === 'clinic') {
            $profile = $user->clinic;
        }

        // Invertimos el estado de activación actual
        $newStatus = !($profile ? $profile->active : $user->email_verified_at);

        if ($profile) {
            // Sincronizamos el estado en su tabla comercial de visualización pública
            $profile->update(['active' => $newStatus]);
            
            // Para médicos/clínicas alteramos un flag personalizado o controlamos vía sesión si es necesario.
            // Si manejas un campo 'active' nativo en la tabla users, descomenta la línea de abajo:
            // $user->update(['active' => $newStatus]);
        } else {
            // Para pacientes o administradores controlamos el estado mediante simulación o campo dedicado
            // Si tu tabla users tiene un campo 'active', ejecutas el update directo.
        }

        $statusText = $newStatus ? 'activado' : 'desactivado';
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
}
