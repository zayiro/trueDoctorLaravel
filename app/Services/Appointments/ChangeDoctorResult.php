<?php

declare(strict_types=1);

namespace App\Services\Appointments;

/**
 * Resultado de intentar reasignar una cita a otro médico de la clínica.
 */
final class ChangeDoctorResult
{
    private function __construct(
        public readonly bool $ok,
        public readonly string $message,
    ) {
    }

    public static function success(string $message): self
    {
        return new self(true, $message);
    }

    public static function failure(string $message): self
    {
        return new self(false, $message);
    }
}
