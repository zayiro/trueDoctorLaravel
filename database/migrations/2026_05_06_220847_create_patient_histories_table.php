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
        Schema::create('patient_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade'); // Quién escribe la nota
            $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('set null'); // Opcional: ligar a una cita
            $table->string('reason_for_consultation'); // Motivo de la consulta
            $table->text('symptoms')->nullable();
            $table->text('diagnosis'); // Diagnóstico médico
            $table->text('treatment_plan')->nullable(); // Plan a seguir
            $table->timestamps(); // created_at será la fecha de la nota
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_histories');
    }
};
