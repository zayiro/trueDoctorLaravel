<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prepara `appointments` y `doctor_payouts` para la capa de pagos.
 *
 *  - appointments.status  ENUM → string(30): admite `expired` y `no_show`.
 *  - appointments.channel ENUM → string(20): admite `doctor` (cita creada por el médico).
 *  - appointments.payment_expires_at: hasta cuándo el turno queda retenido esperando el pago.
 *  - appointments.payment_token: enlace de pago sin sesión (citas creadas por el doctor).
 *  - appointments.created_by_user_id: quién creó la cita (null = el propio paciente).
 *  - doctor_payouts.payment_id: pago que originó el desembolso.
 *
 * Los valores existentes se conservan tal cual (no se reescribe ningún dato).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE appointments MODIFY status VARCHAR(30) NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE appointments MODIFY channel VARCHAR(20) NOT NULL DEFAULT 'web'");

        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('payment_expires_at')->nullable()->after('paid_at');
            $table->string('payment_token', 64)->nullable()->unique()->after('payment_expires_at');
            $table->unsignedBigInteger('created_by_user_id')->nullable()->after('payment_token');

            // Para el barrido que expira las citas sin pagar.
            $table->index(['status', 'payment_status', 'payment_expires_at'], 'appointments_unpaid_sweep_idx');
        });

        Schema::table('doctor_payouts', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_id')->nullable()->after('appointment_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('doctor_payouts', function (Blueprint $table) {
            $table->dropColumn('payment_id');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_unpaid_sweep_idx');
            $table->dropUnique(['payment_token']);
            $table->dropColumn(['payment_expires_at', 'payment_token', 'created_by_user_id']);
        });

        // Los estados nuevos no existen en el ENUM original: se reasignan para no perder filas.
        DB::statement("UPDATE appointments SET status = 'cancelled' WHERE status = 'expired'");
        DB::statement("UPDATE appointments SET status = 'completed' WHERE status = 'no_show'");
        DB::statement("UPDATE appointments SET channel = 'web' WHERE channel NOT IN ('app','web','whatsapp')");

        DB::statement("ALTER TABLE appointments MODIFY status ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE appointments MODIFY channel ENUM('app','web','whatsapp') NOT NULL DEFAULT 'web'");
    }
};
