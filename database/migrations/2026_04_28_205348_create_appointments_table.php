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
            $table->string('reference', 20)->unique();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('doctor_id')->constrained();
            $table->foreignId('clinic_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('service_id')->constrained();
            $table->foreignId('address_id')->nullable()->constrained(); 
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration'); 
            $table->decimal('price', 10, 2);
            
            // Links de Video-conferencia
            $table->text('meeting_link')->nullable()->comment('Enlace genérico o exclusivo para el Paciente');            
            $table->string('zoom_meeting_id')->nullable()->comment('ID numérico de la reunión en Zoom');
            $table->text('meeting_link_password')->nullable()->comment('Contraseña cifrada de la sala de Zoom para el Meeting SDK');
            $table->text('zoom_start_url')->nullable()->comment('Enlace exclusivo para que el Doctor inicie como Anfitrión');
             
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->enum('channel', ['app', 'web', 'whatsapp'])->default('web');
            $table->text('notes')->nullable();
            $table->boolean('email_sent')->default(false);
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
