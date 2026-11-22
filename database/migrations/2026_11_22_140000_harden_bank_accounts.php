<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->text('account_number')->change();                 // ahora guarda el valor cifrado
            $table->string('last4', 4)->nullable()->after('account_number');
            $table->timestamp('changed_at')->nullable()->after('is_primary'); // última vez que cambió algo sensible
            $table->timestamp('verified_at')->nullable()->after('changed_at');
            $table->unsignedBigInteger('verified_by')->nullable()->after('verified_at');
            $table->string('verification_method', 24)->nullable()->after('verified_by'); // nombre_banco | certificacion | microdeposito
        });

        Schema::create('bank_account_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_account_id')->nullable()->index();
            $table->string('accountable_type');
            $table->unsignedBigInteger('accountable_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event', 24);            // created | changed | viewed
            $table->string('ip', 45)->nullable();
            $table->json('meta')->nullable();       // nunca lleva el número completo
            $table->timestamp('created_at')->useCurrent();

            $table->index(['accountable_type', 'accountable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_account_events');
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn(['last4', 'changed_at', 'verified_at', 'verified_by', 'verification_method']);
        });
    }
};
