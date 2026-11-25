<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Catálogo de planes de OpenDoctor. Refleja lo que está en producción (precio por 12 meses en los planes de pago).
 * Es idempotente: correrlo no cambia nada si los valores ya son estos. Para cambiar un precio o un límite,
 * edítalo AQUÍ y en la base; así un `db:seed` nunca lo revierte.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Plan Esencial',
                'slug' => 'esencial',
                'plan' => 'free',
                'applicable_role' => 'doctor',
                'max_addresses' => 0,
                'max_services' => 15,
                'max_doctors' => 1,
                'max_appointments_per_year' => 50,
                'can_search_patients' => false,
                'can_see_whatsapp_contact_button' => false,
                'max_patients_list' => 20,
                'can_export_history' => false,
                'has_telemedicine' => true,
                'ai_scribe_enabled' => true,
                'price' => 0,
                'duration_days' => 30, // gratis: no vence
                'active' => true,
            ],
            [
                'name' => 'Plan Gold',
                'slug' => 'gold',
                'plan' => 'gold',
                'applicable_role' => 'doctor',
                'max_addresses' => 4,
                'max_services' => 80,
                'max_doctors' => 1,
                'max_appointments_per_year' => 9999,
                'can_search_patients' => true,
                'can_see_whatsapp_contact_button' => true,
                'max_patients_list' => 10000,
                'can_export_history' => true,
                'has_telemedicine' => true,
                'ai_scribe_enabled' => true,
                'price' => 950000,
                'duration_days' => 365, // 12 meses
                'active' => true,
            ],
            [
                'name' => 'Plan Clínica Esencial',
                'slug' => 'clinic_free',
                'plan' => 'clinic_free',
                'applicable_role' => 'clinic',
                'max_addresses' => 0, // sin sedes físicas (son de los planes de pago); la sede virtual no cuenta
                'max_services' => 10,
                'max_doctors' => 3,
                'max_appointments_per_year' => 200,
                'can_search_patients' => false,
                'can_see_whatsapp_contact_button' => false,
                'max_patients_list' => 100,
                'can_export_history' => false,
                'has_telemedicine' => true,
                'ai_scribe_enabled' => true,
                'price' => 0,
                'duration_days' => 30, // gratis: no vence
                'active' => true,
            ],
            [
                'name' => 'Plan Clínica Gold',
                'slug' => 'clinic_gold',
                'plan' => 'clinic_gold',
                'applicable_role' => 'clinic',
                'max_addresses' => 15,
                'max_services' => 100,
                'max_doctors' => 25,
                'max_appointments_per_year' => 99999,
                'can_search_patients' => true,
                'can_see_whatsapp_contact_button' => true,
                'max_patients_list' => 50000,
                'can_export_history' => true,
                'has_telemedicine' => true,
                'ai_scribe_enabled' => true,
                'price' => 1500000,
                'duration_days' => 365, // 12 meses
                'active' => true,
            ],
        ];

        foreach ($plans as $data) {
            Plan::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
