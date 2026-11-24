<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('skin_analyses', 'image_quality_note')) {
            Schema::table('skin_analyses', function (Blueprint $table) {
                // Motivo por el que el control de calidad objetó la foto y la persona decidió continuar.
                $table->string('image_quality_note', 500)->nullable()->after('failure_reason');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('skin_analyses', 'image_quality_note')) {
            Schema::table('skin_analyses', function (Blueprint $table) {
                $table->dropColumn('image_quality_note');
            });
        }
    }
};
