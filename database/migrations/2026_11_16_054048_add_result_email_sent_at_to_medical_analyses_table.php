<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('medical_analyses', function (Blueprint $table) {
            $table->timestamp('result_email_sent_at')->nullable()->after('wompi_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_analyses', function (Blueprint $table) {
            $table->dropColumn('result_email_sent_at');
        });
    }
};
