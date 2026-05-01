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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            // Usuario que deja la reseña (paciente)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Puntuación y comentario
            $table->unsignedTinyInteger('rating'); // 1 a 5
            $table->text('comment')->nullable();

            // Campos polimórficos (reviewable_id y reviewable_type)
            // Esto permite asociar la reseña a un Doctor o a una Clínica
            $table->morphs('reviewable'); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
