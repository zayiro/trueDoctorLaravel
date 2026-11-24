<?php

namespace App\Console\Commands;

use App\Services\AiChat\AiChatRetentionService;
use Illuminate\Console\Command;

class PurgeAiChatSessions extends Command
{
    protected $signature = 'ai-chat:purge {--dry-run : Solo cuenta lo que eliminaría}';

    protected $description = 'Elimina la conversación y el informe de las sesiones del Chat de Salud IA que cumplieron su plazo de retención';

    public function handle(AiChatRetentionService $retention): int
    {
        $n = $retention->purge((bool) $this->option('dry-run'));

        if ($n > 0 || $this->option('dry-run')) {
            $this->line(($this->option('dry-run') ? 'Se depurarían: ' : 'Sesiones depuradas: ').$n);
        }

        return self::SUCCESS;
    }
}
