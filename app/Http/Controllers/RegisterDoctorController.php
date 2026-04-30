<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterDoctorController extends Controller
{
    public function register() 
    {
        $specialties = Specialty::orderBy('name', 'asc')->get();

        return view('auth.register-doctor', compact('specialties'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'specialty_id' => 'required|integer|exists:specialties,id',
        ]);

        // Usamos transacción para asegurar que se cree el usuario Y el doctor
        DB::transaction(function () use ($request) {
            // 1. Crear Usuario
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // 2. Asignar Rol de Spatie
            $user->assignRole('doctor');

            // 3. Crear Perfil de Doctor asociado
            $user->doctor()->create([
                'specialty_id' => $request->specialty_id, 
                'plan' => 'basico', // Plan inicial por defecto
            ]);
        });

        return redirect()->route('login')->with('success', 'Registro exitoso. Ya puedes iniciar sesión.');
    }
}
