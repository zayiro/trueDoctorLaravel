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
        Schema::create('medical_analyses', function (Blueprint $table) {
            $table->id();
            $table->string('access_token', 64)
                ->nullable()
                ->unique()
                ->comment('Token público impredecible usado en la URL en vez del ID autoincremental');
            $table->text('file_paths')->nullable();
            $table->longText('ai_response')->nullable();  // Respuesta global de la IA
            $table->enum('ai_provider', ['openai', 'claude', 'gemini'])->nullable();
            $table->string('customer_email');             
            $table->string('reason_type'); // 'routine', 'control', 'symptoms', etc.
            $table->text('reason_custom')->nullable(); // Detalle del motivo escrito por el usuario
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'error'])->default('pending');
            // Datos de pago
            $table->string('payment_id')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'error'])->default('pending');
            $table->decimal('price', 8, 2)->nullable(); // Define el costo del servicio
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_analyses');
    }
};
