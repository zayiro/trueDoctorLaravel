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
            $table->boolean('accepts_online_payments')->default(false);// despues de confirmar el horario lo envia a pagos online
            $table->string('currency')->default('COP');
            
            // Configuración de Citas
            $table->integer('min_notice_hours')->default(24); // Tiempo mínimo para reservar, para bloquear horas muy cercanas a la hora actual en el calendario.
            $table->integer('max_advance_days')->default(30); // Cuánto tiempo a futuro puede ver el paciente
            $table->boolean('requires_approval')->default(false); // Si el doctor debe confirmar manualmente la reservacion
            
            // Notificaciones
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
