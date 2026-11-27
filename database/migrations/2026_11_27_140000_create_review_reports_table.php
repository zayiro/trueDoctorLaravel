<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('review_reports')) {
            return;
        }

        Schema::create('review_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            // Quién reporta (si la cuenta se borra, el reporte se conserva sin nombre)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 30);
            $table->text('details')->nullable();
            // pending | dismissed (se mantiene la reseña) | upheld (se ocultó) | reversed (se ocultó y luego se restauró)
            $table->string('status', 20)->default('pending');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            // Una persona reporta una reseña una sola vez
            $table->unique(['review_id', 'user_id']);
            $table->index(['status', 'review_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_reports');
    }
};
