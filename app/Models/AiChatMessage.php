<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiChatMessage extends Model
{
    protected $table = 'ai_chat_messages';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'counted' => 'boolean',
            'off_topic' => 'boolean',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiChatSession::class, 'ai_chat_session_id');
    }
}
