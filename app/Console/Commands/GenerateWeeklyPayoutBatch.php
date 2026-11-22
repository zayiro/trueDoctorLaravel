<?php

namespace App\Console\Commands;

use App\Services\Payments\PayoutBatchService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateWeeklyPayoutBatch extends Command
{
    protected $signature = 'payouts:generate-batch {--cutoff= : Fecha de corte Y-m-d (por defecto, el último jueves)} {--dry-run : Solo cuenta, no crea nada}';

    protected $description = 'Prepara el borrador del lote semanal de liquidaciones (citas realizadas hasta el corte).';

    public function handle(): int
    {
        $cutoff = $this->option('cutoff')
            ? Carbon::createFromFormat('Y-m-d', (string) $this->option('cutoff'), PayoutBatchService::TZ)->endOfDay()
            : PayoutBatchService::lastCutoff();

        $q = PayoutBatchService::eligible($cutoff);
        $n = (clone $q)->count();
        $sum = (float) (clone $q)->sum('amount_to_pay');
        $this->info("Corte {$cutoff->format('Y-m-d H:i')}: $n liquidación(es) elegibles por $" . number_format($sum, 0, ',', '.'));

        if ($this->option('dry-run') || $n === 0) {
            return self::SUCCESS;
        }

        $batch = PayoutBatchService::createDraft($cutoff, null);
        $this->info($batch ? "Borrador creado: {$batch->number}" : 'No se creó el lote (ya no había elegibles).');

        if ($batch) {
            try {
                $mail = new \App\Mail\PayoutBatchDraftMail($batch->number, (int) $batch->payouts_count, (int) $batch->beneficiaries_count, '$' . number_format((float) $batch->total_amount, 0, ',', '.') . ' COP', $batch->id);
                foreach (\App\Models\User::whereIn('role', ['administrator', 'admin'])->pluck('email')->filter()->unique() as $to) {
                    \Illuminate\Support\Facades\Mail::to($to)->send($mail);
                }
            } catch (\Throwable $e) {
                report($e);
                $this->warn('El lote se creó, pero no pude avisar por correo: ' . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
