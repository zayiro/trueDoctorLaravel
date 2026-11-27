<?php

namespace App\Services\Certificates;

use RuntimeException;

/**
 * Error esperado al emitir/anular un certificado. El mensaje ya está redactado
 * para mostrárselo tal cual al doctor; $reason es un código corto para la lógica.
 */
class CertificateException extends RuntimeException
{
    public function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }
}
