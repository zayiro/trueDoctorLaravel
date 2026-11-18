<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Enums\PaymentTransactionStatus;
use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\DTO\CheckoutIntent;
use App\Payments\DTO\GatewayTransaction;
use App\Payments\Exceptions\InvalidWebhookSignature;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Wompi (Colombia).
 *
 * Recibe la configuración como arreglo (config('services.wompi')) para poder probarlo sin Laravel.
 * Claves esperadas: public_key, private_key, integrity_secret, events_secret, checkout_url, redirect_url, endpoint.
 */
final class WompiGateway implements PaymentGateway
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config)
    {
    }

    public function name(): string
    {
        return 'wompi';
    }

    public function checkout(Payment $payment, ?string $redirectUrl = null): CheckoutIntent
    {
        $amount    = (int) $payment->total_in_cents;
        $currency  = (string) $payment->currency;
        $signature = hash('sha256', $payment->reference . $amount . $currency . (string) ($this->config['integrity_secret'] ?? ''));

        // Mismo parámetro de firma que ya usa el checkout de citas en producción.
        $params = http_build_query([
            'public-key'          => $this->config['public_key'] ?? '',
            'currency'            => $currency,
            'amount-in-cents'     => $amount,
            'reference'           => $payment->reference,
            'redirect-url'        => $redirectUrl ?? ($this->config['redirect_url'] ?? ''),
            'signature:integrity' => $signature, // nombre que documenta Wompi para el checkout web
            'signature-integrity' => $signature, // nombre histórico del sitio (Wompi ignora el que no use)
        ]);

        return new CheckoutIntent(
            url: rtrim((string) ($this->config['checkout_url'] ?? ''), '/') . '/?' . $params,
            reference: $payment->reference,
        );
    }

    public function fetchTransaction(string $transactionId): ?GatewayTransaction
    {
        $response = Http::timeout(15)
            ->withToken((string) ($this->config['public_key'] ?? ''))
            ->acceptJson()
            ->get($this->endpoint() . '/transactions/' . rawurlencode($transactionId));

        if ($response->status() === 404) {
            return null;
        }

        if ($response->failed()) {
            throw new RuntimeException('Wompi respondió ' . $response->status() . ' al consultar la transacción ' . $transactionId);
        }

        $data = $response->json('data');

        return is_array($data) && $data !== [] ? $this->toTransaction($data) : null;
    }

    public function fetchByReference(string $reference): ?GatewayTransaction
    {
        $response = Http::timeout(15)
            ->withToken((string) ($this->config['private_key'] ?? ''))
            ->acceptJson()
            ->get($this->endpoint() . '/transactions', ['reference' => $reference]);

        if ($response->failed()) {
            throw new RuntimeException('Wompi respondió ' . $response->status() . ' al consultar la referencia ' . $reference);
        }

        $items = $response->json('data');

        if (!is_array($items) || $items === []) {
            return null;
        }

        // Una referencia puede tener varios intentos: si alguno fue aprobado, ese manda.
        $chosen = null;

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (strtoupper((string) ($item['status'] ?? '')) === 'APPROVED') {
                $chosen = $item;
                break;
            }

            if ($chosen === null || strcmp((string) ($item['created_at'] ?? ''), (string) ($chosen['created_at'] ?? '')) > 0) {
                $chosen = $item;
            }
        }

        return $chosen !== null ? $this->toTransaction($chosen) : null;
    }

    /**
     * Valida el checksum del evento según la documentación de Wompi:
     * SHA-256 de los valores de `signature.properties` (leídos de `data`), seguidos del `timestamp`
     * y del secreto de eventos. Se compara con `signature.checksum` (o el header X-Event-Checksum).
     */
    public function parseWebhook(array $payload, ?string $headerChecksum = null): ?GatewayTransaction
    {
        $secret = (string) ($this->config['events_secret'] ?? '');

        if ($secret === '') {
            throw new InvalidWebhookSignature('El secreto de eventos de Wompi no está configurado.');
        }

        $properties = $payload['signature']['properties'] ?? null;
        $timestamp  = $payload['timestamp'] ?? null;
        $received   = $payload['signature']['checksum'] ?? $headerChecksum;

        if (!is_array($properties) || $properties === [] || $timestamp === null || $received === null || $received === '') {
            throw new InvalidWebhookSignature('El evento no trae firma.');
        }

        $data      = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $joined    = '';

        foreach ($properties as $path) {
            $value  = data_get($data, (string) $path);
            $joined .= is_scalar($value) ? (string) $value : '';
        }

        $expected = hash('sha256', $joined . $timestamp . $secret);

        if (!hash_equals($expected, strtolower((string) $received))) {
            throw new InvalidWebhookSignature('La firma del evento no coincide.');
        }

        if (($payload['event'] ?? '') !== 'transaction.updated') {
            return null;
        }

        $transaction = $payload['data']['transaction'] ?? null;

        if (!is_array($transaction) || empty($transaction['reference'])) {
            return null;
        }

        return $this->toTransaction($transaction);
    }

    /** @param array<string, mixed> $transaction */
    private function toTransaction(array $transaction): GatewayTransaction
    {
        return new GatewayTransaction(
            id: (string) ($transaction['id'] ?? ''),
            reference: (string) ($transaction['reference'] ?? ''),
            status: $this->mapStatus((string) ($transaction['status'] ?? '')),
            amountInCents: (int) ($transaction['amount_in_cents'] ?? 0),
            currency: (string) ($transaction['currency'] ?? 'COP'),
            raw: $transaction,
        );
    }

    private function mapStatus(string $status): PaymentTransactionStatus
    {
        return match (strtoupper($status)) {
            'APPROVED' => PaymentTransactionStatus::Approved,
            'DECLINED' => PaymentTransactionStatus::Declined,
            'VOIDED'   => PaymentTransactionStatus::Voided,
            'ERROR'    => PaymentTransactionStatus::Error,
            default    => PaymentTransactionStatus::Pending,
        };
    }

    private function endpoint(): string
    {
        return rtrim((string) ($this->config['endpoint'] ?? 'https://production.wompi.co/v1'), '/');
    }
}
