<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skin_analyses', function (Blueprint $table) {
            $table->id();
            $table->string('access_token', 64)->unique()->comment('Token público para las URLs (en vez del id)');

            // Datos del paciente
            $table->string('customer_email');
            $table->enum('analysis_language', ['es', 'en', 'fr', 'pt', 'de'])->default('es')->comment('Idioma del informe');
            $table->string('body_location')->nullable()->comment('Zona del cuerpo elegida por el paciente');
            $table->text('description')->nullable()->comment('Síntomas descritos por el paciente');

            // Ciclo de vida: pending_payment -> processing -> completed | error (o payment_failed)
            $table->string('status', 30)->default('pending_payment')->index();

            // Dinero en pesos colombianos (COP, sin decimales)
            $table->unsignedInteger('price')->comment('COP: precio de lista del análisis');
            $table->unsignedInteger('discount_amount')->default(0)->comment('COP: descuento por código promocional');
            $table->unsignedInteger('wompi_fee')->default(0)->comment('COP: comisión de la pasarela, calculada sobre price - discount_amount');
            $table->unsignedInteger('total_amount')->comment('COP: lo que paga el paciente = price - discount_amount + wompi_fee');
            $table->string('promo_code', 50)->nullable();

            // Pago
            $table->string('wompi_transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Resultado de la IA
            $table->string('ai_model', 50)->nullable()->comment('Modelo que generó el informe');
            $table->json('report')->nullable()->comment('Informe estructurado devuelto por la IA');
            $table->string('failure_reason')->nullable()->comment('Motivo si status = error');
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skin_analyses');
    }
};