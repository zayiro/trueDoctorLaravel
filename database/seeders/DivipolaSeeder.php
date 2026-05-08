<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DivipolaSeeder extends Seeder
{
    public function run()
    {
        $departments = [
            ['id' => '05', 'name' => 'Antioquia'], ['id' => '08', 'name' => 'Atlántico'],
            ['id' => '11', 'name' => 'Bogotá D.C.'], ['id' => '13', 'name' => 'Bolívar'],
            ['id' => '15', 'name' => 'Boyacá'], ['id' => '17', 'name' => 'Caldas'],
            ['id' => '18', 'name' => 'Caquetá'], ['id' => '19', 'name' => 'Cauca'],
            ['id' => '20', 'name' => 'Cesar'], ['id' => '23', 'name' => 'Córdoba'],
            ['id' => '25', 'name' => 'Cundinamarca'], ['id' => '27', 'name' => 'Chocó'],
            ['id' => '41', 'name' => 'Huila'], ['id' => '44', 'name' => 'La Guajira'],
            ['id' => '47', 'name' => 'Magdalena'], ['id' => '50', 'name' => 'Meta'],
            ['id' => '52', 'name' => 'Nariño'], ['id' => '54', 'name' => 'Norte de Santander'],
            ['id' => '63', 'name' => 'Quindío'], ['id' => '66', 'name' => 'Risaralda'],
            ['id' => '68', 'name' => 'Santander'], ['id' => '70', 'name' => 'Sucre'],
            ['id' => '73', 'name' => 'Tolima'], ['id' => '76', 'name' => 'Valle del Cauca'],
            ['id' => '81', 'name' => 'Arauca'], ['id' => '85', 'name' => 'Casanare'],
            ['id' => '86', 'name' => 'Putumayo'], ['id' => '88', 'name' => 'Archipiélago de San Andrés'],
            ['id' => '91', 'name' => 'Amazonas'], ['id' => '94', 'name' => 'Guainía'],
            ['id' => '95', 'name' => 'Guaviare'], ['id' => '97', 'name' => 'Vaupés'],
            ['id' => '99', 'name' => 'Vichada']
        ];

        DB::table('departments')->insert($departments);

        $cities = [
            ['id' => '05001', 'department_id' => '05', 'name' => 'Medellín', 'slug' => 'medellin'],
            ['id' => '08001', 'department_id' => '08', 'name' => 'Barranquilla', 'slug' => 'barranquilla'],
            ['id' => '11001', 'department_id' => '11', 'name' => 'Bogotá', 'slug' => 'bogota'],
            ['id' => '13001', 'department_id' => '13', 'name' => 'Cartagena', 'slug' => 'cartagena'],
            ['id' => '15001', 'department_id' => '15', 'name' => 'Tunja', 'slug' => 'tunja'],
            ['id' => '17001', 'department_id' => '17', 'name' => 'Manizales', 'slug' => 'manizales'],
            ['id' => '19001', 'department_id' => '19', 'name' => 'Popayán', 'slug' => 'popayan'],
            ['id' => '20001', 'department_id' => '20', 'name' => 'Valledupar', 'slug' => 'valledupar'],
            ['id' => '23001', 'department_id' => '23', 'name' => 'Montería', 'slug' => 'monteria'],
            ['id' => '41001', 'department_id' => '41', 'name' => 'Neiva', 'slug' => 'neiva'],
            ['id' => '44001', 'department_id' => '44', 'name' => 'Riohacha', 'slug' => 'riohacha'],
            ['id' => '47001', 'department_id' => '47', 'name' => 'Santa Marta', 'slug' => 'santa-marta'],
            ['id' => '50001', 'department_id' => '50', 'name' => 'Villavicencio', 'slug' => 'villavicencio'],
            ['id' => '52001', 'department_id' => '52', 'name' => 'Pasto', 'slug' => 'pasto'],
            ['id' => '54001', 'department_id' => '54', 'name' => 'Cúcuta', 'slug' => 'cucuta'],
            ['id' => '63001', 'department_id' => '63', 'name' => 'Armenia', 'slug' => 'armenia'],
            ['id' => '66001', 'department_id' => '66', 'name' => 'Pereira', 'slug' => 'pereira'],
            ['id' => '68001', 'department_id' => '68', 'name' => 'Bucaramanga', 'slug' => 'bucaramanga'],
            ['id' => '70001', 'department_id' => '70', 'name' => 'Sincelejo', 'slug' => 'sincelejo'],
            ['id' => '73001', 'department_id' => '73', 'name' => 'Ibagué', 'slug' => 'ibague'],
            ['id' => '76001', 'department_id' => '76', 'name' => 'Cali', 'slug' => 'cali'],
            ['id' => '76520', 'department_id' => '76', 'name' => 'Palmira', 'slug' => 'palmira'],
            ['id' => '88001', 'department_id' => '88', 'name' => 'San Andrés', 'slug' => 'san-andres']
        ];

        foreach ($cities as $cityData) {
            City::create($cityData);
        }
    }

    //cuando tenga completo el archivo database/data/colombia.json
    //se comenta el codigo de arriba y se pone a correr este
    /*
    public function run()
    {
        // Leer el archivo JSON
        $json = File::get(database_path('data/colombia.json'));
        $data = json_decode($json, true);

        // 1. Insertar Departamentos primero (evita errores de llave foránea)
        $departments = collect($data)->unique('department_id')->map(function ($item) {
            return [
                'id' => $item['department_id'],
                'name' => $item['department_name'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        Department::insert($departments);

        // 2. Insertar Municipios en bloques (Chunks) para no saturar la memoria
        $cities = collect($data)->map(function ($item) {
            return [
                'id' => $item['city_id'],
                'department_id' => $item['department_id'],
                'name' => $item['city_name'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        foreach ($cities->chunk(200) as $chunk) {
            City::insert($chunk->toArray());
        }
    }
    */
}
