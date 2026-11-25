<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('contact_messages')) {
            return;
        }
        Schema::table('contact_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('contact_messages', 'replied_at')) {
                $table->timestamp('replied_at')->nullable()->after('is_read');
            }
            if (!Schema::hasColumn('contact_messages', 'replied_by')) {
                $table->unsignedBigInteger('replied_by')->nullable()->after('replied_at');
            }
            if (!Schema::hasColumn('contact_messages', 'reply_body')) {
                $table->text('reply_body')->nullable()->after('replied_by');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('contact_messages')) {
            return;
        }
        Schema::table('contact_messages', function (Blueprint $table) {
            foreach (['reply_body', 'replied_by', 'replied_at'] as $col) {
                if (Schema::hasColumn('contact_messages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
