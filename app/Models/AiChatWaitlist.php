<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Personas que pidieron que les avisemos cuando abra la venta del Chat de Salud con IA. */
class AiChatWaitlist extends Model
{
    protected $table = 'ai_chat_waitlist';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['notified_at' => 'datetime'];
    }
}
