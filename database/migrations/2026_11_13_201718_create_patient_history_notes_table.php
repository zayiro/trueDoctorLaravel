<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones para crear la tabla de notas extemporáneas.
     */
    public function up(): void
    {
        Schema::create('patient_history_notes', function (Blueprint $table) {
            $table->id();
            
            // Relación con la historia clínica principal de la cita
            $table->foreignId('patient_history_id')
                  ->constrained('patient_histories')
                  ->cascadeOnDelete();

            // Relación con el médico que redacta la nota extra
            $table->foreignId('doctor_id')
                  ->constrained('doctors');

            // Contenido textual de la nota (se encriptará en el modelo por normativa)
            $table->text('content');

            // Tipo de nota: extemporánea (pasadas las 3 horas) o aclaratoria (sobre historia ya firmada)
            $table->enum('note_type', ['extemporaneous', 'amendment'])
                  ->default('extemporaneous');

            $table->timestamps();
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_history_notes');
    }
};
