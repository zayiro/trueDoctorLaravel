<?php

namespace App\Console\Commands;

use App\Services\AiChat\AiChatReminderService;
use Illuminate\Console\Command;

class SendAiChatReminders extends Command
{
    protected $signature = 'ai-chat:reminders {--dry-run : Solo cuenta cuántas sesiones recibirían aviso}';

    protected $description = 'Avisa por correo y SMS a quienes tienen una sesión del Chat de Salud IA sin empezar que está por vencer';

    public function handle(AiChatReminderService $reminders): int
    {
        if ($this->option('dry-run')) {
            $this->line('Recibirían aviso: '.$reminders->due()->count().' sesiones.');

            return self::SUCCESS;
        }

        $r = $reminders->run();

        if ($r['sessions'] > 0) {
            $this->line("Recordatorios: {$r['sessions']} sesiones ({$r['email']} correos, {$r['sms']} SMS).");
        }

        return self::SUCCESS;
    }
}
