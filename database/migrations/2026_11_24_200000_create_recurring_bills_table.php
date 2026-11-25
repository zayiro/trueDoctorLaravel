<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('recurring_bills')) {
            Schema::create('recurring_bills', function (Blueprint $t) {
                $t->id();
                $t->string('name', 80);
                $t->decimal('amount', 12, 2)->nullable();      // null = monto por definir
                $t->char('currency', 3)->default('USD');
                $t->string('cadence', 12)->default('monthly');  // monthly | quarterly | yearly | once
                $t->date('due_at');                             // próxima fecha de cobro
                $t->unsignedSmallInteger('remind_days')->default(5);
                $t->boolean('auto_renew')->default(false);      // se cobra solo (tarjeta): la fecha avanza sola
                $t->boolean('active')->default(true);
                $t->string('note', 255)->nullable();
                $t->date('last_paid_at')->nullable();
                $t->timestamps();
            });
        }

        $now = now();
        DB::table('ai_provider_credits')->insertOrIgnore([
            ['provider' => 'twilio', 'alert_amount' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['provider' => 'deepgram', 'alert_amount' => 5, 'created_at' => $now, 'updated_at' => $now],
        ]);

        if (!DB::table('recurring_bills')->where('name', 'Zoom Developer Pack')->exists()) {
            DB::table('recurring_bills')->insert([
                'name' => 'Zoom Developer Pack', 'amount' => 0, 'currency' => 'USD', 'cadence' => 'monthly',
                'due_at' => now()->startOfMonth()->addMonth()->toDateString(), 'remind_days' => 1, 'auto_renew' => 1,
                'note' => 'Developer Pack Credits (prepago mensual, uso mensual). Factura el día 1 por el periodo 1-30; la de noviembre salió en cantidad 0 y $0,00. Revisar si aparece un monto.', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_bills');
        DB::table('ai_provider_credits')->whereIn('provider', ['twilio', 'deepgram'])->delete();
    }
};
