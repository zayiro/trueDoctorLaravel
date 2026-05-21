<?php

namespace Database\Seeders;

use App\Models\Insurance;
use Illuminate\Database\Seeder;

class InsuranceSeeder extends Seeder
{
    public function run(): void
    {
        $eps = [
            ['name' => 'Particular / Prepagada', 'code' => 'PART01'],
            ['name' => 'EPS SURA', 'code' => 'EPS010'],
            ['name' => 'EPS Sanitas', 'code' => 'EPS005'],
            ['name' => 'Salud Total', 'code' => 'EPS002'],
            ['name' => 'Nueva EPS', 'code' => 'EPS037'],
            ['name' => 'Compensar EPS', 'code' => 'EPS008'],
            ['name' => 'Coosalud', 'code' => 'ESS024'],            
            ['name' => 'Aliansalud', 'code' => 'EPS001'],
            ['name' => 'Famisanar', 'code' => 'EPS017'],
            ['name' => 'S.O.S', 'code' => 'EPS018'],
            ['name' => 'Salud Mía', 'code' => 'EPS046'],
            ['name' => 'Mutual Ser', 'code' => 'EPS048'],
            ['name' => 'Asmet Salud', 'code' => 'ESS062'],
            ['name' => 'Capital Salud', 'code' => 'EPSS37'],
        ];

        foreach ($eps as $item) {
            Insurance::create($item);
        }
    }
}

