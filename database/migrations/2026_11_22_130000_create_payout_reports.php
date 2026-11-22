<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('payout_batches')->cascadeOnDelete();
            $table->string('payable_type');
            $table->unsignedBigInteger('payable_id');
            $table->string('number', 40)->unique();          // SL-20261127-01-03
            $table->string('transfer_reference', 255)->nullable();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->unsignedInteger('lines_count')->default(0);
            $table->dateTime('paid_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('pdf_hash', 64)->nullable();      // sha256 del archivo enviado
            $table->dateTime('generated_at')->nullable();
            $table->dateTime('emailed_at')->nullable();
            $table->string('email_to')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'payable_type', 'payable_id']);
            $table->index(['payable_type', 'payable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_reports');
    }
};
