<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('plans', 'duration_days')) {
            Schema::table('plans', function (Blueprint $table) {
                // Días de acceso que compra un pago de este plan (365 = anual). El plan gratuito no vence.
                $table->unsignedSmallInteger('duration_days')->default(30)->after('price');
            });
        }

        // Los planes de pago se venden por 12 meses (la web ya los muestra como «/año»).
        DB::table('plans')->where('price', '>', 0)->update(['duration_days' => 365]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('plans', 'duration_days')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->dropColumn('duration_days');
            });
        }
    }
};
