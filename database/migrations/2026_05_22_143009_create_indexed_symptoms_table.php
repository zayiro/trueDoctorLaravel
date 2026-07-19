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

            // ===================================
            // CONTENIDO CLÍNICO (Resolver Soft 404)
            // ===================================
            
            // Descripción clínica detallada (800-1500 palabras)
            // Se usa si no existe, se llena con placeholder desde el controller
            $table->longText('clinical_description')
                ->nullable()
                ->comment('Descripción médica detallada del síntoma (800-1500 palabras)');
            
            // Causas comunes (HTML con divs/listas)
            $table->longText('common_causes')
                ->nullable()
                ->comment('HTML con causas frecuentes del síntoma');
            
            // Signos de alarma que requieren urgencia (HTML)
            $table->longText('alarm_signs')
                ->nullable()
                ->comment('HTML con signos que requieren atención urgente/emergencia');
            
            // Factores de riesgo (HTML con lista)
            $table->longText('risk_factors')
                ->nullable()
                ->comment('HTML con factores que aumentan el riesgo');
            
            // Recomendaciones de autocuidado (HTML)
            $table->longText('self_care_advice')
                ->nullable()
                ->comment('HTML con recomendaciones de autocuidado en casa');
            
            // Imagen destacada para og:image
            $table->string('image_url')
                ->nullable()
                ->comment('URL de imagen destacada para redes sociales (og:image)');
            
            // ===================================
            // ÍNDICES PARA OPTIMIZACIÓN
            // ===================================
            
            // Full text search para búsquedas rápidas de síntomas
            $table->fullText(['clinical_description', 'common_causes'])
                ->comment('Índice full-text para búsquedas de contenido clínico');
            
            // Índice para queries frecuentes
            $table->index('specialty_id');
            $table->index('urgency_level');
            $table->index('search_count');
            
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
