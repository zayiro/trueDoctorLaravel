<?php

declare(strict_types=1);

namespace App\Payments\DTO;

/**
 * Lo que el gateway devuelve para llevar al usuario a pagar.
 * Genérico a propósito: Wompi usa una URL con firma; otro proveedor podría usar una preferencia o un widget.
 */
final class CheckoutIntent
{
    public function __construct(
        public readonly string $url,
        public readonly string $reference,
    ) {
    }
}
