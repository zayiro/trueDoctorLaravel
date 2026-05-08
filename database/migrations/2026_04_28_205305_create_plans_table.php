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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ejemplo: "Plan Premium"
            $table->string('slug')->unique(); // free, premium, gold
            $table->string('plan'); // Ejemplo: "premium"
            $table->integer('max_addresses')->default(2);
            $table->integer('max_services')->default(3);
            $table->integer('appointment_limit_per_year')->default(50);
            // Booleano para permitir o no el buscador
            $table->boolean('can_search_patients')->default(false);
            // Límite de pacientes visualizables (ej: 20 para Free, 10000 para Gold)
            $table->integer('max_patients_list')->default(20);
            $table->boolean('can_export_history')->default(false); // Descargar PDFs
            $table->boolean('has_telemedicine')->default(true); // Video-consultas
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
