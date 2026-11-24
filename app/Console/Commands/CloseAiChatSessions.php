<?php

namespace App\Console\Commands;

use App\Services\AiChat\AiChatSessionService;
use Illuminate\Console\Command;

class CloseAiChatSessions extends Command
{
    protected $signature = 'ai-chat:close-sessions';

    protected $description = 'Cierra las sesiones del Chat de Salud con IA cuyo tiempo se acabó y vence las que no se estrenaron a tiempo';

    public function handle(AiChatSessionService $sessions): int
    {
        [$closed, $expired] = $sessions->closeOverdue();

        if ($closed || $expired) {
            $this->info("Sesiones cerradas: {$closed} · vencidas: {$expired}");
        }

        return self::SUCCESS;
    }
}
