<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterClinicController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'clinic_name' => 'required|string|max:255',
            'nit' => [
                'required',
                'string',
                'min:7',
                'max:20',
                'unique:clinics,nit',
                'regex:/^[0-9]+(-[0-9]{1})?$/', // Tu nueva Regex para NIT
            ],
            'phone' => [
                'required',
                'string',
                'min:10',
                'regex:/^([0-9\s\-\+\(\)]*)$/', // Tu nueva Regex para Teléfono
            ],

            // Datos del Usuario Gerente
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ], [
            // Mensajes personalizados para que el usuario entienda los errores de Regex
            'nit.regex' => 'El formato del NIT debe ser solo números o incluir el dígito de verificación (ej: 12345678-9).',
            'phone.regex' => 'El formato del teléfono no es válido. Usa números, espacios o los símbolos + ( ) -',
            'nit.unique' => 'Este NIT ya está registrado en nuestra plataforma.',
        ]);        

        DB::transaction(function () use ($request) {
            // 1. Crear Usuario (Gerente)
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // 2. Asignar Rol de Spatie
            $user->assignRole('clinic'); // Asegúrate de haber creado este rol

            $cleanPhone = preg_replace('/[^0-9+]/', '', $request->phone);
            $cleanNit = str_replace('-', '', $request->nit);

            // 3. Crear Perfil de Clínica
            $user->clinic()->create([
                'name' => $request->clinic_name,
                'nit' => $cleanNit,
                'phone' => $cleanPhone,
                'plan' => 'basico',
            ]);
        });

        return redirect()->route('login')->with('success', 'Clínica registrada. Ya puedes administrar tu centro médico.');
    }
}
