<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Specialty;
use App\Models\City;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Address;
use App\Models\Schedule;

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
            RoleSeeder::class,
            CitySeeder::class,
            SpecialtySeeder::class
        ]);

        $specialties = Specialty::all();

        // 1. Crear Doctor con Perfil, Dirección y Horario
        $doctorUser = User::factory()->doctor()->create([
            'name' => 'Dr. Gregory House',
            'email' => 'doctor@ejemplo.com',
            'password' => bcrypt('123456789'),
        ]);

        $doctorUser->assignRole('doctor');

        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'phone' => '3026433877',
            'plan' => 'basico',            
            'bio' => 'Experto en diagnósticos complejos y difíciles.',
            'slug' => Str::slug($doctorUser->name) . '-' . Str::lower(Str::random(5)),
        ]);

        $doctor->specialties()->attach([
            $specialties->where('name', 'Psicólogia')->first()->id,
            $specialties->where('name', 'Medicina General')->first()->id,
        ]);

        $city = City::where('slug', 'bogota')->first();

        $address = Address::create([
            'doctor_id' => $doctor->id,
            'name' => 'Consultorio Central',
            'address' => 'Calle Falsa 123, Ciudad Médica',
            'phone' => '3026433874',
            'city_id' => $city->id,
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
