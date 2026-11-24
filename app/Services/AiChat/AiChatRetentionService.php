<?php

namespace App\Services\AiChat;

use App\Models\AiChatSession;
use Illuminate\Support\Facades\DB;

/**
 * Retención: pasados los días configurados desde que terminó la sesión (purge_at), se eliminan de forma
 * definitiva la conversación y el informe. Queda solo el registro de la sesión sin contenido de salud
 * (fechas, duración, pago, consumo). No toca sesiones cuyo informe aún se está generando.
 */
class AiChatRetentionService
{
    /** @return int sesiones depuradas (o que se depurarían con $dryRun) */
    public function purge(bool $dryRun = false): int
    {
        $total = 0;

        AiChatSession::where('status', AiChatSession::STATUS_FINISHED)
            ->whereNull('purged_at')
            ->whereNotNull('purge_at')
            ->where('purge_at', '<=', now())
            ->whereNotIn('report_status', [AiChatSession::REPORT_PENDING, AiChatSession::REPORT_GENERATING])
            ->orderBy('id')
            ->chunkById(100, function ($rows) use (&$total, $dryRun) {
                foreach ($rows as $session) {
                    if (! $dryRun) {
                        DB::transaction(function () use ($session) {
                            $session->messages()->delete();

                            AiChatSession::whereKey($session->id)->whereNull('purged_at')->update([
                                'report_content' => null,
                                'report_urgency' => null,
                                'report_specialty' => null,
                                'report_error' => null,
                                'focus' => null,
                                'purged_at' => now(),
                                'updated_at' => now(),
                            ]);
                        });
                    }

                    $total++;
                }
            });

        return $total;
    }
}
