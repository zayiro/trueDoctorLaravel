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
        Schema::create('patient_surgeries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Ejemplo: Apendicectomía, Cesárea
            $table->year('year')->nullable(); // Año aproximado de la cirugía
            $table->text('observations')->nullable(); // Ejemplo: Sin complicaciones, Cicatrización 
            //saber si un paciente tuvo una reacción adversa a la anestesia
            $table->boolean('anesthesia_complications')->default(false);
            $table->text('anesthesia_details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_surgeries');
    }
};
