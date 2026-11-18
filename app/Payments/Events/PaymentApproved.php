<?php

declare(strict_types=1);

namespace App\Payments\Events;

use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Se dispara DESPUÉS del commit, una sola vez por cambio de estado. */
final class PaymentApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Payment $payment)
    {
    }
}
