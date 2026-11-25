<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_alerts_sent')) {
            Schema::create('admin_alerts_sent', function (Blueprint $t) {
                $t->id();
                $t->string('alert_key', 120)->unique(); // ej. credit:twilio:low | bill:3:2026-12-01:soon
                $t->string('level', 8);                 // crit | warn
                $t->timestamp('last_sent_at')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_alerts_sent');
    }
};
