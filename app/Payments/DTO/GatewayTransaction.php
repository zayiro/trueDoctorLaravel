<?php

declare(strict_types=1);

namespace App\Payments\DTO;

use App\Enums\PaymentTransactionStatus;

/**
 * Una transacción del proveedor, ya normalizada a nuestros estados.
 */
final class GatewayTransaction
{
    /**
     * @param array<string, mixed> $raw Respuesta original del proveedor (no se guarda completa).
     */
    public function __construct(
        public readonly string $id,
        public readonly string $reference,
        public readonly PaymentTransactionStatus $status,
        public readonly int $amountInCents,
        public readonly string $currency,
        public readonly array $raw = [],
    ) {
    }

    /**
     * Solo los datos que conviene conservar: sin correo, teléfono, tarjeta ni datos del cliente.
     *
     * @return array<string, mixed>
     */
    public function safePayload(): array
    {
        return array_filter([
            'id'                  => $this->id,
            'status'              => $this->raw['status'] ?? null,
            'status_message'      => $this->raw['status_message'] ?? null,
            'payment_method_type' => $this->raw['payment_method_type'] ?? null,
            'amount_in_cents'     => $this->amountInCents,
            'currency'            => $this->currency,
            'finalized_at'        => $this->raw['finalized_at'] ?? null,
        ], fn ($value) => $value !== null);
    }
}
