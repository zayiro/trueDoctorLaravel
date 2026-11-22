<?php

namespace App\Services\Payments;

use App\Models\Appointment;
use App\Models\DoctorPayout;
use App\Models\PayoutBatch;
use Illuminate\Support\Facades\DB;

/**
 * Registro de la transferencia de una liquidación. Un desembolso solo se paga una vez.
 */
class PayoutSettlement
{
    /** @return array{0: bool, 1: string} [ok, mensaje] */
    public static function markPaid(DoctorPayout $payout, string $reference, ?int $userId, bool $viaBatch = false): array
    {
        $reference = trim($reference);
        if ($reference === '') {
            return [false, 'La referencia de la transferencia es obligatoria.'];
        }

        return DB::transaction(function () use ($payout, $reference, $userId, $viaBatch) {
            $p = DoctorPayout::whereKey($payout->getKey())->lockForUpdate()->first();
            if (!$p) {
                return [false, 'La liquidación ya no existe.'];
            }

            if ($p->status !== DoctorPayout::STATUS_PENDING) {
                return [false, $p->status === DoctorPayout::STATUS_PAID
                    ? 'Esta liquidación ya estaba pagada (ref. ' . $p->transfer_reference . '). No se modificó.'
                    : 'Esta liquidación está en estado "' . $p->status . '" y no se puede pagar.'];
            }

            if ($p->batch_id && !$viaBatch) {
                $num = PayoutBatch::whereKey($p->batch_id)->value('number');

                return [false, 'Esta liquidación pertenece al lote ' . $num . ': regístrala desde el lote.'];
            }

            if (DB::table('payment_refunds')->where('appointment_id', $p->appointment_id)->exists()) {
                return [false, 'La cita tiene un reembolso registrado: no se debe liquidar.'];
            }

            $a  = Appointment::find($p->appointment_id);
            $ps = $a ? ($a->payment_status instanceof \BackedEnum ? $a->payment_status->value : (string) $a->payment_status) : '';
            if (in_array($ps, ['refunded', 'cancelled', 'failed'], true)) {
                return [false, 'El pago de la cita está en estado "' . $ps . '": no se debe liquidar. Revisa el reembolso.'];
            }

            $p->update([
                'status'             => DoctorPayout::STATUS_PAID,
                'paid_at'            => now(),
                'paid_by'            => $userId,
                'transfer_reference' => $reference,
            ]);

            return [true, 'Pago registrado correctamente.'];
        });
    }
}
