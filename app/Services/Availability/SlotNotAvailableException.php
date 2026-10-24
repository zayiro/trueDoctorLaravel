<?php

declare(strict_types=1);

namespace App\Services\Availability;

use RuntimeException;

/**
 * El turno que se intentó reservar ya no se puede reservar.
 */
final class SlotNotAvailableException extends RuntimeException
{
    public function __construct(string $message, public readonly ?SlotState $state = null)
    {
        parent::__construct($message);
    }

    public static function notOffered(): self
    {
        return new self('Ese horario no existe en la agenda del especialista.');
    }

    public static function forSlot(Slot $slot): self
    {
        $message = match ($slot->state) {
            SlotState::Past => 'Ese horario ya pasó.',
            SlotState::TooSoon => 'Ese horario requiere más anticipación. Elige uno posterior.',
            SlotState::Booked, SlotState::Full => 'Ese horario acaba de ser reservado por otra persona. Elige otro.',
            default => 'Ese horario ya no está disponible.',
        };

        return new self($message, $slot->state);
    }
}