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
        Schema::create('appointment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->onDelete('cascade');
            $table->string('event_type'); // 'created', 'rescheduled', 'cancelled', 'confirmed', 'executed', 'completed', etc.
            $table->json('payload'); // Datos originales/cambios en formato JSON
            $table->json('metadata')->nullable(); // Información adicional (IP, user agent, etc.)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('user_type')->nullable(); // 'doctor', 'clinic', 'patient', 'admin', 'system'
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('description')->nullable(); // Descripción legible del evento
            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index('appointment_id');
            $table->index('event_type');
            $table->index('user_id');
            $table->index('created_at');
            $table->index(['appointment_id', 'event_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_events');
    }
};
