<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case CANCELLED = 'cancelled';
    case PAY_AT_CLINIC = 'pay_at_clinic';
    case EXEMPT = 'exempt';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pendiente',
            self::PAID => 'Pagado',
            self::FAILED => 'Fallido',
            self::REFUNDED => 'Reembolsado',
            self::CANCELLED => 'Cancelada',
            self::PAY_AT_CLINIC => 'Pago en clínica',
            self::EXEMPT => 'Exento / Control',
        };
    }
}
