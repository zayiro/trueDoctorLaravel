<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Models\Patient;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;
use Spatie\Permission\Models\Role;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // 1. Validaciones estándar por defecto de Fortify
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        // 2. Crear el Usuario base con su rol
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'role'     => 'patient',  
        ]);

        // ESTO ES LO QUE LE ASIGNA EL ROL EN SPATIE:
        $role = Role::firstOrCreate(['name' => 'patient']);        
        $user->assignRole($role);

        // 3. Crear el registro en la tabla patients usando solo el ID generado
        Patient::create([
            'user_id' => $user->id,
            'phone'   => '', 
        ]);

        return $user;
    }
}
