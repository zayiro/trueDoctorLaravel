<?php

declare(strict_types=1);

namespace App\Services\Payments;

/**
 * Desglose de un cobro de cita, en centavos. Inmutable.
 */
final class AppointmentQuote
{
    public const BEARER_PLATFORM = 'platform';
    public const BEARER_OWNER    = 'owner';

    public function __construct(
        public readonly int $baseCents,
        public readonly int $commissionCents,
        public readonly int $discountCents,
        public readonly int $totalCents,
        public readonly float $commissionRate,
        public readonly float $feeRate,
        public readonly bool $isClinic,
        public readonly ?string $promoCode = null,
        public readonly ?int $promoId = null,
        public readonly string $discountBearer = self::BEARER_PLATFORM,
    ) {
    }

    /** Fee estimado de la pasarela sobre lo que paga el cliente. */
    public function estimatedFeeCents(): int
    {
        return GatewayFee::providerFee($this->totalCents);
    }

    public function ownerBearsDiscount(): bool
    {
        return $this->discountBearer === self::BEARER_OWNER;
    }

    /** Lo que recibe el doctor/clínica: el precio completo, menos el descuento cuando lo asume él. */
    public function doctorAmountCents(): int
    {
        return $this->baseCents - ($this->ownerBearsDiscount() ? $this->discountCents : 0);
    }

    /** Lo que le queda a OpenDoctor: comisión − descuento (si lo asume la plataforma) − fee. Puede ser negativo. */
    public function platformMarginCents(): int
    {
        return $this->commissionCents - ($this->ownerBearsDiscount() ? 0 : $this->discountCents) - $this->estimatedFeeCents();
    }

    public function isFree(): bool
    {
        return $this->totalCents === 0;
    }

    /** Formato que ya usa la vista de resumen (valores en pesos). */
    public function toArray(): array
    {
        return [
            'base'              => $this->baseCents / 100,
            'commission_rate'   => $this->commissionRate,
            'commission_amount' => $this->commissionCents / 100,
            'discount_amount'   => $this->discountCents / 100,
            'total'             => $this->totalCents / 100,
            'wompi_fee'         => $this->estimatedFeeCents() / 100,
            'wompi_fee_rate'    => $this->feeRate,
            'doctor_amount'     => $this->doctorAmountCents() / 100,
            'discount_bearer'   => $this->discountBearer,
            'platform_amount'   => $this->platformMarginCents() / 100,
            'is_clinic'         => $this->isClinic,
            'is_free'           => $this->isFree(),
            'promo_code'        => $this->promoCode,
        ];
    }
}
