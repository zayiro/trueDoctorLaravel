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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('doctor_id')->constrained();
            $table->foreignId('service_id')->constrained();
            $table->foreignId('address_id')->nullable()->constrained(); 
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration'); 
            $table->decimal('price', 10, 2);
            $table->text('meeting_link')->nullable();            
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Índice compuesto para validación de disponibilidad
            $table->index(['doctor_id', 'date', 'start_time'], 'appointments_doctor_date_time_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
