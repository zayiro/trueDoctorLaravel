<?php

namespace App\Services\AiChat;

use App\Mail\AiChatReportReady;
use App\Models\AiChatSession;
use App\Models\Setting;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Informe final de la sesión: genera con la IA, guarda, envía por correo y reintenta con espera creciente.
 * Toda la lógica de estado vive aquí; el job y el comando solo la invocan.
 */
class AiChatReportService
{
    public const MAX_ATTEMPTS = 4;

    /** Minutos de espera tras fallar el intento n (1, 2, 3). */
    public const BACKOFF_MINUTES = [2, 10, 30];

    /** Un informe "generando" más de estos minutos se considera trabado y se reprograma. */
    public const STUCK_MINUTES = 10;

    public function __construct(private readonly AiChatReportClient $client)
    {
    }

    /** Reserva la sesión para un solo proceso a la vez. Devuelve null si no le toca o ya la tomó otro. */
    public function claim(int $id): ?AiChatSession
    {
        $taken = AiChatSession::whereKey($id)
            ->where('status', AiChatSession::STATUS_FINISHED)
            ->where('report_status', AiChatSession::REPORT_PENDING)
            ->where(fn ($q) => $q->whereNull('report_next_attempt_at')->orWhere('report_next_attempt_at', '<=', now()))
            ->update([
                'report_status' => AiChatSession::REPORT_GENERATING,
                'report_attempts' => DB::raw('report_attempts + 1'),
                'updated_at' => now(),
            ]);

        return $taken ? AiChatSession::with('user')->find($id) : null;
    }

    /** @return 'skip'|'ok'|'retry'|'failed' */
    public function process(int $id): string
    {
        $session = $this->claim($id);

        if (! $session) {
            return 'skip';
        }

        try {
            if (! $session->report_content) {
                $this->generate($session);
                $session = $session->fresh(['user']);
            }

            $this->deliver($session);

            return 'ok';
        } catch (Throwable $e) {
            return $this->fail($session->fresh(['user']) ?? $session, $e);
        }
    }

    /** Pide el informe a la IA (modelo del informe y, si falla, el de respaldo) y lo guarda. */
    public function generate(AiChatSession $session): void
    {
        $system = AiChatReportPrompt::system($session);
        $message = AiChatReportPrompt::userMessage($session);

        $models = array_values(array_unique(array_filter([
            $session->model_report ?: AiChatSettings::value('ai_chat_model_report'),
            AiChatSettings::value('ai_chat_model_fallback'),
        ])));

        $result = null;
        $last = null;

        foreach ($models as $model) {
            try {
                $result = $this->client->generate((string) $model, $system, $message);
                break;
            } catch (Throwable $e) {
                $last = $e;
                Log::warning('ai_chat.report_model_failed', ['session_id' => $session->id, 'model' => $model, 'message' => $e->getMessage()]);
            }
        }

        if (! $result) {
            throw new \RuntimeException('Ningún modelo de IA generó el informe: '.($last?->getMessage() ?? 'sin modelos configurados'));
        }

        $data = $result['data'] + ['model' => $result['model'], 'generated_at' => now()->toIso8601String()];

        $session->update([
            'report_content' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'report_generated_at' => now(),
            'report_urgency' => $data['urgency'],
            'report_specialty' => $data['specialty'],
            'tokens_in' => (int) $session->tokens_in + $result['tokens_in'],
            'tokens_out' => (int) $session->tokens_out + $result['tokens_out'],
        ]);
    }

    /** Pone el correo en cola y marca el informe como enviado. Si el correo falla, se reintenta sin regenerar. */
    public function deliver(AiChatSession $session): void
    {
        $email = trim((string) $session->user?->email);

        if ($email === '') {
            $session->update([
                'report_status' => AiChatSession::REPORT_SENT,
                'report_error' => 'La persona no tiene correo registrado; el informe solo está en pantalla.',
            ]);

            return;
        }

        Mail::to($email)->queue(new AiChatReportReady($session));

        $session->update([
            'report_status' => AiChatSession::REPORT_SENT,
            'report_sent_at' => now(),
            'report_error' => null,
        ]);
    }

