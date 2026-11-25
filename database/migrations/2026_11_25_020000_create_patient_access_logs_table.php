<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('patient_access_logs')) {
            return;
        }

        Schema::create('patient_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();          // quién accedió (users.id)
            $table->unsignedBigInteger('doctor_id')->nullable();     // médico, si es un médico
            $table->unsignedBigInteger('clinic_id')->nullable();     // clínica, si es una clínica
            $table->unsignedBigInteger('patient_id');                // a la ficha de quién
            $table->string('action', 80);                            // nombre de la ruta (ver ficha, guardar nota…) o "denegado"
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['patient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_access_logs');
    }
};
