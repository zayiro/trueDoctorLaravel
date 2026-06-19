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
        Schema::create('unavailabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
    
            // Opcional: Si la falta es solo en una sede específica
            $table->foreignId('address_id')->nullable()->constrained()->onDelete('cascade');

            // Fechas y horas
            $table->date('start_date'); 
            $table->date('end_date'); // Para rangos de varios días
            
            $table->time('start_time')->nullable(); // Si es nulo, se entiende que es todo el día
            $table->time('end_time')->nullable();
            
            // Razón (Opcional)
            $table->string('reason')->nullable(); // Ej: "Congreso médico", "Personal"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unavailabilities');
    }
};
