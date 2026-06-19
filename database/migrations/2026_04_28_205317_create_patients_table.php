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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('identification')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->enum('blood_type', ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'])->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->decimal('weight', 5, 2)->nullable(); // Hasta 999.99 kg
            $table->decimal('height', 3, 2)->nullable(); // Hasta 9.99 metros (Ej: 1.75)
            //Si quieres generar reportes por aseguradora o tener convenios específicos, crea una tabla maestra insurances.php
            // Tabla 'insurances': id, name, logo, phone
            $table->foreignId('insurance_id')->nullable()->constrained('insurances');
            $table->text('permanent_conditions')->nullable(); // Para enfermedades crónicas
            // Ubicación geográfica
            $table->string('department_id', 5)->nullable(); // Código DIVIPOLA Dep
            $table->string('city_id', 5)->nullable();       // Código DIVIPOLA Mun
            $table->enum('residence_zone', ['Urbana', 'Rural'])->default('Urbana');
            
            // Datos Socioeconómicos
            $table->string('occupation')->nullable();
            $table->enum('civil_status', [
                'Soltero/a', 
                'Casado/a', 
                'Unión Libre', 
                'Divorciado/a', 
                'Viudo/a'
            ])->nullable();
            $table->enum('ethnicity', [
                'Indígena',
                'Rrom (Gitano)',
                'Raizal (San Andrés y Providencia)',
                'Palenquero (San Basilio de Palenque)',
                'Negro, Mulato, Afrocolombiano',
                'Ninguna de las anteriores (Mestizo/Blanco)'
            ])->default('Ninguna de las anteriores (Mestizo/Blanco)');
            // Clasificación Ley 100
            $table->enum('affiliation_type', [
                'Contributivo', 
                'Subsidiado', 
                'Vinculado', 
                'Particular', 
                'Otro'
            ])->nullable();

            $table->enum('regime_type', [
                'General', 
                'Especial', 
                'Excepción'
            ])->default('General');
            // Nivel SISBÉN (A, B, C, D según el nuevo modelo)
            $table->string('sisben_level', 5)->nullable(); 
            // Datos del Responsable / Emergencia (Requisito Res. 1995)
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
