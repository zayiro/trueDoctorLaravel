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
            $table->string('applicable_role', ['doctor', 'clinic', 'patient']);
            $table->integer('max_addresses')->default(2);
            $table->integer('max_services')->default(3);
            $table->integer('max_doctors')->default(1);
            $table->integer('max_appointments_per_year')->default(50);
            // Booleano para permitir o no el buscador
            $table->boolean('can_search_patients')->default(false);
            //ver el boton de contactar por whatsapp
            $table->boolean('can_see_whatsapp_contact_button')->default(false);
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
