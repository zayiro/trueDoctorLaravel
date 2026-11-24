<?php

namespace App\Jobs;

use App\Services\AiChat\AiChatReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Genera y envía el informe final de una sesión. Los reintentos los maneja el servicio (espera creciente). */
class GenerateAiChatReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 150;

    public function __construct(public int $sessionId)
    {
    }

    public function handle(AiChatReportService $reports): void
    {
        $reports->process($this->sessionId);
    }
}
