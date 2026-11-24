<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_waitlist', function (Blueprint $t) {
            $t->id();
            $t->string('email', 190)->unique();
            $t->string('locale', 5)->default('es');
            $t->string('source', 40)->nullable();
            $t->timestamp('notified_at')->nullable();
            $t->timestamps();

            $t->index('notified_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_waitlist');
    }
};
