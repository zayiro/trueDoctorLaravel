<?php

declare(strict_types=1);

namespace App\Services\Appointments;

/** Resultado de una cancelación o de abandonar una reserva. `kind`: cancelled | released | noop | refused. */
final class CancellationResult
{
    private function __construct(
        public readonly bool $ok,
        public readonly string $message,
        public readonly string $kind,
    ) {
    }

    public static function success(string $message = '', string $kind = 'cancelled'): self
    {
        return new self(true, $message, $kind);
    }

    public static function failure(string $message, string $kind = 'refused'): self
    {
        return new self(false, $message, $kind);
    }
}
