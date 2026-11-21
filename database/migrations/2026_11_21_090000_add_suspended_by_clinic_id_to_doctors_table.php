<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            // Clínica que desactivó a este médico en cascada (null = no fue por cascada).
            $table->unsignedBigInteger('suspended_by_clinic_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropIndex(['suspended_by_clinic_id']);
            $table->dropColumn('suspended_by_clinic_id');
        });
    }
};
