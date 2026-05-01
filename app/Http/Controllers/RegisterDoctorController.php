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
            'specialties' => 'required|array|min:1', // Debe ser un array
            'specialties.*' => 'exists:specialties,id',
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
            $doctor =   $user->doctor()->create([
                'phone' => $request->phone,
                'plan' => 'basico', // Plan inicial por defecto
            ]);

            $doctor->specialties()->attach($request->specialties);
        });

        return redirect()->route('login')->with('success', 'Registro exitoso. Ya puedes iniciar sesión.');
    }
}
