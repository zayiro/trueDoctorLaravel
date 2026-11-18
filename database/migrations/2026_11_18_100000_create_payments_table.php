<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Un intento de pago = una referencia única.
            $table->string('reference', 64)->unique();
            $table->string('provider', 20)->default('wompi');
            $table->string('provider_transaction_id', 100)->nullable()->index();

            // Qué se cobra y cómo. Strings (no ENUM de MariaDB) para poder agregar valores sin migraciones pesadas.
            $table->string('kind', 30)->index();
            $table->string('method', 20)->default('online');
            $table->string('status', 20)->default('pending');
            $table->string('review_reason', 50)->nullable();

            // Lo que se paga (cita, suscripción, examen, skin analysis) y quién paga.
            $table->morphs('payable');
            $table->foreignId('payer_id')->nullable()->constrained('users')->nullOnDelete();

            // Reservado para el reparto al médico (quien recibirá el dinero).
            $table->nullableMorphs('payee');

            // Dinero en centavos. total = base - descuento + comisión.
            $table->unsignedBigInteger('base_amount_in_cents');
            $table->unsignedBigInteger('commission_in_cents')->default(0);
            $table->unsignedBigInteger('discount_in_cents')->default(0);
            $table->unsignedBigInteger('provider_fee_in_cents')->default(0);
            $table->unsignedBigInteger('total_in_cents');
            $table->char('currency', 3)->default('COP');

            $table->unsignedBigInteger('promo_code_id')->nullable()->index();

            $table->json('payload')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
