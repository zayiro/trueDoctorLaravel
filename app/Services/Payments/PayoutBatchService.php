<?php

namespace App\Services\Payments;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\DoctorPayout;
use App\Models\PayoutBatch;
use App\Models\PayoutBatchEvent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Lotes semanales de liquidación: una sola puerta para crear, revisar, aprobar y pagar.
 * Un desembolso pertenece a un solo lote a la vez (doctor_payouts.batch_id).
 */
class PayoutBatchService
{
    public const TZ = 'America/Bogota';

    /** Último jueves a las 23:59:59 (hora de Colombia) que ya pasó. */
    public static function lastCutoff(?Carbon $now = null): Carbon
    {
        $now = ($now ?? Carbon::now(self::TZ))->copy()->timezone(self::TZ);
        $d = $now->copy()->startOfDay();
        while ($d->dayOfWeek !== Carbon::THURSDAY || $d->copy()->setTime(23, 59, 59)->gt($now->copy()->setMicroseconds(0))) {
            $d->subDay();
        }

        return $d->setTime(23, 59, 59);
    }

    /** Viernes de pago: hoy si es viernes, si no el próximo. */
    public static function nextPayDate(?Carbon $now = null): Carbon
    {
        $d = ($now ?? Carbon::now(self::TZ))->copy()->timezone(self::TZ)->startOfDay();
        while ($d->dayOfWeek !== Carbon::FRIDAY) { $d->addDay(); }

        return $d;
    }

