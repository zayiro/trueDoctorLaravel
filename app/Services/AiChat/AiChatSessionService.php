<?php

namespace App\Services\AiChat;

use App\Models\AiChatSession;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Reglas de vida de una sesión del Chat de Salud con IA: validar identificación, empezar el reloj,
 * una sola conexión activa y cierre por tiempo. El servidor es la fuente de verdad del tiempo.
 */
class AiChatSessionService
{
    /** Una conexión se considera viva si dio señales hace menos de estos segundos. */
    public const INSTANCE_ALIVE_SECONDS = 180;

    /** Máximo de cambios de dispositivo/navegador por sesión mientras el anterior sigue vivo. */
    public const MAX_CONNECTION_CHANGES = 3;

    /** Minutos de bloqueo tras agotar los intentos de identificación. */
    public const LOCK_MINUTES = 30;

    /** Documento sin espacios, puntos ni guiones, en mayúsculas (igual que en la compra). */
    public static function normalizeId(string $value): string
    {
        return strtoupper(preg_replace('/[\s.\-]/', '', trim($value)));
    }

    // ------------------------------------------------------------------
    // Identificación
    // ------------------------------------------------------------------

    /**
     * Compara el documento escrito con el del paciente dueño de la sesión (por índice ciego, sin desencriptar).
     *
     * @return array{0: 'ok'|'bad'|'locked'|'noid', 1: int} [resultado, intentos que quedan]
     */
    public function checkIdentification(AiChatSession $session, string $input): array
    {
        $session = $session->fresh();

        if ($session->id_locked_until && $session->id_locked_until->isFuture()) {
            return ['locked', 0];
        }

        $patient = Patient::where('user_id', $session->user_id)->first();

        if (! $patient || ! $patient->identification_blind_index) {
            return ['noid', 0];
        }

        $given = Patient::generateBlindIndex(self::normalizeId($input));

        if (hash_equals((string) $patient->identification_blind_index, $given)) {
            $session->update(['id_attempts' => 0, 'id_locked_until' => null]);

            return ['ok', 0];
        }

        $max = max(3, AiChatSettings::int('ai_chat_id_max_attempts'));
        $attempts = (int) $session->id_attempts + 1;

        if ($attempts >= $max) {
            $session->update(['id_attempts' => 0, 'id_locked_until' => now()->addMinutes(self::LOCK_MINUTES)]);

            return ['locked', 0];
        }

        $session->update(['id_attempts' => $attempts]);

        return ['bad', $max - $attempts];
    }

    // ------------------------------------------------------------------
    // Empezar y cerrar
    // ------------------------------------------------------------------

    /** Empieza el reloj una sola vez (atómico). Devuelve false si ya empezó o ya no se puede empezar. */
    public function begin(AiChatSession $session): bool
    {
        return DB::transaction(function () use ($session) {
            $now = now();
            $end = $now->copy()->addMinutes((int) $session->duration_minutes);
            $retention = max(1, AiChatSettings::int('ai_chat_retention_days'));

            $updated = AiChatSession::whereKey($session->id)
                ->where('status', AiChatSession::STATUS_PENDING)
                ->where('expires_at', '>', $now)
                ->update([
                    'status' => AiChatSession::STATUS_ACTIVE,
                    'start_time' => $now,
                    'end_time' => $end,
                    'model_chat' => AiChatSettings::value('ai_chat_model_chat'),
                    'model_report' => AiChatSettings::value('ai_chat_model_report'),
                    'purge_at' => $end->copy()->addDays($retention),
                    'updated_at' => $now,
                ]);

            if (! $updated) {
                return false;
            }

            $fresh = $session->fresh();
            $fresh->messages()->create([
                'role' => 'assistant',
                'kind' => 'greeting',
                'content' => $this->greeting($fresh),
                'counted' => false,
                'locale' => $fresh->locale,
            ]);

            return true;
        });
    }

    /** Saludo fijo (sin llamar a la IA), en el idioma de la sesión y con el nombre si se conoce. */
    public function greeting(AiChatSession $session): string
    {
        $name = trim((string) explode(' ', trim((string) $session->user?->name))[0]);

        return AiChatText::t(
            $name !== '' ? 'greeting_named' : 'greeting_anon',
            ['name' => ucfirst($name), 'min' => $session->duration_minutes],
            $session->locale
        );
    }

