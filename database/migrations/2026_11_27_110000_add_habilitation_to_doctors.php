<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            // Habilitación del prestador cuando el doctor atiende de forma independiente (los doctores de una clínica usan la de la clínica).
            if (!Schema::hasColumn('doctors', 'habilitation_nit')) {
                $table->string('habilitation_nit', 20)->nullable();
            }
            if (!Schema::hasColumn('doctors', 'habilitation_reps')) {
                $table->string('habilitation_reps', 12)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            foreach (['habilitation_nit', 'habilitation_reps'] as $c) {
                if (Schema::hasColumn('doctors', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
