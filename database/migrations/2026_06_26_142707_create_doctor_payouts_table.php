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
        Schema::create('doctor_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments');

            // Polimorfismo: apunta a Doctor (User) o Clinic
            $table->morphs('payable'); // payable_id + payable_type

            $table->decimal('total_charged', 10, 2);   // lo que pagó el paciente
            $table->decimal('wompi_fee', 10, 2);        // comisión Wompi (~2.9%)
            $table->decimal('platform_commission', 10, 2); // comisión OpenDoctor
            $table->decimal('amount_to_pay', 10, 2);    // lo que le debes al médico/clínica

            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->string('transfer_reference')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_payouts');
    }
};
