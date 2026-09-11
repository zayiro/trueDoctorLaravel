<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $twilio;
    protected $phoneNumber;

    public function __construct()
    {
        $this->twilio = new Client(
            config('services.twilio.account_sid'),
            config('services.twilio.auth_token')
        );
        $this->phoneNumber = config('services.twilio.phone_number');
    }

    /**
     * Enviar SMS inmediatamente
     *
     * @param string $to Número de teléfono del destinatario
     * @param string $message Mensaje a enviar
     * @return bool
     */
    public function send(string $to, string $message): bool
    {
        try {
            $this->twilio->messages->create($to, [
                'from' => $this->phoneNumber,
                'body' => $message
            ]);

            Log::info("SMS enviado a {$to}");
            return true;
        } catch (\Exception $e) {
            Log::error("Error enviando SMS a {$to}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar SMS con retry automático
     *
     * @param string $to Número de teléfono
     * @param string $message Mensaje
     * @param int $maxRetries Máximo número de intentos
     * @return bool
     */
    public function sendWithRetry(string $to, string $message, int $maxRetries = 3): bool
    {
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            if ($this->send($to, $message)) {
                return true;
            }
            if ($attempt < $maxRetries) {
                sleep(2 ** $attempt); // Exponential backoff: 2s, 4s, 8s
            }
        }
        return false;
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