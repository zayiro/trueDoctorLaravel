<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Specialty;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Address;
use App\Models\Schedule;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            RoleSeeder::class,
            CitySeeder::class,
            SpecialtySeeder::class
        ]);
/*
        User::factory()->create(
            [
                'name' => 'Andres ocampo',
                'email' => 'ocampotecnologo@gmail.com',
                'password' => bcrypt('123456789'),
            ]
        );*/

        ///////////////////
        
        // 1. Crear especialidad base
        $specialty = Specialty::create(['name' => 'Urología']);

        // 2. Crear Doctor con Perfil, Dirección y Horario
        $doctorUser = User::factory()->doctor()->create([
            'name' => 'Dr. Gregory House',
            'email' => 'doctor@ejemplo.com',
            'password' => bcrypt('123456789'),
        ]);

        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'specialty_id' => $specialty->id,            
            'bio' => 'Experto en diagnósticos complejos.',
        ]);

        $address = Address::create([
            'doctor_id' => $doctor->id,
            'name' => 'Consultorio Central',
            'address' => 'Calle Falsa 123, Ciudad Médica',
            'phone' => '3026433874',
            'city_id' => 1,
        ]);

        // Crear horario de Lunes a Viernes para esta dirección
        for ($i = 1; $i <= 5; $i++) {
            Schedule::create([
                'address_id' => $address->id,
                'day' => $i,
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'duration' => 30,
            ]);
        }

        // 3. Crear Paciente
        $patientUser = User::factory()->patient()->create([
            'name' => 'John Doe patient',
            'email' => 'paciente@ejemplo.com',
            'password' => bcrypt('123456789'),
        ]);
        
        Patient::create([
            'user_id' => $patientUser->id,
        ]);

        // 4. Crear Administrador y Clínica
        User::factory()->admin()->create(['name' => 'Admistrador Ocampo', 'email' => 'administrador@ejemplo.com', 'password' => bcrypt('123456789')]);
        User::factory()->clinic()->create(['name' => 'Clínica Mayo', 'email' => 'clinica@ejemplo.com', 'password' => bcrypt('123456789')]);        
    }
}
