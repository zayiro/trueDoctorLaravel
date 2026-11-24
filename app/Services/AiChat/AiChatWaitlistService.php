<?php

namespace App\Services\AiChat;

use App\Mail\AiChatLaunch;
use App\Models\AiChatWaitlist;
use Illuminate\Support\Facades\Mail;

/** Lista de espera de lanzamiento: alta de correos y envío único del aviso cuando abre la venta. */
class AiChatWaitlistService
{
    /** Guarda el correo (si ya estaba, no pasa nada). Devuelve false si el correo no es válido. */
    public function add(string $email, string $source = 'landing'): bool
    {
        $email = mb_strtolower(trim($email));

        if ($email === '' || mb_strlen($email) > 190 || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        AiChatWaitlist::firstOrCreate(['email' => $email], ['locale' => 'es', 'source' => mb_substr($source, 0, 40)]);

        return true;
    }

    /**
     * Avisa una sola vez a quienes aún no han recibido el aviso. No hace nada si la venta sigue pausada.
     *
     * @return array{status: 'paused'|'ok', count: int}
     */
    public function notify(bool $dryRun = false, ?bool $paused = null): array
    {
        if ($paused ?? AiChatSettings::salesPaused()) {
            return ['status' => 'paused', 'count' => 0];
        }

        $count = 0;

        AiChatWaitlist::whereNull('notified_at')->orderBy('id')->chunkById(100, function ($rows) use (&$count, $dryRun) {
            foreach ($rows as $row) {
                if (! $dryRun) {
                    // Se marca antes de poner el correo en cola: aunque algo falle a medias, nunca se envía dos veces.
                    $claimed = AiChatWaitlist::whereKey($row->id)->whereNull('notified_at')->update(['notified_at' => now()]);

                    if (! $claimed) {
                        continue;
                    }

                    Mail::to($row->email)->queue(new AiChatLaunch($row));
                }

                $count++;
            }
        });

        return ['status' => 'ok', 'count' => $count];
    }
}
