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
        Schema::create('patient_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Ejemplo: Metformina, Losartán
            $table->string('dosage')->nullable(); // Ejemplo: 500mg, 1 tableta
            $table->string('frequency')->nullable(); // Ejemplo: Cada 12 horas, Diariamente
            $table->text('notes')->nullable(); // Ejemplo: Tomar después del desayuno
            $table->boolean('active')->default(true); // Para saber si lo sigue tomando
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_medications');
    }
};
