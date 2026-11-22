<?php

declare(strict_types=1);

namespace App\Payments;

use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
use App\Enums\PaymentTransactionStatus;
use App\Models\Payment;
use App\Models\User;
use App\Payments\DTO\GatewayTransaction;
use App\Payments\Events\PaymentApproved;
use App\Payments\Events\PaymentDeclined;
use App\Payments\Events\PaymentNeedsReview;
use App\Payments\Events\PaymentVoided;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Punto único para crear pagos y aplicar sus resultados.
 *
 * El webhook, el regreso del navegador y el comando de conciliación llaman todos a handleTransaction():
 * es idempotente (se puede llamar muchas veces con el mismo resultado) y serializa con un bloqueo de fila,
 * así que un pago nunca se aprueba dos veces ni dispara dos veces sus efectos.
 */
final class PaymentService
{
    public function __construct(private readonly PaymentGatewayManager $gateways)
    {
    }

    /**
     * Crea un pago pendiente con una referencia nueva. Cada intento de cobro es un Payment distinto.
     *
     * total = base - descuento + comisión (lo que paga el cliente).
     */
    public function start(
        Model $payable,
        ?User $payer,
        PaymentKind $kind,
        int $baseInCents,
        int $commissionInCents = 0,
        int $discountInCents = 0,
        int $providerFeeInCents = 0,
        PaymentMethod $method = PaymentMethod::Online,
        ?CarbonInterface $expiresAt = null,
        ?int $promoCodeId = null,
        ?Model $payee = null,
        ?string $provider = null,
        ?array $payload = null,
    ): Payment {
        foreach (compact('baseInCents', 'commissionInCents', 'discountInCents', 'providerFeeInCents') as $name => $value) {
            if ($value < 0) {
                throw new InvalidArgumentException("El monto {$name} no puede ser negativo.");
            }
        }

        $total = $baseInCents - $discountInCents + $commissionInCents;

        if ($total < 0) {
            throw new InvalidArgumentException('El descuento no puede ser mayor que el valor a pagar.');
        }

        if ($method === PaymentMethod::Online && $total === 0) {
            throw new InvalidArgumentException('Un pago en línea necesita un total mayor a 0. Para citas sin cobro no se crea pago.');
        }

        return Payment::create([
            'reference'             => 'PAY-' . strtoupper((string) Str::ulid()),
            'provider'              => $provider ?? (string) config('payments.default', 'wompi'),
            'kind'                  => $kind,
            'method'                => $method,
            'status'                => PaymentTransactionStatus::Pending,
            'payable_type'          => $payable->getMorphClass(),
            'payable_id'            => $payable->getKey(),
            'payer_id'              => $payer?->id,
            'payee_type'            => $payee?->getMorphClass(),
            'payee_id'              => $payee?->getKey(),
            'base_amount_in_cents'  => $baseInCents,
            'commission_in_cents'   => $commissionInCents,
            'discount_in_cents'     => $discountInCents,
            'provider_fee_in_cents' => $providerFeeInCents,
            'total_in_cents'        => $total,
            'currency'              => (string) config('payments.currency', 'COP'),
            'promo_code_id'         => $promoCodeId,
            'expires_at'            => $expiresAt,
            'payload'               => $payload,
        ]);
    }

    /**
     * Registra una compra con total 0 (p. ej. código promocional del 100 %) como pago aprobado,
     * para que dispare los mismos efectos (confirmar, desembolso al doctor, consumir el código).
     */
    public function settleWithoutCharge(
        Model $payable,
        ?User $payer,
        PaymentKind $kind,
        int $baseInCents,
        int $commissionInCents,
        int $discountInCents,
        ?int $promoCodeId = null,
        ?Model $payee = null,
    ): Payment {
        if ($baseInCents - $discountInCents + $commissionInCents !== 0) {
            throw new InvalidArgumentException('Solo se puede registrar sin cobro una compra con total 0.');
        }

        return DB::transaction(function () use ($payable, $payer, $kind, $baseInCents, $commissionInCents, $discountInCents, $promoCodeId, $payee) {
            $payment = Payment::create([
                'reference'            => 'PAY-' . strtoupper((string) Str::ulid()),
                'provider'             => 'internal',
                'kind'                 => $kind,
                'method'               => PaymentMethod::Free,
                'status'               => PaymentTransactionStatus::Approved,
                'payable_type'         => $payable->getMorphClass(),
                'payable_id'           => $payable->getKey(),
                'payer_id'             => $payer?->id,
                'payee_type'           => $payee?->getMorphClass(),
                'payee_id'             => $payee?->getKey(),
                'base_amount_in_cents' => $baseInCents,
                'commission_in_cents'  => $commissionInCents,
                'discount_in_cents'    => $discountInCents,
                'total_in_cents'       => 0,
                'currency'             => (string) config('payments.currency', 'COP'),
                'promo_code_id'        => $promoCodeId,
                'approved_at'          => now(),
            ]);

            DB::afterCommit(fn () => $this->dispatchEvent($payment));

            return $payment;
        });
    }

    /** URL a la que se envía al usuario para pagar. */
    public function checkoutUrl(Payment $payment, ?string $redirectUrl = null): string
    {
        return $this->gateways->driver($payment->provider)->checkout($payment, $redirectUrl)->url;
    }

