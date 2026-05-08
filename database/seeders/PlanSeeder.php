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
            ['name' => 'Plan Free', 'plan' => 'free', 'slug' => 'free', 'max_addresses' => 2, 'max_services' => 3, 'max_appointments_per_year' => 50, 'can_search_patients' => false, 'max_patients_list' => 20, 'can_export_history' => false, 'has_telemedicine' => true, 'price' => 0],
            ['name' => 'Plan Premium', 'plan' => 'premium', 'slug' => 'premium', 'max_addresses' => 10, 'max_services' => 20, 'max_appointments_per_year' => 500, 'can_search_patients' => true, 'max_patients_list' => 200, 'can_export_history' => true, 'has_telemedicine' => true, 'price' => 2400000],
            ['name' => 'Plan Gold', 'plan' => 'gold', 'slug' => 'gold', 'max_addresses' => 20, 'max_services' => 50, 'max_appointments_per_year' => 9999, 'can_search_patients' => true, 'max_patients_list' => 10000, 'can_export_history' => true, 'has_telemedicine' => true, 'price' => 3500000]
        ];

        foreach ($plans as $plan) {
            Plan::create([
                'name'  => $plan['name'],
                'plan' => $plan['plan'],
                'slug' => $plan['slug'],
                'max_addresses' => $plan['max_addresses'],
                'max_services' => $plan['max_services'],
                'price' => $plan['price']
            ]);
        }
    }
}
