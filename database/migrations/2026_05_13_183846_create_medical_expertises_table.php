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
        Schema::create('medical_expertises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->string('disease_name'); // Ej: Hipertensión, Arritmia
            $table->text('symptoms_keywords'); // Ej: "dolor de pecho, palpitaciones, falta de aire, presion alta"
            $table->timestamps();
            
            // Indexamos para búsquedas rápidas
            $table->fullText(['disease_name', 'symptoms_keywords']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_expertises');
    }
};
