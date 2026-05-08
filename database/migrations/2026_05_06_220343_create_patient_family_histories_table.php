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
        Schema::create('patient_family_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->string('condition'); // Ejemplo: Diabetes, Hipertensión, Cáncer de colon
            $table->string('relationship'); // Ejemplo: Padre, Madre, Abuelo materno
            $table->text('notes')->nullable(); // Ejemplo: Diagnosticado a los 40 años
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_family_histories');
    }
};
