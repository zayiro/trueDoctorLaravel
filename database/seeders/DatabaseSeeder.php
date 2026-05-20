<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Specialty;
use App\Models\Doctor;
use App\Models\Patient;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            RoleSeeder::class,
            DivipolaSeeder::class,
            SpecialtySeeder::class,
            InsuranceSeeder::class,
        ]);

        $specialties = Specialty::all();

        // Crear Usuario
        $doctorUser = User::factory()->doctor()->create([
            'name' => 'Dr. Gregory House',
            'email' => 'doctor@ejemplo.com',
            'password' => bcrypt('123456789'),
        ]);

        $doctorUser->assignRole('doctor');

        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'phone' => '3026433877',
            'identification' => '16944752',
            'bio' => 'Experto en diagnósticos complejos y difíciles.',
            'slug' => Str::slug($doctorUser->name) . '-' . Str::lower(Str::random(5)),
            'validation_status' => 'missing',
        ]);

        $doctor->settings()->create([
            'plan_id' => '1', //premium
        ]);

        // Llamamos manualmente a la creación de la sede virtual DESPUÉS de tener plan
        $doctor->createVirtualAddress(); 

        $doctor->specialties()->attach([
            $specialties->where('name', 'Psicólogia')->first()->id,
            $specialties->where('name', 'Medicina General')->first()->id,
        ]);

        // Crear Paciente
        $patientUser = User::factory()->patient()->create([
            'name' => 'John Doe patient',
            'email' => 'paciente@ejemplo.com',
            'password' => bcrypt('123456789'),
        ]);
        
        Patient::create([
            'user_id' => $patientUser->id,
            'identification' => '100000789',
            'phone' => '3001234567',
        ]);

        // Crear Administrador y Clínica
        User::factory()->admin()->create(['name' => 'Admistrador Ocampo', 'email' => 'administrador@ejemplo.com', 'password' => bcrypt('123456789')]);
        User::factory()->clinic()->create(['name' => 'Clínica Mayo', 'email' => 'clinica@ejemplo.com', 'password' => bcrypt('123456789')]);        
    }
}
