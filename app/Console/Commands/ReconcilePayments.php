<?php

namespace App\Console\Commands;

use App\Enums\PaymentMethod;
use App\Enums\PaymentTransactionStatus;
use App\Models\Payment;
use App\Payments\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Red de seguridad: si un webhook se perdió o el paciente cerró el navegador, este comando consulta
 * al proveedor los pagos que siguen pendientes y aplica el resultado.
 */
class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile
        {--minutes= : Solo pagos creados hace más de N minutos (por defecto config payments.reconcile.after_minutes)}
        {--limit= : Máximo de pagos por corrida (por defecto config payments.reconcile.batch)}';

    protected $description = 'Consulta al proveedor los pagos pendientes y aplica su resultado';

    public function handle(PaymentService $payments): int
    {
        if (!config('payments.reconcile.enabled')) {
            $this->info('Conciliación desactivada (PAYMENTS_RECONCILE=false).');

            return self::SUCCESS;
        }

        $minutes = (int) ($this->option('minutes') ?: config('payments.reconcile.after_minutes', 5));
        $limit   = (int) ($this->option('limit') ?: config('payments.reconcile.batch', 100));
        $window  = (int) config('payments.reconcile.window_days', 7);

        $pending = Payment::query()
            ->where('status', PaymentTransactionStatus::Pending->value)
            ->where('method', PaymentMethod::Online->value)
            ->where('created_at', '<=', now()->subMinutes($minutes))
            ->where('created_at', '>=', now()->subDays($window))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $updated = 0;
        $errors  = 0;

        foreach ($pending as $payment) {
            try {
                $fresh = $payments->syncByReference($payment->reference);

                if ($fresh && $fresh->status !== PaymentTransactionStatus::Pending) {
                    $updated++;
                    continue;
                }

                if ($fresh && $fresh->expires_at !== null && $fresh->expires_at->isPast()) {
                    $payments->markExpired($fresh);
                }
            } catch (Throwable $e) {
                $errors++;
                Log::warning('payments.reconcile.error', ['reference' => $payment->reference, 'error' => $e->getMessage()]);
            }
        }

        // Los que llevan más que la ventana sin resultado ya no se consultan: se marcan expirados.
        $stale = Payment::query()
            ->where('status', PaymentTransactionStatus::Pending->value)
            ->where('method', PaymentMethod::Online->value)
            ->where('created_at', '<', now()->subDays($window))
            ->update(['status' => PaymentTransactionStatus::Expired->value]);

        $this->info("Revisados: {$pending->count()} | con resultado: {$updated} | errores: {$errors} | expirados por antigüedad: {$stale}");

        return self::SUCCESS;
    }
}
