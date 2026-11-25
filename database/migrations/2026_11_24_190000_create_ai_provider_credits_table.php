<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_provider_credits')) {
            Schema::create('ai_provider_credits', function (Blueprint $t) {
                $t->id();
                $t->string('provider', 20)->unique();          // anthropic | openai | deepseek
                $t->decimal('baseline_usd', 12, 4)->nullable(); // saldo leído en la consola del proveedor
                $t->timestamp('baseline_at')->nullable();       // cuándo se registró ese saldo
                $t->decimal('alert_amount', 10, 2)->default(10); // umbral de alerta (misma moneda del saldo)
                $t->decimal('last_balance', 12, 4)->nullable();
                $t->char('last_currency', 3)->nullable();
                $t->boolean('last_available')->nullable();
                $t->string('last_source', 12)->nullable();      // api | estimate | manual
                $t->decimal('spent_since', 12, 4)->nullable();  // gasto desde baseline_at (solo con llave admin)
                $t->timestamp('last_checked_at')->nullable();
                $t->string('last_error', 255)->nullable();
                $t->timestamp('billing_error_at')->nullable();  // último error de "sin crédito" visto en producción
                $t->timestamps();
            });
        }

        $now = now();
        foreach (['anthropic', 'openai', 'deepseek'] as $p) {
            DB::table('ai_provider_credits')->insertOrIgnore(['provider' => $p, 'alert_amount' => 10, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_provider_credits');
    }
};
