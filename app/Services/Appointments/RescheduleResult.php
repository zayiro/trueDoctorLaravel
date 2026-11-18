<?php

declare(strict_types=1);

namespace App\Services\Appointments;

/**
 * Resultado de reprogramar una cita. El controlador solo decide cómo mostrarlo (redirect, JSON...).
 */
final class RescheduleResult
{
    private function __construct(
        public readonly bool $ok,
        public readonly string $message,
        public readonly bool $pendingApproval = false,
    ) {
    }

    public static function success(string $message, bool $pendingApproval = false): self
    {
        return new self(true, $message, $pendingApproval);
    }

    public static function failure(string $message): self
    {
        return new self(false, $message);
    }
}
