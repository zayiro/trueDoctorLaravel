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
            'identification' => 'required|string|max:30|unique:doctors,identification',
            'phone' => 'required|string|max:20', // <-- ¡FALTA AGREGAR ESTO!
            'medical_license' => 'nullable|string|max:50',
            'password' => 'required|min:8|confirmed',
            'specialties' => 'required|array|min:1',
            'specialties.*' => 'exists:specialties,id',
        ]);

        // Usamos transacción para asegurar que se cree el usuario Y el doctor
        DB::transaction(function () use ($request) {
            // 1. Crear Usuario
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'doctor',
            ]);

            // 2. Crear Perfil de Doctor asociado
            $doctor = $user->doctor()->create([
                'medical_license' => $request->medical_license,
                'identification' => $request->identification,
                'phone' => $request->phone,
            ]);

            // 3. Guardar en la tabla pivote doctor_specialty
            $doctor->specialties()->attach($request->specialties);
        });

        return redirect()->route('login')->with('success', 'Registro exitoso. Tu perfil médico está en proceso de validación.');
    }
}
