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
        Schema::create('indexed_symptoms', function (Blueprint $table) {
            $table->id();
            $table->string('search_query')->unique(); // Lo que escribió el usuario (ej: "Dolor fuerte de cabeza")
            $table->string('slug')->unique();         // Para la URL limpia (ej: "dolor-fuerte-de-cabeza")
            
            // Relación con la especialidad que determinó la IA
            $table->foreignId('specialty_id')->nullable()->constrained()->onDelete('set null');
            
            // Campos autogenerados para SEO
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            
            // Datos del Triage de OpenAI
            $table->string('urgency_level')->nullable(); // Alta, Media, Baja
            $table->text('ai_advice')->nullable();       // Consejo inicial de la IA
            
            $table->integer('search_count')->default(1); // Contador de popularidad (opcional)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indexed_symptoms');
    }
};
