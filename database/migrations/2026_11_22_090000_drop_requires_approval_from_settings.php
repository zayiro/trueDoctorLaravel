<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La "aprobación manual de citas" nunca estuvo activa (siempre en 0) y ya no forma parte del producto:
 * una cita queda confirmada, o retenida mientras se paga.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['doctor_settings', 'clinic_settings'] as $table) {
            if (Schema::hasColumn($table, 'requires_approval')) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn('requires_approval'));
            }
        }
    }

    public function down(): void
    {
        foreach (['doctor_settings', 'clinic_settings'] as $table) {
            if (!Schema::hasColumn($table, 'requires_approval')) {
                Schema::table($table, fn (Blueprint $t) => $t->boolean('requires_approval')->default(false));
            }
        }
    }
};
