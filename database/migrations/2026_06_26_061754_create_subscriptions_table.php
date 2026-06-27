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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('plans');
            $table->string('wompi_transaction_id')->nullable()->unique();
            $table->string('wompi_reference')->unique(); // tu referencia interna
            $table->enum('status', ['pending', 'approved', 'declined', 'voided', 'error'])->default('pending');
            $table->unsignedBigInteger('amount_in_cents');
            $table->string('currency', 3)->default('COP');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable(); // starts_at + 30 días
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
