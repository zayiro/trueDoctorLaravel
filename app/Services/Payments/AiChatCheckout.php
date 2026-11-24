<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Enums\PaymentKind;
use App\Enums\PaymentTransactionStatus;
use App\Models\AiChatSession;
use App\Models\Payment;
use App\Payments\PaymentService;
use App\Services\AiChat\AiChatPricing;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Cobro de las sesiones del Chat de Salud con IA sobre la capa de pagos.
 * Todo el dinero es de OpenDoctor (sin doctor ni clínica, sin desembolso).
 *
 * Los montos salen de la sesión, que los congeló en el servidor al empezar el pago:
 * price_cop = base, total_cop = base + comisión de la pasarela.
 */
final class AiChatCheckout
{
    public function __construct(private readonly PaymentService $payments)
    {
    }

    /**
     * Crea un intento de cobro nuevo (cada intento es un Payment distinto).
     *
     * @throws DomainException si la sesión ya no se puede pagar.
     */
    public function start(AiChatSession $session): Payment
    {
        return DB::transaction(function () use ($session) {
            $locked = AiChatSession::query()->whereKey($session->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== AiChatSession::STATUS_AWAITING_PAYMENT) {
                throw new DomainException('Esta sesión ya no se puede pagar.');
            }

            $base = (int) $locked->price_cop;
            $total = (int) $locked->total_cop;

            if ($base <= 0 || $total < $base) {
                throw new DomainException('La sesión tiene montos inconsistentes.');
            }

            // Intentos anteriores sin resultado: ya no se van a usar (si alguno se pagó igual, el webhook lo acredita).
            Payment::where('payable_type', $locked->getMorphClass())
                ->where('payable_id', $locked->getKey())
                ->where('kind', PaymentKind::AiChat->value)
                ->where('status', PaymentTransactionStatus::Pending->value)
                ->get()
                ->each(fn (Payment $old) => $this->payments->markExpired($old));

            return $this->payments->start(
                payable: $locked,
                payer: $locked->user,
                kind: PaymentKind::AiChat,
                baseInCents: $base * 100,
                commissionInCents: ($total - $base) * 100,
                discountInCents: 0,
                providerFeeInCents: AiChatPricing::providerFee($total) * 100,
            );
        });
    }

    /** URL de la pasarela para un pago creado con start(). */
    public function checkoutUrl(Payment $payment): string
    {
        return $this->payments->checkoutUrl($payment, route('payments.return', $payment->reference));
    }
}
