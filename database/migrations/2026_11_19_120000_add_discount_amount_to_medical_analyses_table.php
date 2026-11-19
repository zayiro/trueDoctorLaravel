<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_analyses', function (Blueprint $table) {
            // Descuento (en pesos) que se aplicó con un código. price ya es lo que se cobra con el descuento.
            $table->unsignedInteger('discount_amount')->default(0)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('medical_analyses', function (Blueprint $table) {
            $table->dropColumn('discount_amount');
        });
    }
};
