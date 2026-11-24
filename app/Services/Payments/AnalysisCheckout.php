<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Enums\PaymentKind;
use App\Enums\PaymentTransactionStatus;
use App\Models\MedicalAnalysis;
use App\Models\Payment;
use App\Models\PromoCode;
use App\Models\Setting;
use App\Models\SkinAnalysis;
use App\Payments\PaymentService;
use App\Services\MedicalAnalysisFilePurger;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Cobro de los análisis con IA (exámenes y piel) sobre la capa de pagos.
 * Todo el dinero es de OpenDoctor: no hay doctor ni clínica de por medio (sin desembolso).
 *
 * Los montos se leen de la orden, que ya los fijó en el servidor al crearla:
 *   - Exámenes: price (ya con descuento) + wompi_fee (el 3 % de la pasarela que se le traslada al cliente).
 *   - Piel: price − descuento + wompi_fee (el 3 % de la pasarela que se le traslada al cliente) = total_amount.
 */
final class AnalysisCheckout
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly MedicalAnalysisFilePurger $purger,
    ) {
    }

    /**
     * Crea un intento de cobro nuevo para la orden (cada intento es un Payment distinto).
     *
     * @throws DomainException si la orden ya no se puede pagar.
     */
    public function start(MedicalAnalysis|SkinAnalysis $analysis): Payment
    {
        return DB::transaction(function () use ($analysis) {
            $locked = $analysis::query()->whereKey($analysis->getKey())->lockForUpdate()->firstOrFail();

            [$kind, $base, $commission, $discount] = $locked instanceof MedicalAnalysis
                ? $this->medicalAmounts($locked)
                : $this->skinAmounts($locked);

            // Intentos anteriores sin resultado: ya no se van a usar (si alguno se pagó igual, el webhook lo acredita).
            Payment::where('payable_type', $locked->getMorphClass())
                ->where('payable_id', $locked->getKey())
                ->where('kind', $kind->value)
                ->where('status', PaymentTransactionStatus::Pending->value)
                ->get()
                ->each(fn (Payment $old) => $this->payments->markExpired($old));

            $total = $base - $discount + $commission;

            $promoId = $locked->promo_code
                ? PromoCode::where('code', strtoupper((string) $locked->promo_code))->value('id')
                : null;

            return $this->payments->start(
                payable: $locked,
                payer: null,
                kind: $kind,
                baseInCents: $base,
                commissionInCents: $commission,
                discountInCents: $discount,
                providerFeeInCents: \App\Services\Payments\GatewayFee::providerFee((int) $total),
                promoCodeId: $promoId ? (int) $promoId : null,
            );
        });
    }

    /** URL de la pasarela para un pago creado con start(). */
    public function checkoutUrl(Payment $payment): string
    {
        return $this->payments->checkoutUrl($payment, route('payments.return', $payment->reference));
    }

    /** @return array{0: PaymentKind, 1: int, 2: int, 3: int} [kind, base, comisión, descuento] en centavos */
    private function medicalAmounts(MedicalAnalysis $analysis): array
    {
        if ($analysis->payment_status === 'completed') {
            throw new DomainException('Esta orden ya fue pagada.');
        }

        $price = (int) round(((float) $analysis->price) * 100);

        if ($price <= 0) {
            throw new DomainException('Esta orden no requiere pago.');
        }

        // La purga elimina los archivos de órdenes viejas sin pagar: sin archivos no hay nada que analizar.
        if ($this->purger->paths($analysis) === []) {
            throw new DomainException('Esta orden ya no está disponible. Sube tus archivos nuevamente.');
        }

        $discount = max(0, (int) $analysis->discount_amount) * 100;

        // La comisión de pasarela va aparte del precio: el cliente paga price + wompi_fee.
        $commission = max(0, (int) $analysis->wompi_fee) * 100;

        return [PaymentKind::Exam, $price + $discount, $commission, $discount];
    }

    /** @return array{0: PaymentKind, 1: int, 2: int, 3: int} */
    private function skinAmounts(SkinAnalysis $analysis): array
    {
        if (!in_array($analysis->status, SkinAnalysis::PAYABLE_STATUSES, true)) {
            throw new DomainException('Esta orden ya no se puede pagar.');
        }

        $total = (int) $analysis->total_amount * 100;

        if ($total <= 0) {
            throw new DomainException('Esta orden no requiere pago.');
        }

        if (Storage::disk('private')->files($analysis->imageDirectory()) === []) {
            throw new DomainException('Esta orden ya no está disponible. Sube tu fotografía nuevamente.');
        }

        $base = (int) $analysis->price * 100;
        $discount = (int) $analysis->discount_amount * 100;
        // La comisión es lo que falta para llegar al total guardado: el total de la orden manda.
        $commission = $total - $base + $discount;

        if ($commission < 0) {
            throw new DomainException('La orden tiene montos inconsistentes. Crea una nueva.');
        }

        return [PaymentKind::SkinAnalysis, $base, $commission, $discount];
    }
}
