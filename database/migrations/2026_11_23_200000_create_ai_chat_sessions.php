<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('payment_id')->nullable()->index();

            $t->string('token_access', 64)->unique();
            $t->unsignedTinyInteger('duration_minutes');
            $t->unsignedInteger('price_cop');              // precio base congelado al iniciar el pago
            $t->unsignedInteger('total_cop')->nullable();  // total cobrado (con fee de pasarela)

            // pago_pendiente | pendiente | activa | terminada | vencida
            $t->string('status', 20)->default('pago_pendiente');
            $t->string('locale', 5)->default('es');

            $t->timestamp('paid_at')->nullable();
            $t->timestamp('expires_at')->nullable();   // pago + días de vigencia
            $t->timestamp('start_time')->nullable();   // se escribe una sola vez, al pulsar Empezar
            $t->timestamp('end_time')->nullable();     // start_time + duración
            $t->timestamp('ended_at')->nullable();     // cierre real

            // Términos y consentimientos
            $t->timestamp('terms_accepted_at')->nullable();
            $t->string('terms_version', 20)->nullable();
            $t->string('terms_ip', 45)->nullable();
            $t->boolean('consent_profile')->default(false);
            $t->boolean('email_full_copy')->default(false);

            // Validación de identificación (sin guardar la identificación aquí)
            $t->unsignedTinyInteger('id_attempts')->default(0);
            $t->timestamp('id_locked_until')->nullable();

            // Una sola conexión activa por sesión
            $t->string('active_instance', 64)->nullable();
            $t->timestamp('active_instance_seen_at')->nullable();
            $t->unsignedTinyInteger('connection_changes')->default(0);

            // Recordatorio del día 5
            $t->timestamp('reminder_sent_at')->nullable();

            // Conversación
            $t->unsignedSmallInteger('user_message_count')->default(0);
            $t->unsignedTinyInteger('offtopic_count')->default(0);
            $t->string('focus', 40)->nullable();

            // IA
            $t->string('model_chat', 60)->nullable();
            $t->string('model_report', 60)->nullable();
            $t->unsignedInteger('tokens_in')->default(0);
            $t->unsignedInteger('tokens_out')->default(0);
            $t->unsignedInteger('tokens_cache_read')->default(0);

            // Reporte final: pendiente | generando | enviado | fallido | omitido
            $t->string('report_status', 15)->default('pendiente');
            $t->unsignedTinyInteger('report_attempts')->default(0);
            $t->timestamp('report_next_attempt_at')->nullable();
            $t->timestamp('report_generated_at')->nullable();
            $t->timestamp('report_sent_at')->nullable();
            $t->text('report_error')->nullable();
            $t->string('report_urgency', 10)->nullable();
            $t->string('report_specialty', 80)->nullable();
            $t->longText('report_content')->nullable();

            // Retención de la conversación
            $t->timestamp('purge_at')->nullable();
            $t->timestamp('purged_at')->nullable();

            $t->timestamps();

            $t->index(['status', 'expires_at']);
            $t->index(['status', 'end_time']);
            $t->index(['report_status', 'report_next_attempt_at']);
            $t->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_sessions');
    }
};
