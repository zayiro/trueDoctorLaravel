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
        Schema::create('patient_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained(); // Quién lo emitió
            $table->string('type'); // Ejemplo: Quirúrgico, Odontológico, Telemedicina
            $table->dateTime('signed_at'); // Fecha y hora exacta de la firma
            $table->string('file_path'); // Ruta del archivo PDF/Imagen
            $table->string('ip_address')->nullable(); // Para validez digital
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_consents');
    }
};
