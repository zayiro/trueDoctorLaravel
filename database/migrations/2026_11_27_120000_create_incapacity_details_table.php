<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('incapacity_details')) {
            return;
        }

        Schema::create('incapacity_details', function (Blueprint $table) {
            $table->id();
            // Cada incapacidad ES un certificado (tipo «incapacity»); aquí van sus datos propios.
            $table->unsignedBigInteger('certificate_id')->unique();
            $table->string('origin', 10);                       // common | work
            $table->string('service_group', 40);                // Consulta externa
            $table->string('modality', 15);                     // telemedicine | in_person
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('days');
            $table->boolean('is_extension')->default(false);
            $table->unsignedBigInteger('previous_certificate_id')->nullable();
            $table->boolean('is_retroactive')->default(false);
            $table->text('retroactive_reason')->nullable();     // cifrado desde el modelo
            $table->timestamps();

            $table->foreign('certificate_id')->references('id')->on('certificates')->cascadeOnDelete();
            $table->foreign('previous_certificate_id')->references('id')->on('certificates')->nullOnDelete();
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incapacity_details');
    }
};
