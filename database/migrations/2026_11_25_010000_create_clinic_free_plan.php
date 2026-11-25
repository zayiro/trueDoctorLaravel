<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Plan gratuito de clínica: solo sede virtual (las sedes físicas son de los planes de pago), 10 servicios y 3 especialistas.
        DB::table('plans')->updateOrInsert(
            ['slug' => 'clinic_free'],
            [
                'name' => 'Plan Clínica Esencial',
                'plan' => 'clinic_free',
                'applicable_role' => 'clinic',
                'max_addresses' => 0,
                'max_services' => 10,
                'max_doctors' => 3,
                'max_appointments_per_year' => 200,
                'can_search_patients' => 0,
                'can_see_whatsapp_contact_button' => 0,
                'max_patients_list' => 100,
                'can_export_history' => 0,
                'has_telemedicine' => 1,
                'ai_scribe_enabled' => 1,
                'price' => 0,
                'duration_days' => 30,
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        $id = DB::table('plans')->where('slug', 'clinic_free')->value('id');
        if ($id
            && !DB::table('clinic_settings')->where('plan_id', $id)->exists()
            && !DB::table('subscriptions')->where('plan_id', $id)->exists()) {
            DB::table('plans')->where('id', $id)->delete();
        }
    }
};
