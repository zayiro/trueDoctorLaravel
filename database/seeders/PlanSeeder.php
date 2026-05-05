<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            ['name' => 'Plan Free', 'plan' => 'free', 'max_addresses' => 2, 'max_services' => 3, 'price' => 0],
            ['name' => 'Plan Premium', 'plan' => 'premium', 'max_addresses' => 10, 'max_services' => 20, 'price' => 2400000],
            ['name' => 'Plan Gold', 'plan' => 'gold', 'max_addresses' => 20, 'max_services' => 50, 'price' => 3500000]
        ];

        foreach ($plans as $plan) {
            Plan::create([
                'name'  => $plan['name'],
                'plan' => $plan['plan'],
                'max_addresses' => $plan['max_addresses'],
                'max_services' => $plan['max_services'],
                'price' => $plan['price']
            ]);
        }
    }
}
