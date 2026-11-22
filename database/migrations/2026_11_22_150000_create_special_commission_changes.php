<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Historial de cambios de los porcentajes especiales (quién, cuándo, antes, después y por qué). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('special_commission_changes')) {
            return;
        }

        Schema::create('special_commission_changes', function (Blueprint $t) {
            $t->id();
            $t->string('owner_type', 10);              // doctor | clinic
            $t->unsignedBigInteger('owner_id');
            $t->string('modality', 12);                // virtual | presential
            $t->decimal('old_value', 5, 2)->nullable(); // null = tarifa general
            $t->decimal('new_value', 5, 2)->nullable();
            $t->string('reason', 255);
            $t->unsignedBigInteger('changed_by')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['owner_type', 'owner_id', 'created_at'], 'scc_owner_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_commission_changes');
    }
};
