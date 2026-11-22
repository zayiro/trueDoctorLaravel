<?php

namespace App\Jobs;

use App\Models\PayoutBatch;
use App\Services\Payments\PayoutReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Genera el soporte de liquidación de un beneficiario y se lo envía por correo. Seguro de reintentar. */
class SendPayoutReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $batchId, public string $type, public int $payableId) {}

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(): void
    {
        $batch = PayoutBatch::find($this->batchId);
        if (!$batch) { return; }

        $report = PayoutReportService::ensure($batch, $this->type, $this->payableId);
        if ($report) {
            PayoutReportService::send($report);
        }
    }
}
