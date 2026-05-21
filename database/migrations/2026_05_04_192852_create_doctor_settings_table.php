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
        Schema::create('doctor_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            
            // Configuración de Plan y Pagos
            $table->foreignId('plan_id')->nullable()->constrained('plans')->onDelete('set null');
            $table->boolean('accepts_online_payments')->default(false);
            $table->string('currency')->default('COP');
            
            // Configuración de Citas (Tus campos base)
            $table->integer('min_notice_hours')->default(2); 
            $table->integer('max_advance_days')->default(30); 
            $table->boolean('requires_approval')->default(false); 

            // 👇 SUGERENCIA 1: Control de Citas Avanzado
            $table->unsignedInteger('buffer_time_minutes')->default(0); // Tiempo libre entre citas
            $table->unsignedInteger('max_appointments_per_day')->nullable(); // null = sin límite diario

            // 👇 SUGERENCIA 2: Políticas de Cancelación y Reprogramación
            $table->boolean('allow_patient_cancellation')->default(true);
            $table->unsignedInteger('cancellation_notice_hours')->default(2); // Mínimo de tiempo para cancelar
            $table->boolean('allow_patient_rescheduling')->default(true);

            // 👇 SUGERENCIA 3: Integraciones Virtuales
            $table->string('virtual_meeting_platform')->default('internal'); // zoom, meet, teams
            $table->boolean('google_calendar_sync')->default(false);
            
            // Notificaciones (Tus campos base)
            $table->boolean('email_notifications')->default(true);
            $table->boolean('whatsapp_notifications')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_settings');
    }
};
