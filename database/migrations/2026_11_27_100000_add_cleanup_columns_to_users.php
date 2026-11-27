<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Última vez que la persona «volvió de verdad» (ingreso posterior a crear la cuenta, contraseña creada o «Conservar mi cuenta»).
            if (!Schema::hasColumn('users', 'last_engaged_at')) {
                $table->timestamp('last_engaged_at')->nullable();
            }
            // Cuándo se le avisó que su cuenta sin uso se eliminaría.
            if (!Schema::hasColumn('users', 'cleanup_notified_at')) {
                $table->timestamp('cleanup_notified_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['last_engaged_at', 'cleanup_notified_at'] as $c) {
                if (Schema::hasColumn('users', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
