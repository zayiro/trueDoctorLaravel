<?php

declare(strict_types=1);

namespace App\Sms\Gateways;

use App\Sms\Contracts\SmsGateway;
use Illuminate\Support\Facades\Log;
use Throwable;
use Twilio\Rest\Client;

/** Twilio. Llaves: TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN, TWILIO_PHONE_NUMBER (config/services.php → twilio). */
final class TwilioSmsGateway implements SmsGateway
{
    private ?Client $client = null;

    public function isConfigured(): bool
    {
        return config('services.twilio.account_sid') && config('services.twilio.auth_token') && config('services.twilio.phone_number') ? true : false;
    }

    public function send(string $to, string $text): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            // El cliente se crea al enviar, no al arrancar: un .env incompleto ya no rompe nada.
            $this->client ??= new Client((string) config('services.twilio.account_sid'), (string) config('services.twilio.auth_token'));
            $this->client->messages->create($to, [
                'from' => config('services.twilio.phone_number'),
                'body' => $text,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error("Error enviando SMS a {$to}: " . $e->getMessage());

            return false;
        }
    }
}
