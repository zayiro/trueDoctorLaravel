<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pago en línea de citas presenciales:
 *  - presential_payment_mode: onsite | choice | online (por defecto onsite).
 *  - special_*_commission: porcentaje especial por médico/clínica (null = se usa el general del admin).
 *  - commission_acceptances: constancia de que aceptaron la comisión de servicio.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['doctor_settings', 'clinic_settings'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (!Schema::hasColumn($table, 'presential_payment_mode')) {
                    $t->string('presential_payment_mode', 12)->default('onsite')->after('accepts_online_payments');
                }
                if (!Schema::hasColumn($table, 'special_virtual_commission')) {
                    $t->decimal('special_virtual_commission', 5, 2)->nullable();
                }
                if (!Schema::hasColumn($table, 'special_presential_commission')) {
                    $t->decimal('special_presential_commission', 5, 2)->nullable();
                }
            });

            // Quien ya tenía el pago en línea obligatorio conserva ese comportamiento.
            DB::table($table)->where('accepts_online_payments', 1)->update(['presential_payment_mode' => 'online']);
        }

        if (!Schema::hasTable('commission_acceptances')) {
            Schema::create('commission_acceptances', function (Blueprint $t) {
                $t->id();
                $t->morphs('payable');
                $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $t->string('mode', 12);
                $t->decimal('rate_percent', 5, 2);
                $t->string('terms_version', 20);
                $t->string('ip', 45)->nullable();
                $t->string('user_agent', 255)->nullable();
                $t->timestamp('accepted_at');
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_acceptances');

        foreach (['doctor_settings', 'clinic_settings'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                foreach (['presential_payment_mode', 'special_virtual_commission', 'special_presential_commission'] as $col) {
                    if (Schema::hasColumn($table, $col)) {
                        $t->dropColumn($col);
                    }
                }
            });
        }
    }
};
