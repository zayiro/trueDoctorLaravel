<?php

namespace App\Console\Commands;

use App\Services\AiChat\AiChatCleanupService;
use Illuminate\Console\Command;

class CleanupAiChatSessions extends Command
{
    protected $signature = 'ai-chat:cleanup {--dry-run : Solo cuenta lo que borraría}';

    protected $description = 'Borra las compras abandonadas del Chat de Salud IA (sin pagar tras 48 horas y sin ningún pago en curso)';

    public function handle(AiChatCleanupService $cleanup): int
    {
        $n = $cleanup->abandoned((bool) $this->option('dry-run'));

        if ($n > 0 || $this->option('dry-run')) {
            $this->line(($this->option('dry-run') ? 'Se borrarían: ' : 'Compras abandonadas borradas: ').$n);
        }

        return self::SUCCESS;
    }
}
