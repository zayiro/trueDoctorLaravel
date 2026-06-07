<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Specialty;
use App\Models\Clinic;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class RegisterClinicController extends Controller
{
    /**
     * Muestra el formulario de registro para centros médicos y clínicas.
     */
    public function register() 
    {
        $specialties = Specialty::orderBy('name', 'asc')->get();

        return view('auth.register-clinic', compact('specialties'));
    }

    /**
     * Procesa la creación transaccional de la clínica, su rol en Spatie y su plan institucional.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
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
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
            'specialties' => 'required|array|min:1',
            'specialties.*' => 'exists:specialties,id',
        ], [
            'nit.regex' => 'El formato del NIT debe ser solo números o incluir el dígito de verificación (ej: 12345678-9).',
            'nit.unique' => 'Este NIT ya está registrado en nuestra plataforma.',
            'phone.regex' => 'El número celular debe contener exactamente 10 dígitos numéricos (ej: 3001234567).',
            'reps_code.required' => 'El código de habilitación REPS es obligatorio.',
            'reps_code.digits' => 'El código REPS debe tener exactamente 12 números.',
            'reps_code.unique' => 'Este código REPS ya se encuentra registrado.',
        ]);        

        $cleanPhone = preg_replace('/[^0-9]/', '', trim($request->phone));
        $cleanNit = str_replace('-', '', trim($request->nit));

        $fullPhone = $request->country_code . $cleanPhone;

        try {
            // Estructura transaccional unificada para prevenir inconsistencias en el SaaS
            DB::transaction(function () use ($request, $cleanNit, $fullPhone) {
                
                // 1. Crear el usuario administrador del centro médico
                $user = User::create([
                    'name'     => trim($request->name),
                    'email'    => strtolower(trim($request->email)),
                    'password' => Hash::make($request->password),
                    'role'     => 'clinic',
                ]);

                // 2. Sincronización blindada de permisos con Spatie
                $role = Role::firstOrCreate(['name' => 'clinic']);                
                $user->assignRole($role);

                // 3. Crear el perfil de la clínica
                // Al ejecutarse este create(), Laravel dispara el ClinicObserver para inyectar ClinicSetting y la Sede Virtual
                $clinic = $user->clinic()->create([
                    'name'              => trim($request->name), 
                    'nit'               => $cleanNit,
                    'reps_code'         => trim($request->reps_code), 
                    'phone'             => $fullPhone,
                    'validation_status' => 'missing', 
                    'active'            => true
                ]);
                
                // 4. Guardar las especialidades en la tabla pivote clinic_specialty
                $clinic->specialties()->attach($request->specialties);
            });

            return redirect()->route('login')
                ->with('success', 'Clínica registrada correctamente. Ya puedes iniciar sesión para administrar tu centro médico.');

        } catch (\Exception $e) {            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error durante el registro transaccional de la clínica: ' . $e->getMessage());
        }
    }
}
