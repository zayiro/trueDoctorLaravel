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
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            // Vincula el código a un usuario específico
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('code')->unique(); // El cupón (ej: BIENVENIDA20)
            $table->enum('type', ['fixed', 'percent']); // Tipo de descuento
            $table->decimal('reward', 8, 2); // Valor del descuento (ej: 10.00 o 15.50)
            
            // Límites y uso
            $table->integer('max_uses')->nullable(); // Cantidad máxima de usos globales (null = ilimitado)
            $table->integer('uses')->default(0); // Cuántas veces se ha usado ya
            
            // Fechas de validez
            $table->timestamp('starts_at')->nullable(); // Cuándo empieza a ser válido
            $table->timestamp('expires_at')->nullable(); // Cuándo expira
            
            $table->boolean('is_active')->default(true); // Para desactivarlo manualmente
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