    /** @return 'retry'|'failed' */
    private function fail(AiChatSession $session, Throwable $e): string
    {
        $attempts = (int) $session->report_attempts;
        $error = mb_substr($e->getMessage(), 0, 500);

        Log::warning('ai_chat.report_failed', ['session_id' => $session->id, 'attempt' => $attempts, 'message' => $error]);

        if ($attempts >= self::MAX_ATTEMPTS) {
            $session->update([
                'report_status' => AiChatSession::REPORT_FAILED,
                'report_next_attempt_at' => null,
                'report_error' => $error,
            ]);
            $this->notifyAdmin($session, $error);

            return 'failed';
        }

        $wait = self::BACKOFF_MINUTES[min($attempts, count(self::BACKOFF_MINUTES)) - 1] ?? 30;

        $session->update([
            'report_status' => AiChatSession::REPORT_PENDING,
            'report_next_attempt_at' => now()->addMinutes($wait),
            'report_error' => $error,
        ]);

        return 'retry';
    }

    /** Los "generando" que llevan demasiado tiempo (proceso caído) vuelven a la cola o pasan a fallido. */
    public function releaseStuck(): int
    {
        $n = 0;

        AiChatSession::where('status', AiChatSession::STATUS_FINISHED)
            ->where('report_status', AiChatSession::REPORT_GENERATING)
            ->where('updated_at', '<=', now()->subMinutes(self::STUCK_MINUTES))
            ->get()
            ->each(function (AiChatSession $s) use (&$n) {
                $this->fail($s, new \RuntimeException('El proceso se interrumpió mientras generaba el informe.'));
                $n++;
            });

        return $n;
    }

    /** Reintento manual: vuelve a poner en cola (conserva el informe si ya estaba generado). */
    public function requeue(int $id): bool
    {
        return (bool) AiChatSession::whereKey($id)
            ->where('status', AiChatSession::STATUS_FINISHED)
            ->whereIn('report_status', [AiChatSession::REPORT_FAILED, AiChatSession::REPORT_SKIPPED, AiChatSession::REPORT_SENT])
            ->update([
                'report_status' => AiChatSession::REPORT_PENDING,
                'report_attempts' => 0,
                'report_next_attempt_at' => now(),
                'report_error' => null,
                'updated_at' => now(),
            ]);
    }

    /** Contenido del informe ya decodificado, o null. */
    public static function content(AiChatSession $session): ?array
    {
        $raw = $session->report_content;
        $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;

        return is_array($data) && isset($data['summary']) ? $data : null;
    }

    /**
     * Misma lógica que los exámenes: la sugerida si tiene médicos disponibles (status = 1);
     * si no, el respaldo (medicina general).
     *
     * @return array{suggested: ?Specialty, target: ?Specialty, available: bool}
     */
    public static function recommendation(?string $slug): array
    {
        $suggested = $slug ? Specialty::where('slug', $slug)->first() : null;

        if ($suggested && (int) $suggested->status === 1) {
            return ['suggested' => $suggested, 'target' => $suggested, 'available' => true];
        }

        $fallback = Specialty::where('slug', config('ai_specialties.fallback', 'medicina-general'))
            ->where('status', 1)
            ->first();

        return ['suggested' => $suggested, 'target' => $fallback, 'available' => false];
    }

    /** Nombre de la especialidad en el idioma de la sesión (con el nombre de la base de datos como respaldo). */
    public static function specialtyName(?Specialty $s, string $locale): string
    {
        if (! $s) {
            return '';
        }

        return AiChatSpecialties::label($s->slug, $locale) ?? (string) $s->name;
    }

    private function notifyAdmin(AiChatSession $session, string $error): void
    {
        try {
            $to = Setting::where('key', 'support_email')->value('value')
                ?: User::where('role', 'admin')->value('email');

            if (! $to) {
                return;
            }

            Mail::raw(
                AiChatReportText::t('admin_failed_body', ['id' => $session->id, 'n' => $session->report_attempts, 'error' => $error], 'es'),
                fn ($m) => $m->to($to)->subject(AiChatReportText::t('admin_failed_subject', [], 'es'))
            );
        } catch (Throwable $e) {
            Log::warning('ai_chat.report_admin_notice_failed', ['message' => $e->getMessage()]);
        }
    }
}
