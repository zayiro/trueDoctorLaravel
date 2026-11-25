<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perfil fiscal de cada usuario (doctor, clínica o paciente): una sola tabla para los tres roles.
 * document_number y address_line se guardan CIFRADOS (cast "encrypted" en el modelo), por eso son text.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('billing_profiles')) {
            return;
        }

        Schema::create('billing_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('document_type', 10);
            $table->text('document_number');              // cifrado
            $table->string('check_digit', 1)->nullable(); // DV, solo para NIT
            $table->string('legal_name');
            $table->string('billing_email');
            $table->string('phone', 30)->nullable();
            $table->char('country_code', 2)->default('CO');
            $table->string('department_id', 5)->nullable();
            $table->string('city_id', 10)->nullable();
            $table->text('address_line')->nullable();     // cifrado
            $table->string('tax_responsibility', 30)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['department_id', 'city_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_profiles');
    }
};
