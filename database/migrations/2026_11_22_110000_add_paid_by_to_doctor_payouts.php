<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_payouts', function (Blueprint $table) {
            // Quién del equipo registró la transferencia (auditoría).
            $table->unsignedBigInteger('paid_by')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_payouts', function (Blueprint $table) {
            $table->dropColumn('paid_by');
        });
    }
};