    /** Desembolsos que pueden entrar a un lote con ese corte. */
    public static function eligible(Carbon $cutoff): Builder
    {
        $cut = $cutoff->copy()->timezone(self::TZ)->format('Y-m-d H:i:s');

        return DoctorPayout::query()
            ->where('status', DoctorPayout::STATUS_PENDING)
            ->whereNull('batch_id')
            ->whereHas('appointment', fn ($a) => $a->where('status', 'completed')->where('payment_status', 'paid')
                ->whereRaw('TIMESTAMP(appointments.date, appointments.end_time) <= ?', [$cut]))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('payment_refunds')
                ->whereColumn('payment_refunds.appointment_id', 'doctor_payouts.appointment_id'));
    }

    /** ¿Sigue siendo pagable? (se revisa al aprobar). */
    public static function stillPayable(DoctorPayout $p): bool
    {
        $a = \App\Models\Appointment::find($p->appointment_id);
        if (!$a) { return false; }
        $ps = $a->payment_status instanceof \BackedEnum ? $a->payment_status->value : (string) $a->payment_status;

        return $p->status === DoctorPayout::STATUS_PENDING
            && $ps === 'paid'
            && !DB::table('payment_refunds')->where('appointment_id', $p->appointment_id)->exists();
    }

    public static function createDraft(Carbon $cutoff, ?int $userId, ?Carbon $payDate = null): ?PayoutBatch
    {
        $payDate = ($payDate ?? self::nextPayDate())->copy()->startOfDay();

        return DB::transaction(function () use ($cutoff, $userId, $payDate) {
            $ids = self::eligible($cutoff)->lockForUpdate()->pluck('doctor_payouts.id');
            if ($ids->isEmpty()) { return null; }

            $batch = PayoutBatch::create([
                'number'    => 'tmp-' . bin2hex(random_bytes(6)),
                'status'    => PayoutBatch::DRAFT,
                'cutoff_at' => $cutoff->copy()->timezone(config('app.timezone', 'UTC')),
                'pay_date'  => $payDate->format('Y-m-d'),
                'created_by' => $userId,
            ]);
            $seq = PayoutBatch::whereDate('pay_date', $payDate->format('Y-m-d'))->count();
            $batch->update(['number' => 'LQ-' . $payDate->format('Ymd') . '-' . str_pad((string) $seq, 2, '0', STR_PAD_LEFT)]);

            DoctorPayout::whereIn('id', $ids)->update(['batch_id' => $batch->id]);
            self::recalc($batch);
            self::log($batch, 'created', $userId, null, ['cutoff' => $cutoff->format('Y-m-d H:i'), 'lines' => $ids->count()]);

            return $batch->fresh();
        });
    }

    public static function recalc(PayoutBatch $batch): void
    {
        $q = DoctorPayout::where('batch_id', $batch->id);
        $batch->update([
            'payouts_count'       => (clone $q)->count(),
            'beneficiaries_count' => (clone $q)->distinct()->count(DB::raw("concat(payable_type, '#', payable_id)")),
            'total_amount'        => (clone $q)->sum('amount_to_pay'),
        ]);
    }

    /** @return array{0: bool, 1: string} */
    public static function releaseLine(PayoutBatch $batch, DoctorPayout $payout, ?int $userId): array
    {
        return DB::transaction(function () use ($batch, $payout, $userId) {
            $b = PayoutBatch::whereKey($batch->id)->lockForUpdate()->first();
            $p = DoctorPayout::whereKey($payout->id)->lockForUpdate()->first();
            if (!$b || !$b->isOpen()) { return [false, 'El lote ya no admite cambios.']; }
            if (!$p || (int) $p->batch_id !== (int) $b->id) { return [false, 'Esa liquidación no pertenece a este lote.']; }
            if ($p->status !== DoctorPayout::STATUS_PENDING) { return [false, 'Esa liquidación ya está pagada.']; }

            $p->update(['batch_id' => null]);
            self::recalc($b);
            self::log($b, 'line_released', $userId, $p->id);

            return [true, 'La liquidación salió del lote y volverá a estar disponible para el siguiente.'];
        });
    }

    /** @return array{0: bool, 1: string} */
    public static function approve(PayoutBatch $batch, ?int $userId): array
    {
        return DB::transaction(function () use ($batch, $userId) {
            $b = PayoutBatch::whereKey($batch->id)->lockForUpdate()->first();
            if (!$b || $b->status !== PayoutBatch::DRAFT) { return [false, 'Solo se puede aprobar un lote en borrador.']; }

            $removed = 0;
            foreach (DoctorPayout::where('batch_id', $b->id)->lockForUpdate()->get() as $p) {
                if (!self::stillPayable($p)) {
                    $p->update(['batch_id' => null]);
                    self::log($b, 'line_auto_removed', $userId, $p->id, ['motivo' => 'ya no es pagable (reembolso o cambio de estado)']);
                    $removed++;
                }
            }
            self::recalc($b);
            if ($b->fresh()->payouts_count === 0) { return [false, 'Tras revisar, ninguna línea seguía siendo pagable. Cancela el lote.']; }

            $b->update(['status' => PayoutBatch::APPROVED, 'approved_by' => $userId, 'approved_at' => now()]);
            self::log($b, 'approved', $userId, null, ['removed' => $removed]);

            return [true, 'Lote aprobado.' . ($removed ? " Salieron $removed línea(s) que ya no eran pagables." : '')];
        });
    }

    /** Paga TODAS las líneas pendientes de un beneficiario con una sola transferencia. @return array{0: bool, 1: string} */
    public static function payBeneficiary(PayoutBatch $batch, string $payableType, int $payableId, string $reference, ?int $userId): array
    {
        $class = ['doctor' => Doctor::class, 'clinic' => Clinic::class][$payableType] ?? null;
        if (!$class) { return [false, 'Tipo de beneficiario no válido.']; }

        try {
            return DB::transaction(function () use ($batch, $class, $payableType, $payableId, $reference, $userId) {
                $b = PayoutBatch::whereKey($batch->id)->lockForUpdate()->first();
                if (!$b || $b->status !== PayoutBatch::APPROVED) { return [false, 'El lote debe estar aprobado para registrar pagos.']; }

                $lines = DoctorPayout::where('batch_id', $b->id)->where('payable_type', $class)->where('payable_id', $payableId)
                    ->where('status', DoctorPayout::STATUS_PENDING)->lockForUpdate()->get();
                if ($lines->isEmpty()) { return [false, 'Ese beneficiario no tiene líneas pendientes en este lote.']; }

                $account = BankAccountService::forOwner($class, $payableId);
                if (!$account) { return [false, 'El beneficiario no tiene cuenta bancaria registrada. Pídele que la cargue en «Mis liquidaciones».']; }
                if (!$account->isVerified()) {
                    return [false, 'La cuenta bancaria no está verificada. Haz la transferencia, confirma en tu banco que el nombre del titular coincide y márcala como verificada; luego registra la referencia.'];
                }

                $sum = 0.0;
                foreach ($lines as $line) {
                    [$ok, $msg] = PayoutSettlement::markPaid($line, $reference, $userId, true);
                    if (!$ok) { throw new \RuntimeException($msg); }
                    $sum += (float) $line->amount_to_pay;
                }

                \Illuminate\Support\Facades\DB::afterCommit(fn () => \App\Jobs\SendPayoutReport::dispatch($b->id, $payableType, $payableId));
                self::log($b, 'group_paid', $userId, null, ['type' => $class, 'id' => $payableId, 'reference' => trim($reference), 'lines' => $lines->count(), 'amount' => $sum]);

                if (!DoctorPayout::where('batch_id', $b->id)->where('status', DoctorPayout::STATUS_PENDING)->exists()) {
                    $b->update(['status' => PayoutBatch::PAID, 'paid_at' => now()]);
                    self::log($b, 'completed', $userId);
                }

                return [true, 'Transferencia registrada: ' . $lines->count() . ' liquidación(es) por $' . number_format($sum, 0, ',', '.') . '.'];
            });
        } catch (\RuntimeException $e) {
            return [false, $e->getMessage()];
        }
    }

    /** @return array{0: bool, 1: string} */
    public static function cancel(PayoutBatch $batch, ?int $userId): array
    {
        return DB::transaction(function () use ($batch, $userId) {
            $b = PayoutBatch::whereKey($batch->id)->lockForUpdate()->first();
            if (!$b || !$b->isOpen()) { return [false, 'Este lote ya no se puede cancelar.']; }
            if (DoctorPayout::where('batch_id', $b->id)->where('status', DoctorPayout::STATUS_PAID)->exists()) {
                return [false, 'El lote ya tiene transferencias registradas; no se puede cancelar.'];
            }

            DoctorPayout::where('batch_id', $b->id)->update(['batch_id' => null]);
            $b->update(['status' => PayoutBatch::CANCELLED, 'cancelled_at' => now()]);
            self::recalc($b);
            self::log($b, 'cancelled', $userId);

            return [true, 'Lote cancelado. Sus liquidaciones quedaron libres para el siguiente.'];
        });
    }

    public static function log(PayoutBatch $batch, string $event, ?int $userId, ?int $payoutId = null, ?array $meta = null): void
    {
        PayoutBatchEvent::create(['batch_id' => $batch->id, 'payout_id' => $payoutId, 'user_id' => $userId, 'event' => $event, 'meta' => $meta]);
    }
}
