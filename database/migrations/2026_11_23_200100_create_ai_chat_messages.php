<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ai_chat_session_id')->constrained('ai_chat_sessions')->cascadeOnDelete();

            $t->string('role', 12);                    // user | assistant | system
            $t->string('kind', 16)->default('chat');   // chat | greeting | quickstart | notice
            $t->text('content');
            $t->boolean('counted')->default(true);     // cuenta contra el tope de mensajes
            $t->boolean('off_topic')->default(false);
            $t->string('locale', 5)->nullable();

            $t->string('model', 60)->nullable();
            $t->unsignedInteger('tokens_in')->default(0);
            $t->unsignedInteger('tokens_out')->default(0);

            $t->timestamps();

            $t->index(['ai_chat_session_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_messages');
    }
};
