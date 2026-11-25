<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BillList extends Command
{
    protected $signature = 'bills:list';

    protected $description = 'Lista los cobros recurrentes registrados';

    public function handle(): int
    {
        $rows = DB::table('recurring_bills')->orderBy('due_at')->get()->map(fn ($b) => [
            $b->id, $b->name, $b->amount === null ? 'por definir' : number_format((float) $b->amount, 2) . ' ' . $b->currency,
            $b->cadence, $b->due_at, $b->remind_days, $b->auto_renew ? 'sí' : 'no', $b->active ? 'sí' : 'no',
        ])->all();
        $this->table(['#', 'Cobro', 'Monto', 'Periodicidad', 'Próxima fecha', 'Aviso (días)', 'Auto', 'Activo'], $rows);

        return self::SUCCESS;
    }
}
