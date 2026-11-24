<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AiChatSession extends Model
{
    public const STATUS_AWAITING_PAYMENT = 'pago_pendiente';
    public const STATUS_PENDING = 'pendiente';   // pagada, aún sin empezar
    public const STATUS_ACTIVE = 'activa';
    public const STATUS_FINISHED = 'terminada';
    public const STATUS_EXPIRED = 'vencida';

    public const REPORT_PENDING = 'pendiente';
    public const REPORT_GENERATING = 'generando';
    public const REPORT_SENT = 'enviado';
    public const REPORT_FAILED = 'fallido';
    public const REPORT_SKIPPED = 'omitido';

    protected $table = 'ai_chat_sessions';

    protected $guarded = ['id'];

    protected $hidden = ['token_access', 'report_content'];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'ended_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'id_locked_until' => 'datetime',
            'active_instance_seen_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'report_next_attempt_at' => 'datetime',
            'report_generated_at' => 'datetime',
            'report_sent_at' => 'datetime',
            'purge_at' => 'datetime',
            'purged_at' => 'datetime',
            'consent_profile' => 'boolean',
            'email_full_copy' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class, 'ai_chat_session_id');
    }

    /** Token único y difícil de adivinar para el enlace del correo. */
    public static function newToken(): string
    {
        do {
            $token = Str::random(48);
        } while (static::where('token_access', $token)->exists());

        return $token;
    }

    /** Segundos que le quedan al reloj (0 si no ha empezado o ya terminó). */
    public function secondsLeft(): int
    {
        if (! $this->end_time) {
            return 0;
        }

        return max(0, (int) floor(now()->diffInSeconds($this->end_time, false)));
    }
}
