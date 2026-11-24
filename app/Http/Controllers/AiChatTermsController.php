<?php

namespace App\Http\Controllers;

use App\Models\Setting;

/** Términos del servicio del Chat de Salud con IA (página pública). */
class AiChatTermsController extends Controller
{
    public function show()
    {
        $int = fn (string $key, int $default): int => max(1, (int) (Setting::get($key, $default) ?: $default));

        $email = trim((string) Setting::get('support_email', ''));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = '';
        }

        return view('ai-chat.terms', [
            'expiryDays'    => $int('ai_chat_expiry_days', 7),
            'retentionDays' => $int('ai_chat_retention_days', 30),
            'supportEmail'  => $email,
        ]);
    }
}