    /**
     * Aplica el resultado de una transacción del proveedor. Devuelve el pago, o null si la referencia no es nuestra.
     */
    public function handleTransaction(GatewayTransaction $transaction): ?Payment
    {
        return DB::transaction(function () use ($transaction) {
            $payment = Payment::where('reference', $transaction->reference)->lockForUpdate()->first();

            if (!$payment) {
                Log::info('payments.unknown_reference', ['reference' => $transaction->reference]);

                return null;
            }

            $target = $transaction->status;

            // Mismo estado (webhook repetido, regreso del navegador recargado): no se hace nada más.
            if ($payment->status === $target) {
                if ($payment->provider_transaction_id === null && $transaction->id !== '') {
                    $payment->update(['provider_transaction_id' => $transaction->id]);
                }

                return $payment;
            }

            // Eventos fuera de orden (p. ej. un "rechazado" tardío tras un "aprobado") se ignoran.
            if (!$payment->status->canTransitionTo($target)) {
                Log::info('payments.transition_ignored', [
                    'reference' => $payment->reference,
                    'from'      => $payment->status->value,
                    'to'        => $target->value,
                ]);

                return $payment;
            }

            if ($target === PaymentTransactionStatus::Approved) {
                $problem = $this->approvalProblem($payment, $transaction);

                if ($problem !== null) {
                    return $this->sendToReview($payment, $transaction, $problem);
                }
            }

            $payment->fill([
                'status'                  => $target,
                'provider_transaction_id' => $transaction->id !== '' ? $transaction->id : $payment->provider_transaction_id,
                // Se conserva lo que guardó el cobro (p. ej. quién asume el descuento) y se agrega lo de la pasarela.
                'payload'                 => array_merge($payment->payload ?? [], $transaction->safePayload()),
            ]);

            if ($target === PaymentTransactionStatus::Approved) {
                $payment->approved_at = now();
            }

            $payment->save();

            DB::afterCommit(fn () => $this->dispatchEvent($payment));

            return $payment;
        });
    }

    /** Consulta al proveedor por referencia y aplica lo que encuentre (conciliación y regreso del navegador). */
    public function syncByReference(string $reference): ?Payment
    {
        $payment = Payment::where('reference', $reference)->first();

        if (!$payment) {
            return null;
        }

        $transaction = $this->gateways->driver($payment->provider)->fetchByReference($reference);

        return $transaction ? $this->handleTransaction($transaction) : $payment->fresh();
    }

    /** Consulta al proveedor por id de transacción (Wompi lo manda como ?id= al regresar al sitio). */
    public function syncByTransactionId(string $transactionId, ?string $provider = null): ?Payment
    {
        $transaction = $this->gateways->driver($provider)->fetchTransaction($transactionId);

        return $transaction ? $this->handleTransaction($transaction) : null;
    }

    /** El doctor marcó que cobró en consulta. */
    public function markPaidOnSite(Payment $payment, ?User $by = null): Payment
    {
        return DB::transaction(function () use ($payment, $by) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->method !== PaymentMethod::OnSite) {
                throw new InvalidArgumentException('Solo los pagos en consulta se marcan manualmente.');
            }

            if ($locked->status !== PaymentTransactionStatus::Pending) {
                return $locked;
            }

            $locked->fill([
                'status'      => PaymentTransactionStatus::Approved,
                'approved_at' => now(),
                'payload'     => array_merge((array) $locked->payload, ['marked_by_user_id' => $by?->id, 'marked_at' => now()->toIso8601String()]),
            ])->save();

            DB::afterCommit(fn () => $this->dispatchEvent($locked));

            return $locked;
        });
    }

    /** Pasa a "expirado" un pago pendiente que nunca recibió resultado. No cancela nada más. */
    public function markExpired(Payment $payment): void
    {
        Payment::whereKey($payment->id)
            ->where('status', PaymentTransactionStatus::Pending->value)
            ->update(['status' => PaymentTransactionStatus::Expired->value]);
    }

    /** Motivo por el que una aprobación no se puede dar por buena, o null si está bien. */
    private function approvalProblem(Payment $payment, GatewayTransaction $transaction): ?string
    {
        if ($transaction->amountInCents !== (int) $payment->total_in_cents) {
            return 'amount_mismatch';
        }

        if (strtoupper($transaction->currency) !== strtoupper((string) $payment->currency)) {
            return 'currency_mismatch';
        }

        $alreadyPaid = Payment::where('payable_type', $payment->payable_type)
            ->where('payable_id', $payment->payable_id)
            ->where('kind', $payment->kind->value)
            ->where('id', '!=', $payment->id)
            ->where('status', PaymentTransactionStatus::Approved->value)
            ->exists();

        return $alreadyPaid ? 'duplicate_payment' : null;
    }

    private function sendToReview(Payment $payment, GatewayTransaction $transaction, string $reason): Payment
    {
        $payment->fill([
            'status'                  => PaymentTransactionStatus::Review,
            'review_reason'           => $reason,
            'provider_transaction_id' => $transaction->id !== '' ? $transaction->id : $payment->provider_transaction_id,
            // Se conserva lo que guardó el cobro (p. ej. quién asume el descuento) y se agrega lo de la pasarela.
                'payload'                 => array_merge($payment->payload ?? [], $transaction->safePayload()),
        ])->save();

        Log::warning('payments.needs_review', [
            'reference' => $payment->reference,
            'reason'    => $reason,
            'expected'  => (int) $payment->total_in_cents,
            'received'  => $transaction->amountInCents,
        ]);

        DB::afterCommit(fn () => $this->dispatchEvent($payment));

        return $payment;
    }

    private function dispatchEvent(Payment $payment): void
    {
        match ($payment->status) {
            PaymentTransactionStatus::Approved => event(new PaymentApproved($payment)),
            PaymentTransactionStatus::Declined,
            PaymentTransactionStatus::Error    => event(new PaymentDeclined($payment)),
            PaymentTransactionStatus::Voided   => event(new PaymentVoided($payment)),
            PaymentTransactionStatus::Review   => event(new PaymentNeedsReview($payment, (string) $payment->review_reason)),
            default                            => null,
        };
    }
}