<?php

declare(strict_types=1);

namespace App\Services\Availability;

use DateTimeImmutable;

/**
 * Un turno concreto de un doctor en una sede, con su estado.
 */
final class Slot
{
    public function __construct(
        public readonly DateTimeImmutable $start,
        public readonly DateTimeImmutable $end,
        public readonly int $doctorId,
        public readonly int $addressId,
        public readonly SlotState $state,
        public readonly ?string $reason = null,
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->state->isBookable();
    }
}