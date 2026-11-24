<?php

namespace App\Console\Commands;

use App\Jobs\GenerateAiChatReport;
use App\Models\AiChatSession;
use App\Services\AiChat\AiChatReportService;
use Illuminate\Console\Command;

class ProcessAiChatReports extends Command
{
    protected $signature = 'ai-chat:reports {--requeue= : ID de una sesión para reintentar su informe} {--limit=20 : Máximo de informes a encolar por pasada}';

    protected $description = 'Encola los informes finales del Chat de Salud IA que ya toca generar o reintentar';

    public function handle(AiChatReportService $reports): int
    {
        if ($id = $this->option('requeue')) {
            $ok = $reports->requeue((int) $id);
            $this->line($ok ? "Sesión #{$id} puesta de nuevo en cola." : "Sesión #{$id}: no se pudo (debe estar terminada y con informe fallido, omitido o enviado).");

            return $ok ? self::SUCCESS : self::FAILURE;
        }

        $stuck = $reports->releaseStuck();

        $ids = AiChatSession::where('status', AiChatSession::STATUS_FINISHED)
            ->where('report_status', AiChatSession::REPORT_PENDING)
            ->where(fn ($q) => $q->whereNull('report_next_attempt_at')->orWhere('report_next_attempt_at', '<=', now()))
            ->orderBy('id')
            ->limit(max(1, (int) $this->option('limit')))
            ->pluck('id');

        foreach ($ids as $id) {
            GenerateAiChatReport::dispatch((int) $id);
        }

        if ($stuck || $ids->isNotEmpty()) {
            $this->line("Informes: {$ids->count()} encolados, {$stuck} trabados reprogramados.");
        }

        return self::SUCCESS;
    }
}
