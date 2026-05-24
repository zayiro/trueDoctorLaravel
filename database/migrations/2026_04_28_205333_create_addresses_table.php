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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();

            // 1. Hacemos que doctor_id sea nullable (ya que la clínica puede ser dueña de la sede)
            $table->foreignId('doctor_id')->nullable()->constrained()->onDelete('cascade');
            
            // 2. Agregamos el campo para vincular la sede a una clínica (anulable)
            $table->foreignId('clinic_id')->nullable()->constrained()->onDelete('cascade');
            
            $table->string('name');
            $table->string('address');
            $table->string('type')->default('physical');
            $table->string('phone')->nullable();
            $table->string('city_id', 5);
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
