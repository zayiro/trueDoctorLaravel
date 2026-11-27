<?php

namespace App\Console\Commands;

use App\Services\Accounts\AbandonedAccountCleaner;
use Illuminate\Console\Command;

class AccountsCleanup extends Command
{
    protected $signature = 'accounts:cleanup {--reporte : Solo muestra qué haría, sin avisar ni eliminar nada}';

    protected $description = 'Avisa y elimina las cuentas de paciente que nunca se usaron (aviso a los 13 días, eliminación al día 14)';

    public function handle(AbandonedAccountCleaner $cleaner): int
    {
        if ($this->option('reporte')) {
            $del = $cleaner->deleteList();
            $warn = $cleaner->warnList();

            $this->line('== SE ELIMINARÍAN hoy (' . $del->count() . ') ==');
            foreach ($del as $r) {
                $this->line("  #{$r->id} {$r->email} — creada {$r->created_at}, avisada {$r->cleanup_notified_at}");
            }

            $this->line('== SE AVISARÍA hoy (' . $warn->count() . ', máximo ' . (int) config('account_cleanup.max_warnings_per_run', 30) . ' por corrida) ==');
            foreach ($warn as $r) {
                $this->line("  #{$r->id} {$r->email} — creada {$r->created_at}");
            }

            $this->info('Reporte: no se cambió nada.');

            return self::SUCCESS;
        }

        $deleted = 0;
        foreach ($cleaner->deleteList() as $r) {
            if ($cleaner->delete((int) $r->id)) {
                $deleted++;
            }
        }

        $warned = 0;
        foreach ($cleaner->warnList() as $r) {
            if ($cleaner->warn($r)) {
                $warned++;
            }
        }

        $this->info("Cuentas eliminadas: $deleted. Avisos enviados: $warned.");

        return self::SUCCESS;
    }
}
