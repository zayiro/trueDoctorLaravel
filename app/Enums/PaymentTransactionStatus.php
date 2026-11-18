<?php

namespace App\Enums;

/**
 * Estado de una transacción en la tabla `payments`.
 *
 * No confundir con App\Enums\PaymentStatus, que es el estado de pago de una cita
 * (appointments.payment_status).
 *
 * Nota sobre Wompi: una misma referencia puede recibir varios intentos (rechazado, rechazado, aprobado),
 * por eso Declined y Error pueden pasar a Approved. Un estado Approved solo puede terminar anulado o reembolsado.
 */
enum PaymentTransactionStatus: string
{
    case Pending  = 'pending';
    case Approved = 'approved';
    case Declined = 'declined';
    case Voided   = 'voided';
    case Error    = 'error';
    case Expired  = 'expired';
    case Refunded = 'refunded';
    case Review   = 'review';

    public function label(): string
    {
        return match ($this) {
            self::Pending  => 'Pendiente',
            self::Approved => 'Aprobado',
            self::Declined => 'Rechazado',
            self::Voided   => 'Anulado',
            self::Error    => 'Error',
            self::Expired  => 'Expirado',
            self::Refunded => 'Reembolsado',
            self::Review   => 'En revisión',
        };
    }

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending  => [self::Approved, self::Declined, self::Voided, self::Error, self::Expired, self::Review],
            self::Expired  => [self::Approved, self::Declined, self::Voided, self::Error, self::Review],
            self::Declined => [self::Approved, self::Error, self::Review],
            self::Error    => [self::Approved, self::Declined, self::Review],
            self::Approved => [self::Voided, self::Refunded, self::Review],
            self::Voided, self::Refunded, self::Review => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isApproved(): bool
    {
        return $this === self::Approved;
    }

    /** Aún puede recibir un resultado del proveedor. */
    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Expired], true);
    }
}
