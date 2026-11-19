<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_analyses', function (Blueprint $table) {
            // Comisión de la pasarela (en pesos) que se le traslada al cliente. Total cobrado = price + wompi_fee.
            $table->unsignedInteger('wompi_fee')->default(0)->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('medical_analyses', function (Blueprint $table) {
            $table->dropColumn('wompi_fee');
        });
    }
};
