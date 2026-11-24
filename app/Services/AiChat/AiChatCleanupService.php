<?php

namespace App\Services\AiChat;

use App\Enums\PaymentKind;
use App\Models\AiChatSession;
use Illuminate\Support\Facades\DB;

/**
 * Borra las compras abandonadas (sesión en "pago_pendiente" que nunca se pagó).
 * Nunca toca una sesión que tenga algún pago aprobado, en curso o aún vigente en la pasarela:
 * si Wompi confirma tarde, la sesión tiene que seguir existiendo.
 */
class AiChatCleanupService
{
    /** Horas que una compra sin pagar espera antes de considerarse abandonada. */
    public const ABANDONED_HOURS = 48;

    /** Estados de pago que ya no pueden cambiar. */
    private const FINAL_STATUSES = ['declined', 'error', 'expired'];

    /** @return int sesiones borradas (o que se borrarían con $dryRun) */
    public function abandoned(bool $dryRun = false): int
    {
        $total = 0;

        AiChatSession::where('status', AiChatSession::STATUS_AWAITING_PAYMENT)
            ->where('created_at', '<=', now()->subHours(self::ABANDONED_HOURS))
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$total, $dryRun) {
                $ids = $rows->pluck('id')->all();

                $protected = DB::table('payments')
                    ->where('kind', PaymentKind::AiChat->value)
                    ->whereIn('payable_id', $ids)
                    ->where(function ($q) {
                        $q->whereNotIn('status', self::FINAL_STATUSES)
                            ->orWhereNotNull('approved_at')
                            ->orWhere('expires_at', '>', now());
                    })
                    ->pluck('payable_id')
                    ->all();

                $delete = array_values(array_diff($ids, array_map('intval', $protected)));

                if ($delete === []) {
                    return;
                }

                $total += $dryRun
                    ? count($delete)
                    : AiChatSession::whereIn('id', $delete)->where('status', AiChatSession::STATUS_AWAITING_PAYMENT)->delete();
            });

        return $total;
    }
}
