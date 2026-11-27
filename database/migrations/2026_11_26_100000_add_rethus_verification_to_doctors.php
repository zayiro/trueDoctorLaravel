<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $cols = [
        'identification_type', 'rethus_status', 'rethus_requested_at', 'rethus_verified_at', 'rethus_expires_at',
        'rethus_verified_by_user_id', 'rethus_confirmed_text', 'rethus_note', 'rethus_mismatch_reason', 'rethus_expiring_notified_at',
    ];

    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            if (!Schema::hasColumn('doctors', 'identification_type')) {
                $table->string('identification_type', 10)->nullable()->after('identification');
            }
            if (!Schema::hasColumn('doctors', 'rethus_status')) {
                $table->string('rethus_status', 20)->default('none')->index();
            }
            if (!Schema::hasColumn('doctors', 'rethus_requested_at')) { $table->timestamp('rethus_requested_at')->nullable(); }
            if (!Schema::hasColumn('doctors', 'rethus_verified_at')) { $table->timestamp('rethus_verified_at')->nullable(); }
            if (!Schema::hasColumn('doctors', 'rethus_expires_at')) { $table->timestamp('rethus_expires_at')->nullable(); }
            if (!Schema::hasColumn('doctors', 'rethus_verified_by_user_id')) { $table->unsignedBigInteger('rethus_verified_by_user_id')->nullable(); }
            if (!Schema::hasColumn('doctors', 'rethus_confirmed_text')) { $table->string('rethus_confirmed_text', 255)->nullable(); }
            if (!Schema::hasColumn('doctors', 'rethus_note')) { $table->text('rethus_note')->nullable(); }
            if (!Schema::hasColumn('doctors', 'rethus_mismatch_reason')) { $table->string('rethus_mismatch_reason', 30)->nullable(); }
            if (!Schema::hasColumn('doctors', 'rethus_expiring_notified_at')) { $table->timestamp('rethus_expiring_notified_at')->nullable(); }
        });

        if (!Schema::hasTable('rethus_verification_logs')) {
            Schema::create('rethus_verification_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
                $table->string('action', 20); // requested, verified, mismatch, expired, reset
                $table->unsignedBigInteger('by_user_id')->nullable();
                $table->string('confirmed_text', 255)->nullable();
                $table->text('note')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['doctor_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rethus_verification_logs');
        Schema::table('doctors', function (Blueprint $table) {
            if (Schema::hasColumn('doctors', 'rethus_status')) { $table->dropIndex(['rethus_status']); }
            foreach ($this->cols as $c) {
                if (Schema::hasColumn('doctors', $c)) { $table->dropColumn($c); }
            }
        });
    }
};
