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
        Schema::create('exam_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Opcional si permites invitados
            $table->string('customer_email');
            $table->string('file_path');
            $table->string('reason_type'); // 'routine', 'control', 'symptoms', etc.
            $table->text('reason_custom')->nullable(); // Detalle del motivo escrito por el usuario
            
            // Datos de pago
            $table->string('payment_id')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->decimal('price', 8, 2)->default(4.99); // Define el costo del servicio

            // Resultado de la IA
            $table->json('ai_result')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_analyses');
    }
};
