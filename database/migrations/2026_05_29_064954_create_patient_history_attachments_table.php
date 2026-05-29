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
        Schema::create('patient_history_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade')->index(); // Crea un índice explícito b-tree para búsquedas veloces por paciente
            $table->string('name'); // Nombre visible (ej: "Radiografía de Tórax")
            $table->string('file_path'); // Ruta en el storage
            $table->string('file_type'); // MIME type (application/pdf, image/jpeg)
            $table->bigInteger('file_size'); // Tamaño en bytes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_history_attachments');
    }
};
