<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_batches', function (Blueprint $table) {
            $table->id();
            $table->string('number', 32)->unique();                 // LQ-20261127-01
            $table->string('status', 16)->default('draft')->index(); // draft | approved | paid | cancelled
            $table->dateTime('cutoff_at');                           // citas realizadas hasta este momento
            $table->date('pay_date');                                // viernes de pago
            $table->unsignedInteger('payouts_count')->default(0);
            $table->unsignedInteger('beneficiaries_count')->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();   // null = generado automáticamente
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payout_batch_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('payout_batches')->cascadeOnDelete();
            $table->unsignedBigInteger('payout_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event', 32);
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('doctor_payouts', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->after('payment_id')->constrained('payout_batches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('doctor_payouts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_id');
        });
        Schema::dropIfExists('payout_batch_events');
        Schema::dropIfExists('payout_batches');
    }
};
