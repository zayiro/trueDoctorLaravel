<?php

namespace Database\Seeders;

use App\Models\Insurance;
use Illuminate\Database\Seeder;

class InsuranceSeeder extends Seeder
{
    public function run(): void
    {
        $eps = [
            ['name' => 'SURA', 'code' => 'EPS010'],
            ['name' => 'Sanitas', 'code' => 'EPS005'],
            ['name' => 'Salud Total', 'code' => 'EPS002'],
            ['name' => 'Nueva EPS', 'code' => 'EPS037'],
            ['name' => 'Compensar', 'code' => 'EPS008'],
            ['name' => 'Coosalud', 'code' => 'ESS024'],
            ['name' => 'Particular / Prepagada', 'code' => 'PART01'],
        ];

        foreach ($eps as $item) {
            Insurance::create($item);
        }
    }
}

