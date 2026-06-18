<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            // Agregar clinic_id si no existe
            if (!Schema::hasColumn('schedules', 'clinic_id')) {
                $table->foreignId('clinic_id')->nullable()->after('address_id')->constrained()->onDelete('cascade');
            }

            // Agregar is_active si no existe
            if (!Schema::hasColumn('schedules', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('end_time');
            }

            // Agregar índices si no existen
            if (!Schema::hasIndex('schedules', 'schedules_doctor_day_index')) {
                $table->index(['doctor_id', 'day'], 'schedules_doctor_day_index');
            }

            if (!Schema::hasIndex('schedules', 'schedules_clinic_id_index')) {
                $table->index(['clinic_id'], 'schedules_clinic_id_index');
            }

            if (!Schema::hasIndex('schedules', 'schedules_doctor_clinic_day_index')) {
                $table->index(['doctor_id', 'clinic_id', 'day'], 'schedules_doctor_clinic_day_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            // Eliminar índices
            $table->dropIndexIfExists('schedules_doctor_day_index');
            $table->dropIndexIfExists('schedules_clinic_id_index');
            $table->dropIndexIfExists('schedules_doctor_clinic_day_index');

            // Eliminar columnas
            if (Schema::hasColumn('schedules', 'clinic_id')) {
                $table->dropForeignKeyIfExists(['clinic_id']);
                $table->dropColumn('clinic_id');
            }

            if (Schema::hasColumn('schedules', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
