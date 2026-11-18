<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';
    case EXPIRED = 'expired';     // no se pagó a tiempo: el turno se liberó
    case NO_SHOW = 'no_show';     // el paciente no se presentó

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'pending',
            self::CONFIRMED => 'confirmed',
            self::CANCELLED => 'cancelled',
            self::COMPLETED => 'completed',
            self::EXPIRED => 'expired',
            self::NO_SHOW => 'no_show',
        };
    }

    /** Texto para mostrar al usuario. */
    public function text(): string
    {
        return match($this) {
            self::PENDING => 'Pendiente',
            self::CONFIRMED => 'Confirmada',
            self::CANCELLED => 'Cancelada',
            self::COMPLETED => 'Completada',
            self::EXPIRED => 'Expirada',
            self::NO_SHOW => 'No asistió',
        };
    }

    /** Estados que ya no se pueden modificar. */
    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED, self::EXPIRED, self::NO_SHOW], true);
    }
}