    /** Cierra o vence la sesión si ya le tocó, sin esperar al proceso programado. */
    public function refresh(AiChatSession $session): AiChatSession
    {
        if ($session->status === AiChatSession::STATUS_ACTIVE && $session->end_time && $session->end_time->lte(now())) {
            $this->close($session);

            return $session->fresh();
        }

        if ($session->status === AiChatSession::STATUS_PENDING && $session->expires_at && $session->expires_at->lte(now())) {
            AiChatSession::whereKey($session->id)
                ->where('status', AiChatSession::STATUS_PENDING)
                ->update(['status' => AiChatSession::STATUS_EXPIRED, 'updated_at' => now()]);

            return $session->fresh();
        }

        return $session;
    }

    /** Pasa a "terminada" una sesión activa cuyo tiempo se acabó. Deja lista la señal para el reporte. */
    public function close(AiChatSession $session): bool
    {
        $wrote = $session->messages()->where('role', 'user')->exists();

        return (bool) AiChatSession::whereKey($session->id)
            ->where('status', AiChatSession::STATUS_ACTIVE)
            ->update([
                'status' => AiChatSession::STATUS_FINISHED,
                'ended_at' => now(),
                'active_instance' => null,
                'report_status' => $wrote ? AiChatSession::REPORT_PENDING : AiChatSession::REPORT_SKIPPED,
                'report_next_attempt_at' => $wrote ? now() : null,
                'updated_at' => now(),
            ]);
    }

    /**
     * Proceso programado: cierra las sesiones activas vencidas y vence las no estrenadas.
     *
     * @return array{0: int, 1: int} [cerradas, vencidas]
     */
    public function closeOverdue(): array
    {
        $closed = 0;

        AiChatSession::where('status', AiChatSession::STATUS_ACTIVE)
            ->where('end_time', '<=', now())
            ->chunkById(100, function ($rows) use (&$closed) {
                foreach ($rows as $row) {
                    if ($this->close($row)) {
                        $closed++;
                    }
                }
            });

        $expired = AiChatSession::where('status', AiChatSession::STATUS_PENDING)
            ->where('expires_at', '<=', now())
            ->update(['status' => AiChatSession::STATUS_EXPIRED, 'updated_at' => now()]);

        return [$closed, $expired];
    }

    // ------------------------------------------------------------------
    // Una sola conexión activa
    // ------------------------------------------------------------------

    /**
     * El último que abre gana. Devuelve [id de la conexión, 'ok'] o [null, 'inactive'|'limit'].
     * Si la conexión anterior sigue viva se cuenta como un cambio; al llegar al tope no se deja abrir otra.
     *
     * @return array{0: ?string, 1: 'ok'|'inactive'|'limit'}
     */
    public function claimInstance(AiChatSession $session): array
    {
        $instance = Str::random(32);

        return DB::transaction(function () use ($session, $instance) {
            $row = AiChatSession::whereKey($session->id)->lockForUpdate()->first();

            if (! $row || $row->status !== AiChatSession::STATUS_ACTIVE) {
                return [null, 'inactive'];
            }

            $alive = $row->active_instance
                && $row->active_instance_seen_at
                && $row->active_instance_seen_at->gt(now()->subSeconds(self::INSTANCE_ALIVE_SECONDS));

            if ($alive && (int) $row->connection_changes >= self::MAX_CONNECTION_CHANGES) {
                return [null, 'limit'];
            }

            $row->update([
                'active_instance' => $instance,
                'active_instance_seen_at' => now(),
                'connection_changes' => (int) $row->connection_changes + ($alive ? 1 : 0),
            ]);

            return [$instance, 'ok'];
        });
    }

    /** Señal de vida de la conexión. False si ya no es la activa. */
    public function touch(AiChatSession $session, string $instance): bool
    {
        if ($instance === '') {
            return false;
        }

        return (bool) AiChatSession::whereKey($session->id)
            ->where('active_instance', $instance)
            ->update(['active_instance_seen_at' => now()]);
    }

    /** Libera la conexión (al cerrar o recargar la pestaña) para que no cuente como un cambio. */
    public function release(AiChatSession $session, string $instance): void
    {
        if ($instance === '') {
            return;
        }

        AiChatSession::whereKey($session->id)
            ->where('active_instance', $instance)
            ->update(['active_instance' => null]);
    }

    public function isActiveInstance(AiChatSession $session, string $instance): bool
    {
        return $instance !== '' && $session->active_instance !== null && hash_equals((string) $session->active_instance, $instance);
    }

    // ------------------------------------------------------------------
    // Mensajes
    // ------------------------------------------------------------------

    public function remainingMessages(AiChatSession $session): int
    {
        return max(0, AiChatSettings::maxMessages((int) $session->duration_minutes) - (int) $session->user_message_count);
    }
}
