<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            // null = código de OpenDoctor (lo asume la plataforma). 'doctor' | 'clinic' = lo asume quien lo creó.
            $table->string('owner_type', 20)->nullable()->after('user_id');
            $table->unsignedBigInteger('owner_id')->nullable()->after('owner_type');
            $table->index(['owner_type', 'owner_id'], 'promo_codes_owner_idx');
        });
    }

    public function down(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->dropIndex('promo_codes_owner_idx');
            $table->dropColumn(['owner_type', 'owner_id']);
        });
    }
};
