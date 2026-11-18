<?php

declare(strict_types=1);

namespace App\Payments\Contracts;

use App\Models\Payment;
use App\Payments\DTO\CheckoutIntent;
use App\Payments\DTO\GatewayTransaction;
use App\Payments\Exceptions\InvalidWebhookSignature;

/**
 * Lo único que el resto del sistema sabe de un proveedor de pagos.
 * Wompi hoy; Mercado Pago u otro mañana, sin tocar los módulos que cobran.
 */
interface PaymentGateway
{
    /** Nombre corto del proveedor, el mismo que se guarda en payments.provider. */
    public function name(): string;

    /** Prepara el cobro de un pago ya creado (con su referencia y su total). */
    public function checkout(Payment $payment, ?string $redirectUrl = null): CheckoutIntent;

    /** Consulta una transacción por su id en el proveedor. Null si no existe. */
    public function fetchTransaction(string $transactionId): ?GatewayTransaction;

    /** Consulta la transacción más relevante de una referencia (la aprobada, si hay). Null si no hay ninguna. */
    public function fetchByReference(string $reference): ?GatewayTransaction;

    /**
     * Valida la firma de un webhook y lo convierte en una transacción normalizada.
     * Devuelve null si el evento es válido pero no es de transacciones.
     *
     * @param array<string, mixed> $payload
     * @throws InvalidWebhookSignature si la firma no es válida
     */
    public function parseWebhook(array $payload, ?string $headerChecksum = null): ?GatewayTransaction;
}
