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
        Schema::create('patient_allergies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Ejemplo: Penicilina, Nueces, Polen
            $table->enum('type', ['drug', 'food', 'environment', 'other'])->default('other');
            $table->enum('severity', ['mild', 'moderate', 'severe'])->default('mild');
            $table->text('reaction')->nullable(); // Ejemplo: Erupción, Choque anafiláctico
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_allergies');
    }
};
