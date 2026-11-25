<?php

declare(strict_types=1);

namespace App\Sms\Contracts;

/**
 * Un proveedor de SMS. Para agregar uno nuevo: una clase que implemente esto y una línea en SmsManager::REGISTRY.
 * Nunca debe lanzar excepciones hacia afuera: si falla, devuelve false.
 */
interface SmsGateway
{
    /** ¿Están las llaves de este proveedor en el .env? (no consulta a la red) */
    public function isConfigured(): bool;

    /** @param string $to número en formato internacional (+57...) */
    public function send(string $to, string $text): bool;
}
