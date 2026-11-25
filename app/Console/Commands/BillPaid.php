<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BillPaid extends Command
{
    protected $signature = 'bills:paid {id : Número del cobro (ver bills:list)} {--amount= : Monto pagado esta vez, opcional}';

    protected $description = 'Marca un cobro como pagado y avanza su próxima fecha según la periodicidad';

    public function handle(): int
    {
        $b = DB::table('recurring_bills')->where('id', $this->argument('id'))->first();
        if (!$b) {
            $this->error('No existe ese cobro. Mira los números con: php artisan bills:list');

            return self::FAILURE;
        }

        $due = Carbon::parse($b->due_at);
        $next = match ($b->cadence) {
            'yearly' => $due->copy()->addYear(),
            'quarterly' => $due->copy()->addMonths(3),
            'once' => null,
            default => $due->copy()->addMonth(),
        };

        $upd = ['last_paid_at' => now()->toDateString(), 'updated_at' => now()];
        if ($this->option('amount') !== null && is_numeric($this->option('amount'))) {
            $upd['amount'] = (float) $this->option('amount');
        }
        if ($next) {
            $upd['due_at'] = $next->toDateString();
        } else {
            $upd['active'] = 0;
        }
        DB::table('recurring_bills')->where('id', $b->id)->update($upd);
        $this->info($next ? "{$b->name}: pagado. Próxima fecha {$next->toDateString()}" : "{$b->name}: pagado y desactivado (cobro único).");

        return self::SUCCESS;
    }
}
