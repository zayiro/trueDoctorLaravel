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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();

            // Añadimos el doctor_id al horario para saber qué médico atiende en esa sede de la clínica
            $table->foreignId('doctor_id')->nullable()->constrained()->onDelete('cascade');
                        
            $table->foreignId('address_id')->constrained()->onDelete('cascade');
            
            // 🆕 clinic_id: NULL = consultorio particular, NOT NULL = clínica corporativa
            $table->foreignId('clinic_id')->nullable()->constrained()->onDelete('cascade');
            
            $table->unsignedTinyInteger('day'); // 1=Lunes, 7=Domingo
            $table->time('start_time');
            $table->time('end_time');
            
            // 🆕 Permite desactivar bloques sin eliminarlos (soft-delete alternativo)
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();

            // Índice compuesto óptimo para el motor de búsquedas de citas
            $table->index(['address_id', 'doctor_id', 'day'], 'schedules_address_doctor_day_index');
            
            // 🆕 Índice para detectar solapamientos globales del doctor (particular + clínicas)
            $table->index(['doctor_id', 'day'], 'schedules_doctor_day_index');
            
            // 🆕 Índice para búsquedas por clínica
            $table->index(['clinic_id'], 'schedules_clinic_id_index');
            
            // 🆕 Índice combinado para búsquedas doctor + clínica + día
            $table->index(['doctor_id', 'clinic_id', 'day'], 'schedules_doctor_clinic_day_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
