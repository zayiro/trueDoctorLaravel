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
        // Catálogo maestro de planes del SaaS con el nuevo campo max_doctors
        $plans = [
            [
                'name' => 'Plan Free', 
                'plan' => 'free', 
                'applicable_role' => 'doctor',
                'slug' => 'free', 
                'max_addresses' => 2, 
                'max_services' => 3, 
                'max_doctors' => 1, // Individual
                'max_appointments_per_year' => 50, 
                'can_search_patients' => false, 
                'can_see_whatsapp_contact_button' => false, 
                'max_patients_list' => 20, 
                'can_export_history' => false, 
                'has_telemedicine' => true, 
                'price' => 0,
                'active' => true
            ],
            [
                'name' => 'Plan Premium', 
                'plan' => 'premium', 
                'applicable_role' => 'doctor',
                'slug' => 'premium', 
                'max_addresses' => 10, 
                'max_services' => 20, 
                'max_doctors' => 1, // Individual
                'max_appointments_per_year' => 500, 
                'can_search_patients' => true, 
                'can_see_whatsapp_contact_button' => true, 
                'max_patients_list' => 200, 
                'can_export_history' => true, 
                'has_telemedicine' => true, 
                'price' => 2400000,
                'active' => true
            ],
            [
                'name' => 'Plan Gold', 
                'plan' => 'gold', 
                'applicable_role' => 'doctor',
                'slug' => 'gold', 
                'max_addresses' => 20, 
                'max_services' => 50, 
                'max_doctors' => 1, // Individual
                'max_appointments_per_year' => 9999, 
                'can_search_patients' => true, 
                'can_see_whatsapp_contact_button' => true, 
                'max_patients_list' => 10000, 
                'can_export_history' => true, 
                'has_telemedicine' => true, 
                'price' => 3500000,
                'active' => true
            ],
            // 🏢 PLAN FREE EXCLUSIVO AÑADIDO PARA CLÍNICAS / CENTROS MÉDICOS MULTI-DOCTOR
            [
                'name' => 'Plan Clínica Free', 
                'plan' => 'clinic_free', 
                'applicable_role' => 'clinic',
                'slug' => 'clinic_free', 
                'max_addresses' => 2, 
                'max_services' => 10, 
                'max_doctors' => 5, // Capacidad para la nómina de la clínica
                'max_appointments_per_year' => 200, 
                'can_search_patients' => true, 
                'can_see_whatsapp_contact_button' => true, 
                'max_patients_list' => 500, 
                'can_export_history' => true, 
                'has_telemedicine' => true, 
                'price' => 0,
                'active' => true
            ],
            // 🏢 PLAN EXCLUSIVO AÑADIDO PARA CLÍNICAS / CENTROS MÉDICOS MULTI-DOCTOR
            [
                'name' => 'Plan Clínica Gold', 
                'plan' => 'clinic_gold', 
                'applicable_role' => 'clinic',
                'slug' => 'clinic_gold', 
                'max_addresses' => 15, 
                'max_services' => 100, 
                'max_doctors' => 25, // Capacidad para la nómina de la clínica
                'max_appointments_per_year' => 99999, 
                'can_search_patients' => true, 
                'can_see_whatsapp_contact_button' => true, 
                'max_patients_list' => 50000, 
                'can_export_history' => true, 
                'has_telemedicine' => true, 
                'price' => 8500000,
                'active' => true
            ]
        ];

        // Procesamiento seguro contra colisiones de base de datos
        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']], // Atributo único de control
                [
                    'name' => $planData['name'],
                    'plan' => $planData['plan'],
                    'max_addresses' => $planData['max_addresses'],
                    'max_services' => $planData['max_services'],
                    'max_doctors' => $planData['max_doctors'], // Columna de límites integrada
                    'max_appointments_per_year' => $planData['max_appointments_per_year'],
                    'can_search_patients' => $planData['can_search_patients'],
                    'can_see_whatsapp_contact_button' => $planData['can_see_whatsapp_contact_button'],
                    'max_patients_list' => $planData['max_patients_list'],
                    'can_export_history' => $planData['can_export_history'],
                    'has_telemedicine' => $planData['has_telemedicine'],
                    'price' => $planData['price'],
                    'active' => $planData['active']
                ]
            );
        }
    }
}
