<?php

namespace App\Console\Commands;

use App\Services\Rethus\RethusVerificationService;
use Illuminate\Console\Command;

class RethusCheckExpiry extends Command
{
    protected $signature = 'rethus:check-expiry';

    protected $description = 'Marca como vencidas las verificaciones ReTHUS que ya pasaron su fecha y avisa a quienes vencen pronto';

    public function handle(RethusVerificationService $service): int
    {
        $r = $service->runDailyChecks();
        $this->info("ReTHUS: {$r['expired']} vencidas, {$r['expiring']} avisos de vencimiento próximo.");

        return self::SUCCESS;
    }
}
