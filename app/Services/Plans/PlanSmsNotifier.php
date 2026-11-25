<?php

declare(strict_types=1);

namespace App\Services\Plans;

use App\Models\User;
use App\Services\SmsService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * SMS de aviso de plan (por vencer / en gracia) al teléfono registrado del médico o de la clínica.
 * Nunca rompe el flujo que lo llama: si falta el teléfono, Twilio no está configurado o el envío falla, solo devuelve false.
 * Los textos van sin tildes para que cuenten como un solo SMS.
 */
final class PlanSmsNotifier
{
    public function expiring(User $user, string $planName, CarbonInterface $endsAt, int $daysLeft): bool
    {
        $when = $daysLeft <= 1 ? ' (es manana)' : " (faltan {$daysLeft} dias)";
        $text = 'OpenDoctor: tu ' . $planName . ' vence el ' . $endsAt->copy()->timezone('America/Bogota')->format('d/m/Y') . $when
            . '. Renuevalo aqui: ' . route('plans.index');

        return $this->send($user, $text);
    }

    public function grace(User $user, string $planName, CarbonInterface $graceEnd): bool
    {
        $text = 'OpenDoctor: tu ' . $planName . ' vencio. Renuevalo antes del ' . $graceEnd->copy()->timezone('America/Bogota')->format('d/m/Y')
            . ' para no pasar al plan gratuito: ' . route('plans.index');

        return $this->send($user, $text);
    }

    public function send(User $user, string $text): bool
    {
        if (!config('plans.sms_enabled', true)) {
            return false;
        }
        if (!config('services.twilio.account_sid') || !config('services.twilio.auth_token') || !config('services.twilio.phone_number')) {
            return false;
        }

        $phone = $this->phoneFor($user);
        if ($phone === null) {
            Log::info('plans.sms_skipped_no_phone', ['user_id' => $user->id]);

            return false;
        }

        try {
            $sent = app(SmsService::class)->send($phone, Str::ascii($text));
            Log::info($sent ? 'plans.sms_sent' : 'plans.sms_failed', ['user_id' => $user->id, 'to' => '***' . substr($phone, -4)]);

            return (bool) $sent;
        } catch (Throwable $e) {
            Log::warning('plans.sms_error', ['user_id' => $user->id, 'message' => $e->getMessage()]);

            return false;
        }
    }

    /** Teléfono del médico o de la clínica, en formato internacional; null si no hay o no es válido. */
    public function phoneFor(User $user): ?string
    {
        $row = match ($user->role) {
            'doctor' => DB::table('doctors')->where('user_id', $user->id)->first(['phone']),
            'clinic' => DB::table('clinics')->where('user_id', $user->id)->first(['phone']),
            default  => null,
        };

        return $row ? self::normalize((string) ($row->phone ?? '')) : null;
    }

    public static function normalize(?string $raw): ?string
    {
        $n = preg_replace('/[\s\-().]/', '', (string) $raw);
        if ($n === '' || $n === null) {
            return null;
        }
        if (preg_match('/^\+[1-9]\d{7,14}$/', $n)) {
            return $n;
        }
        if (preg_match('/^(?:57)?(3\d{9})$/', $n, $m)) {
            return '+57' . $m[1];
        }

        return null;
    }
}
