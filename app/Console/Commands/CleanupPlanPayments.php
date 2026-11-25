<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Limpia las filas `pending` de suscripciones que nunca se pagaron (clics en «pagar» que el médico abandonó).
 * No toca pagos ni suscripciones aprobadas/rechazadas: solo `pending` de más de 7 días sin ningún pago vivo
 * (pendiente, aprobado o en revisión) que dependa de ellas.
 */
class CleanupPlanPayments extends Command
{
    protected $signature = 'plans:cleanup {--dry-run : Muestra cuántas filas se borrarían sin borrar nada}';

    protected $description = 'Borra suscripciones pendientes abandonadas (más de 7 días y sin pago vivo)';

    public function handle(): int
    {
        $morph = (new Subscription())->getMorphClass();

        $query = Subscription::query()
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subDays(7))
            ->whereNotExists(function ($q) use ($morph) {
                $q->select(DB::raw(1))
                    ->from('payments')
                    ->whereColumn('payments.payable_id', 'subscriptions.id')
                    ->where('payments.payable_type', $morph)
                    ->whereIn('payments.status', ['pending', 'approved', 'review']);
            });

        $count = (clone $query)->count();

        if ($this->option('dry-run')) {
            $this->info("DRY-RUN: se borrarían {$count} suscripción(es) pendiente(s) abandonada(s).");

            return self::SUCCESS;
        }

        $deleted = $count > 0 ? $query->delete() : 0;
        $this->info("Suscripciones pendientes abandonadas borradas: {$deleted}.");

        return self::SUCCESS;
    }
}
