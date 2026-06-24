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

            // ── Relaciones ───────────────────────────────────────────────
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('set null');

            // ── Metadatos (no encriptados — necesarios para filtros) ─────
            $table->enum('entry_type', ['consultation', 'follow_up', 'emergency', 'note']);
            $table->enum('status', ['draft', 'signed', 'amended'])->default('draft');
            $table->string('cie10_code')->nullable();
            $table->boolean('ai_assisted')->default(false);

            // ── SOAP (se encriptan en el modelo con AES-256) ─────────────
            $table->text('soap_subjective')->nullable();  // S — síntomas del paciente
            $table->text('soap_objective')->nullable();   // O — examen físico y signos vitales
            $table->text('soap_assessment')->nullable();  // A — diagnóstico
            $table->text('soap_plan')->nullable();        // P — tratamiento y seguimiento

            $table->timestamps();
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
