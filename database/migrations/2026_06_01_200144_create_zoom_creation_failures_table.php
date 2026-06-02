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
        Schema::create('zoom_creation_failures', function (Blueprint $table) {
            $table->id();
            // Relación con tu tabla appointments (asumiendo que el ID es un bigInteger unsigned)
            $table->foreignId('appointment_id')->constrained('appointments')->onDelete('cascade');
            $table->integer('attempts')->default(0);
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zoom_creation_failures');
    }
};
