<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            ['name' => 'Cali', 'state' => 'Valle del Cauca'],
            ['name' => 'Bogotá', 'state' => 'Cundinamarca'],
            ['name' => 'Medellín', 'state' => 'Antioquia'],
            ['name' => 'Barranquilla', 'state' => 'Atlántico'],
            ['name' => 'Cartagena', 'state' => 'Bolívar'],
        ];

        foreach ($cities as $city) {
            City::create([
                'name'  => $city['name'],
                'state' => $city['state'],
                'slug'  => Str::slug($city['name']), // Convierte "Bogotá" en "bogota"
            ]);
        }
    }
}
