<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Specialty;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RegisterClinicController extends Controller
{
    public function register() 
    {
        $specialties = Specialty::orderBy('name', 'asc')->get();

        return view('auth.register-clinic', compact('specialties'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nit' => [
                'required',
                'string',
                'min:7',
                'max:20',
                'unique:clinics,nit',
                'regex:/^[0-9]+(-[0-9]{1})?$/', 
            ],
            'reps_code' => [
                'required',
                'string',
                'digits:12', 
                Rule::unique('clinics', 'reps_code'), 
            ],
            'phone' => ['required', 'string', 'regex:/^[0-9]{10}$/'], 
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ], [
            'nit.regex' => 'El formato del NIT debe ser solo números o incluir el dígito de verificación (ej: 12345678-9).',
            'nit.unique' => 'Este NIT ya está registrado en nuestra plataforma.',
            'phone.regex' => 'El número celular debe contener exactamente 10 dígitos numéricos (ej: 3001234567).',
            'reps_code.required' => 'El código de habilitación REPS es obligatorio.',
            'reps_code.digits' => 'El código REPS debe tener exactamente 12 números.',
            'reps_code.unique' => 'Este código REPS ya se encuentra registrado.',
        ]);        

        $cleanPhone = preg_replace('/[^0-9]/', '', $request->phone);
        $cleanNit = str_replace('-', '', $request->nit);

        try {
            DB::transaction(function () use ($request, $cleanNit, $cleanPhone) {
                // 1. Crear Usuario Gerente
                $user = User::create([
                    'name'     => $request->name,
                    'email'    => $request->email,
                    'password' => Hash::make($request->password),
                    'role'     => 'clinic',
                ]);

                // 2. Asignar Rol con Spatie
                $user->assignRole('clinic');

                // 3. Crear Perfil de Clínica
                // 🔥 Al ejecutarse este create(), Laravel dispara el ClinicObserver automáticamente en segundo plano
                $clinic = $user->clinic()->create([
                    'name'              => $request->name, 
                    'nit'               => $cleanNit,
                    'reps_code'         => $request->reps_code, 
                    'phone'             => $cleanPhone,
                    'validation_status' => 'missing', 
                    'active'            => true
                ]);
                
                // 4. Sincronizar especialidades médicas seleccionadas
                if ($request->has('specialties')) {
                    $clinic->specialties()->sync($request->specialties);
                }
            });

            return redirect()->route('login')
                ->with('success', 'Clínica registrada correctamente. Ya puedes iniciar sesión para administrar tu centro médico.');

        } catch (\Exception $e) {
            dd("Error en el registro desacoplado del SaaS: " . $e->getMessage());
        }
    }
}
