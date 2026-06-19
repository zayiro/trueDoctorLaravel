<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Agregar índice único compuesto para prevenir duplicados lógicos
        Schema::table('addresses', function (Blueprint $table) {
            // Índice para validar que al menos uno de doctor_id o clinic_id esté presente
            // y que no haya duplicados del mismo nombre/dirección por tenant
            $table->unique(['doctor_id', 'name'], 'addresses_doctor_name_unique')->nullable();
            $table->unique(['clinic_id', 'name'], 'addresses_clinic_name_unique')->nullable();
            $table->unique(['doctor_id', 'address'], 'addresses_doctor_address_unique')->nullable();
            $table->unique(['clinic_id', 'address'], 'addresses_clinic_address_unique')->nullable();
        });

        // 2. Agregar índice único en address_service para prevenir duplicados
        Schema::table('address_service', function (Blueprint $table) {
            $table->unique(['address_id', 'service_id'], 'address_service_unique');
        });

        // 3. Agregar índices de búsqueda en service_specialty
        Schema::table('service_specialty', function (Blueprint $table) {
            $table->index(['specialty_id', 'user_id'], 'service_specialty_search_idx');
            $table->index(['user_id', 'service_id'], 'service_specialty_owner_idx');
        });

        // 4. Validar integridad: Eliminar sedes huérfanas (sin doctor_id ni clinic_id)
        DB::statement('DELETE FROM addresses WHERE doctor_id IS NULL AND clinic_id IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropUnique('addresses_doctor_name_unique');
            $table->dropUnique('addresses_clinic_name_unique');
            $table->dropUnique('addresses_doctor_address_unique');
            $table->dropUnique('addresses_clinic_address_unique');
        });

        Schema::table('address_service', function (Blueprint $table) {
            $table->dropUnique('address_service_unique');
        });

        Schema::table('service_specialty', function (Blueprint $table) {
            $table->dropIndex('service_specialty_search_idx');
            $table->dropIndex('service_specialty_owner_idx');
        });
    }
};
