<?php

declare(strict_types=1);

namespace App\Payments;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Gateways\WompiGateway;
use InvalidArgumentException;

/**
 * Elige el proveedor de pagos. El "switch" es config('payments.default') (PAYMENTS_GATEWAY en .env).
 * Para agregar otro proveedor: una clase que implemente PaymentGateway y un caso en el match.
 */
final class PaymentGatewayManager
{
    /** @var array<string, PaymentGateway> */
    private array $resolved = [];

    public function driver(?string $name = null): PaymentGateway
    {
        $name ??= (string) config('payments.default', 'wompi');

        return $this->resolved[$name] ??= match ($name) {
            'wompi' => new WompiGateway((array) config('services.wompi', [])),
            default => throw new InvalidArgumentException("Proveedor de pagos no soportado: {$name}"),
        };
    }
}
