<?php

declare(strict_types=1);

namespace App\Payments\Events;

use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Un pago que no se pudo dar por bueno de forma automática: monto o moneda distintos,
 * o un segundo pago aprobado para lo mismo (doble cobro). Requiere revisión manual.
 */
final class PaymentNeedsReview
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Payment $payment,
        public readonly string $reason,
    ) {
    }
}
