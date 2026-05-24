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
        Schema::create('clinic_doctor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending')->comment('pending, approved, rejected, inactive');
            $table->timestamps();

            // Blindaje: El mismo doctor no puede ser contratado dos veces por la misma clínica
            $table->unique(['clinic_id', 'doctor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_doctor');
    }
};
