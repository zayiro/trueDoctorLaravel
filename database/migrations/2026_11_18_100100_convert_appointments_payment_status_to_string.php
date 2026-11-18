<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * appointments.payment_status era ENUM('pending','paid','cancelled','failed'), pero el enum PHP
 * App\Enums\PaymentStatus también tiene refunded, pay_at_clinic y exempt: guardarlos fallaba.
 *
 * Solo cambia el tipo de la columna; los datos existentes no se tocan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('payment_status', 30)->default('pending')->change();
        });
    }

    public function down(): void
    {
        // El ENUM viejo no admite estos valores: se llevan al más cercano antes de volver atrás.
        DB::table('appointments')->where('payment_status', 'refunded')->update(['payment_status' => 'cancelled']);
        DB::table('appointments')->where('payment_status', 'pay_at_clinic')->update(['payment_status' => 'pending']);
        DB::table('appointments')->where('payment_status', 'exempt')->update(['payment_status' => 'paid']);

        Schema::table('appointments', function (Blueprint $table) {
            $table->enum('payment_status', ['pending', 'paid', 'cancelled', 'failed'])->default('pending')->change();
        });
    }
};
