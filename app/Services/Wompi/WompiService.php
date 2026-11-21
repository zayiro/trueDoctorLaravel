<?php

namespace App\Services\Wompi;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class WompiService
{
    protected string $publicKey;
    protected string $privateKey;
    protected string $checkoutUrl;
    protected string $redirectUrl;

    public function __construct()
    {
        $this->publicKey    = config('services.wompi.public_key');
        $this->privateKey   = config('services.wompi.private_key');
        $this->checkoutUrl  = config('services.wompi.checkout_url');
        $this->redirectUrl  = config('services.wompi.redirect_url');
    }

    /**
     * Genera la URL de checkout de Wompi y crea la suscripción en estado pending.
     */
    public function buildCheckoutUrl(int $doctorId, Plan $plan): array
    {
        $reference     = 'OD-' . $doctorId . '-' . Str::upper(Str::random(8)) . '-' . time();
        $amountInCents = (int) ($plan->price * 100);
        $currency      = 'COP';
        $integrity     = config('services.wompi.integrity_secret');

        $signature = hash('sha256', $reference . $amountInCents . $currency . $integrity);

        $params = http_build_query([
            'public-key'          => $this->publicKey,
            'currency'            => $currency,
            'amount-in-cents'     => $amountInCents,
            'reference'           => $reference,
            'redirect-url'        => $this->redirectUrl,
            'signature-integrity' => $signature,
        ]);

        $subscription = Subscription::create([
            'doctor_id'       => $doctorId,
            'plan_id'         => $plan->id,
            'wompi_reference' => $reference,
            'status'          => 'pending',
            'amount_in_cents' => $amountInCents,
            'currency'        => $currency,
            'starts_at'       => now(),
            'ends_at'         => now()->addDays(30),
        ]);

        return [
            'subscription' => $subscription,
            'checkout_url' => config('services.wompi.checkout_url') . '?' . $params,
        ];
    }

    /**
     * Consulta el estado de una transacción en Wompi por referencia.
     */
    public function getTransactionByReference(string $reference): ?array
    {
        $response = Http::withToken($this->privateKey)
            ->get("https://production.wompi.co/v1/transactions", [
                'reference' => $reference,
            ]);

        if ($response->successful()) {
            $data = $response->json('data');
            return $data[0] ?? null;
        }

        return null;
    }

    /**
     * Consulta una transacción en Wompi por su id y devuelve sus datos
     * (status, reference, amount_in_cents, ...). Lanza excepción si Wompi no responde bien.
     */
    public function validateTransaction(string $transactionId): array
    {
        $baseUrl = config('services.wompi.endpoint');

        $response = Http::timeout(15)
            ->withToken($this->publicKey)
            ->acceptJson()
            ->get("{$baseUrl}/transactions/{$transactionId}");

        if ($response->failed()) {
            throw new \RuntimeException(
                'Wompi respondió ' . $response->status() . ' al consultar la transacción ' . $transactionId
            );
        }

        return $response->json('data') ?? [];
    }

    /**
     * Valida la firma del webhook de Wompi.
     */
    public function validateWebhookSignature(array $payload, string $signature): bool
    {
        $properties = $payload['event'] . $payload['timestamp'];
        $computed    = hash_hmac('sha256', $properties, config('services.wompi.events_secret'));
        return hash_equals($computed, $signature);
    }

    /**
     * Genera la firma de integridad requerida por Wompi.
     */
    public function generateSignature(string $reference, int $amountInCents, string $currency): string
    {
        $integrity             = config('services.wompi.integrity_secret'); // clave de integridad, diferente a la privada
        $signatureIntegrity    = $reference . $amountInCents . $currency . $integrity;
        return hash('sha256', $signatureIntegrity);
    }
}
