<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones.
     */
    public function up(): void
    {
        Schema::create('affected_appointments', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('unavailability_id')
                ->constrained('unavailabilities')
                ->onDelete('cascade');
            
            $table->foreignId('appointment_id')
                ->constrained('appointments')
                ->onDelete('cascade');
            
            $table->foreignId('patient_id')
                ->constrained('patients')
                ->onDelete('cascade');
            
            $table->foreignId('doctor_id')
                ->constrained('doctors')
                ->onDelete('cascade');
            
            $table->foreignId('rescheduled_to_appointment_id')
                ->nullable()
                ->constrained('appointments')
                ->onDelete('set null');
            
            // Datos de auditoría
            $table->date('original_date');
            $table->time('original_start_time');
            $table->time('original_end_time');
            
            // Estado del reagendamiento
            $table->enum('status', ['pending_reschedule', 'rescheduled', 'cancelled', 'confirmed'])
                ->default('pending_reschedule');
            
            // Timestamps
            $table->timestamp('notification_sent_at')->nullable();
            $table->timestamp('rescheduled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Índices para queries frecuentes
            $table->index('unavailability_id');
            $table->index('appointment_id');
            $table->index('patient_id');
            $table->index('doctor_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('affected_appointments');
    }
};
