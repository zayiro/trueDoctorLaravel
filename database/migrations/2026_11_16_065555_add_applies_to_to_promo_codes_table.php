<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            // Lista de módulos donde sirve el código: appointment | exam | skin_analysis
            $table->json('applies_to')->nullable()->after('code');
        });

        // Por defecto, los códigos existentes valen solo para citas
        DB::table('promo_codes')->update(['applies_to' => json_encode(['appointment'])]);

        DB::table('promo_codes')->where('code', 'CODEEXAM2026')
            ->update(['applies_to' => json_encode(['exam'])]);

        DB::table('promo_codes')->where('code', 'CODESKIN2026')
            ->update(['applies_to' => json_encode(['skin_analysis'])]);
    }

    public function down(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->dropColumn('applies_to');
        });
    }
};