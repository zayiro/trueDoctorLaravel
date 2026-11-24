<?php

namespace App\Console\Commands;

use App\Services\AiChat\AiChatWaitlistService;
use Illuminate\Console\Command;

class NotifyAiChatWaitlist extends Command
{
    protected $signature = 'ai-chat:notify-waitlist {--dry-run : Solo cuenta a cuántas personas se avisaría}';

    protected $description = 'Avisa (una sola vez) a quienes pidieron que les avisemos cuando abra la venta del Chat de Salud IA';

    public function handle(AiChatWaitlistService $waitlist): int
    {
        $r = $waitlist->notify((bool) $this->option('dry-run'));

        if ($r['status'] === 'paused') {
            $this->warn('Las ventas siguen pausadas. Actívalas en Admin → Chat de Salud IA y vuelve a correr este comando.');

            return self::FAILURE;
        }

        $this->line(($this->option('dry-run') ? 'Se avisaría a: ' : 'Avisos puestos en cola: ').$r['count']);

        return self::SUCCESS;
    }
}
