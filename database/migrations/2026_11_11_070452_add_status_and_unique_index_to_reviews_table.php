<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Estado de moderación (published / hidden)
        if (! Schema::hasColumn('reviews', 'status')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->string('status', 20)->default('published')->after('comment');
            });
        }

        // Una reseña por usuario y entidad. reviewable_type se indexa con prefijo de 191
        // caracteres para evitar "Specified key was too long" en utf8mb4.
        DB::statement(
            'ALTER TABLE reviews
             ADD UNIQUE INDEX reviews_user_reviewable_unique (user_id, reviewable_type(191), reviewable_id)'
        );
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_user_reviewable_unique');
            $table->dropColumn('status');
        });
    }
};