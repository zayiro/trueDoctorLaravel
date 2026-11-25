<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El dictado con IA queda solo para planes de pago: se apaga en el plan gratuito de médicos (free)
 * y en el de clínicas (clinic_free). No toca ninguna nota ya guardada.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('plans')->whereIn('plan', ['free', 'clinic_free'])->update(['ai_scribe_enabled' => 0]);
    }

    public function down(): void
    {
        DB::table('plans')->whereIn('plan', ['free', 'clinic_free'])->update(['ai_scribe_enabled' => 1]);
    }
};
