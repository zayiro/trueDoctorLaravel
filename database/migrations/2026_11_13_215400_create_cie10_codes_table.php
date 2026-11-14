<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla catálogo oficial de diagnósticos CIE-10.
     */
    public function up(): void
    {
        Schema::create('cie10_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 15)->unique(); // Ej: J00, I10, E11.9
            $table->string('description');        // Ej: Rinofaringitis aguda [resfriado común]
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Índice para acelerar búsquedas por descripción
            $table->index('description');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('cie10_codes');
    }
};
