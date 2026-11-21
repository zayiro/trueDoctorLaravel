<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id')->unique();
            $table->unsignedBigInteger('appointment_id')->index();
            $table->unsignedBigInteger('patient_user_id')->nullable();
            $table->unsignedBigInteger('amount_in_cents');
            $table->string('status', 20)->default('pending')->index(); // pending | completed
            $table->string('cancelled_by', 20)->nullable();            // patient | doctor | system
            $table->string('reason', 255)->nullable();
            $table->text('notes')->nullable();                          // lo que dejó el sistema (p. ej. pago al médico retirado)
            $table->string('provider_reference', 255)->nullable();      // referencia del reembolso en Wompi
            $table->string('admin_note', 500)->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_refunds');
    }
};
