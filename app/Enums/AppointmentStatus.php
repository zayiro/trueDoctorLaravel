<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'pending',
            self::CONFIRMED => 'confirmed',
            self::CANCELLED => 'cancelled',
            self::COMPLETED => 'completed',
        };
    }
}
