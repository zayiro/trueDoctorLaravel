<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validation_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained('clinics')->cascadeOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_email');
            $table->json('items');
            $table->text('note')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['doctor_id', 'sent_at']);
            $table->index(['clinic_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validation_notices');
    }
};
