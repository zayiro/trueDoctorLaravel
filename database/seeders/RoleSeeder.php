<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Catálogo maestro de roles requeridos en el SaaS
        $roles = [
            'patient',
            'doctor',
            'admin',
            'receptionist',
            'clinic'
        ];

        // 2. Creación segura e idéntica de roles (Evita duplicados si se corre múltiples veces)
        foreach ($roles as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName
            ]);
        }

        // 3. Asignación automática de permisos al Administrador Global
        // Una vez asegurados los roles, buscamos al admin y le otorgamos todos los permisos maestros
        $adminRole = Role::where('name', 'admin')->first();
        
        if ($adminRole && Permission::exists()) {
            // Solo sincroniza si ya has corrido un seeder de permisos previamente
            $adminRole->givePermissionTo(Permission::all());
        }
    }
}
