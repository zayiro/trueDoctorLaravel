<?php

declare(strict_types=1);

namespace App\Services\Availability;

/**
 * Estado de un turno. Solo `Available` se puede reservar.
 */
enum SlotState: string
{
    case Available = 'available'; // reservable
    case Past = 'past';           // ya pasó (se oculta en la interfaz)
    case TooSoon = 'too_soon';    // falta menos que la anticipación mínima (en gris)
    case Booked = 'booked';       // ocupado por una cita (en gris)
    case Blocked = 'blocked';     // ausencia o bloqueo del doctor
    case Full = 'full';           // el doctor alcanzó su máximo de citas del día

    public function isBookable(): bool
    {
        return $this === self::Available;
    }
}