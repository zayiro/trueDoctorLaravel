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
        Schema::create('service_specialty', function (Blueprint $table) {
            $table->id();
            
            // Llaves foráneas del catálogo
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->foreignId('specialty_id')->constrained()->onDelete('cascade');
            
            // 🔒 COLUMNA DE AISLAMIENTO MULTI-TENANT: Rastrea al dueño de esta vinculación
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->timestamps();

            // Índice compuesto único modificado para que el mismo usuario no repita el enlace, 
            // pero permitiendo que otros usuarios vinculen el mismo servicio a sus propias especialidades.
            $table->unique(['service_id', 'specialty_id', 'user_id'], 'service_specialty_tenant_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_specialty');
    }
};
