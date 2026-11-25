<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BillAdd extends Command
{
    protected $signature = 'bills:add {name : Nombre del cobro, ej. "AWS"} {--amount= : Monto (omítelo si aún no lo sabes)} {--currency=USD} {--cadence=monthly : monthly|quarterly|yearly|once} {--due= : Próxima fecha de cobro AAAA-MM-DD} {--remind=5 : Días de aviso antes} {--auto : Se cobra solo a la tarjeta: la fecha avanza sola} {--note=}';

    protected $description = 'Registra un cobro recurrente para que el dashboard del admin avise antes de la fecha';

    public function handle(): int
    {
        if (!in_array($this->option('cadence'), ['monthly', 'quarterly', 'yearly', 'once'], true)) {
            $this->error('--cadence debe ser monthly, quarterly, yearly o once.');

            return self::FAILURE;
        }
        try {
            $due = Carbon::parse((string) $this->option('due'))->toDateString();
        } catch (\Throwable) {
            $this->error('Indica la próxima fecha con --due=AAAA-MM-DD');

            return self::FAILURE;
        }
        if ($this->option('amount') !== null && !is_numeric($this->option('amount'))) {
            $this->error('--amount debe ser un número.');

            return self::FAILURE;
        }

        $id = DB::table('recurring_bills')->insertGetId([
            'name' => mb_substr((string) $this->argument('name'), 0, 80),
            'amount' => $this->option('amount') !== null ? (float) $this->option('amount') : null,
            'currency' => strtoupper(substr((string) $this->option('currency'), 0, 3)),
            'cadence' => $this->option('cadence'),
            'due_at' => $due,
            'remind_days' => (int) $this->option('remind'),
            'auto_renew' => $this->option('auto') ? 1 : 0,
            'note' => $this->option('note') ? mb_substr((string) $this->option('note'), 0, 255) : null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->info("Cobro #{$id} registrado: {$this->argument('name')} · próxima fecha {$due}");

        return self::SUCCESS;
    }
}
