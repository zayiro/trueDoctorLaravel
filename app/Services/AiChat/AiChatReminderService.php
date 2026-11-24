<?php

namespace App\Services\AiChat;

use App\Mail\AiChatReminder;
use App\Models\AiChatSession;
use App\Models\Patient;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Recordatorio de una sesión pagada que aún no se ha empezado: 48 horas antes de que venza
 * (con 7 días de vigencia, es el día 5), por correo y SMS, una sola vez.
 * El SMS no lleva datos médicos ni el enlace personal.
 */
class AiChatReminderService
{
    /** Horas antes del vencimiento en que se avisa (máximo; con vigencias cortas se reduce). */
    public const LEAD_HOURS = 48;

    /** Si falta menos que esto para vencer, ya no se avisa (no daría tiempo de nada). */
    public const MIN_LEFT_MINUTES = 60;

    /** @return \Illuminate\Database\Eloquent\Builder<AiChatSession> */
    public function due()
    {
        $lead = min(self::LEAD_HOURS, max(1, AiChatSettings::int('ai_chat_expiry_days') * 12));

        return AiChatSession::where('status', AiChatSession::STATUS_PENDING)
            ->whereNull('reminder_sent_at')
            ->whereNotNull('paid_at')
            ->where('expires_at', '>', now()->addMinutes(self::MIN_LEFT_MINUTES))
            ->where('expires_at', '<=', now()->addHours($lead));
    }

    /** @return array{email: int, sms: int, sessions: int} */
    public function run(int $limit = 100): array
    {
        $out = ['email' => 0, 'sms' => 0, 'sessions' => 0];

        foreach ($this->due()->with('user')->orderBy('expires_at')->limit($limit)->get() as $session) {
            $r = $this->remind($session);

            if ($r === null) {
                continue;
            }

            $out['sessions']++;
            $out['email'] += $r['email'] ? 1 : 0;
            $out['sms'] += $r['sms'] ? 1 : 0;
        }

        return $out;
    }

    /**
     * Reserva el aviso (una sola vez, aunque corran dos procesos) y lo envía.
     *
     * @return array{email: bool, sms: bool}|null null si otro proceso ya lo tomó o ya no aplica
     */
    public function remind(AiChatSession $session): ?array
    {
        $claimed = AiChatSession::whereKey($session->id)
            ->where('status', AiChatSession::STATUS_PENDING)
            ->whereNull('reminder_sent_at')
            ->update(['reminder_sent_at' => now()]);

        if (! $claimed) {
            return null;
        }

        $session = $session->fresh(['user']);
        $result = ['email' => false, 'sms' => false];

        if ($session->user?->email) {
            try {
                Mail::to($session->user->email)->queue(new AiChatReminder($session));
                $result['email'] = true;
            } catch (Throwable $e) {
                Log::warning('ai_chat.reminder_mail_failed', ['session_id' => $session->id, 'message' => $e->getMessage()]);
            }
        }

        $result['sms'] = $this->sms($session);

        if (! $result['email'] && ! $result['sms']) {
            Log::warning('ai_chat.reminder_no_channel', ['session_id' => $session->id]);
        }

        return $result;
    }

    /** Texto del SMS: sin tildes (cabe en un solo mensaje) y sin datos médicos. */
    public function smsText(AiChatSession $session): string
    {
        return Str::ascii(AiChatReminderText::t('sms', [
            'date' => AiChatReminderText::date($session->expires_at, $session->locale),
            'url' => route('patient.ai-chats.index'),
        ], $session->locale));
    }

    private function sms(AiChatSession $session): bool
    {
        try {
            $phone = Patient::where('user_id', $session->user_id)->value('phone');

            if (! $phone) {
                return false;
            }

            $sms = app(SmsService::class);
            $to = $sms->normalizePhoneNumber((string) $phone);

            if (! $to) {
                return false;
            }

            return (bool) $sms->send($to, $this->smsText($session));
        } catch (Throwable $e) {
            Log::warning('ai_chat.reminder_sms_failed', ['session_id' => $session->id, 'message' => $e->getMessage()]);

            return false;
        }
    }
}
