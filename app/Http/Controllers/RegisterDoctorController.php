<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

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
            'phone' => ['required', 'string', 'regex:/^[0-9]{10}$/'], 
            'medical_license' => 'nullable|string|max:50',
            'password' => 'required|min:8|confirmed',
            'specialties' => 'required|array|min:1',
            'specialties.*' => 'exists:specialties,id',
        ]);

        $cleanPhone = preg_replace('/[^0-9]/', '', $request->phone);
        $cleanIdentification = str_replace('-', '', $request->nit);

        try {
            // Usamos transacción para asegurar que se cree el usuario Y el doctor
            DB::transaction(function () use ($request, $cleanPhone, $cleanIdentification) {
                // 1. Crear Usuario
                $user = User::create([
                    'name'     => $request->name,
                    'email'    => $request->email,
                    'password' => Hash::make($request->password),
                    'role'     => 'doctor',          
                ]);

                $user->assignRole('doctor');
                
                // 4. Crear Perfil de Doctor asociado
                $doctor = $user->doctor()->create([
                    'medical_license'   => $request->medical_license,
                    'identification'    => $cleanIdentification,
                    'phone'             => $cleanPhone,
                    'validation_status' => 'missing', 
                    'active'            => true
                ]);

                // 5. Guardar las especialidades en la tabla pivote doctor_specialty
                $doctor->specialties()->attach($request->specialties);
            });
        } catch (\Exception $e) {
            dd("Error en el registro desacoplado del SaaS: " . $e->getMessage());
        }

        return redirect()->route('login')->with('success', 'Registro exitoso. Tu perfil médico está en proceso de validación.');
    }
}
