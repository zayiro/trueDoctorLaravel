<?php

namespace App\Services;

use App\Sms\SmsManager;
use Illuminate\Support\Facades\Log;

/**
 * Punto único de envío de SMS del sistema. Los métodos públicos son los de siempre; por dentro el envío lo hace el proveedor
 * elegido en Administración → Configuración (ver App\Sms\SmsManager). Quien la usa no sabe qué proveedor hay detrás.
 */
class SmsService
{
    /**
     * Enviar SMS inmediatamente.
     *
     * @param string $to Número de teléfono del destinatario (formato internacional)
     * @param string $message Mensaje a enviar
     * @return object|null Un objeto (con el proveedor que lo envió) si se envió, o null si falló en todos los proveedores
     */
    public function send(string $to, string $message)
    {
        $provider = app(SmsManager::class)->send($to, $message);

        if ($provider === null) {
            Log::error("Error enviando SMS a {$to}: ningún proveedor lo pudo enviar");

            return null;
        }

        return (object) ['provider' => $provider];
    }

    /**
     * Enviar SMS con retry automático
     *
     * @param string $to Número de teléfono
     * @param string $message Mensaje
     * @param int $maxRetries Máximo número de intentos
     * @return object|null Retorna el resultado si se envió correctamente, o null después de todos los intentos
     */
    public function sendWithRetry(string $to, string $message, int $maxRetries = 3)
    {
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $result = $this->send($to, $message);
            if ($result) {
                return $result;
            }
            if ($attempt < $maxRetries) {
                sleep(2 ** $attempt);
            }
        }

        return null;
    }

    /**
     * Validar formato de número de teléfono colombiano
     *
     * @param string $phoneNumber Número a validar
     * @return bool
     */
    public function isValidColombianNumber(string $phoneNumber): bool
    {
        // Acepta formatos: +573001234567, 573001234567, 3001234567
        return preg_match('/^(\+57|57)?3\d{9}$/', str_replace([' ', '-', '(', ')'], '', $phoneNumber));
    }

    /**
     * Normalizar número de teléfono al formato +57...
     *
     * @param string $phoneNumber Número a normalizar
     * @return string|null Número normalizado o null si es inválido
     */
    public function normalizePhoneNumber(string $phoneNumber): ?string
    {
        $cleaned = str_replace([' ', '-', '(', ')'], '', $phoneNumber);

        if (!$this->isValidColombianNumber($cleaned)) {
            return null;
        }

        // Remover +57 o 57 si existen
        $cleaned = preg_replace('/^(\+57|57)/', '', $cleaned);

        return '+57' . $cleaned;
    }
}
