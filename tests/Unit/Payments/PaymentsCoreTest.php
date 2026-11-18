<?php

namespace Tests\Unit\Payments;

use App\Enums\PaymentTransactionStatus;
use App\Payments\Exceptions\InvalidWebhookSignature;
use App\Payments\Gateways\WompiGateway;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas puras (sin base de datos ni red) del gateway de Wompi y de las transiciones de estado.
 */
class PaymentsCoreTest extends TestCase
{
    private const SECRET = 'test_events_secret';

    private function gateway(string $secret = self::SECRET): WompiGateway
    {
        return new WompiGateway(['events_secret' => $secret]);
    }

    /** @return array<string, mixed> */
    private function event(string $status = 'APPROVED', int $amount = 1500000, string $name = 'transaction.updated', ?string $secret = self::SECRET): array
    {
        $timestamp = 1730000000;
        $checksum  = hash('sha256', '12345-abc' . $status . $amount . $timestamp . $secret);

        return [
            'event' => $name,
            'data'  => ['transaction' => [
                'id' => '12345-abc', 'reference' => 'PAY-XYZ', 'status' => $status,
                'amount_in_cents' => $amount, 'currency' => 'COP',
            ]],
            'timestamp' => $timestamp,
            'signature' => [
                'properties' => ['transaction.id', 'transaction.status', 'transaction.amount_in_cents'],
                'checksum'   => $checksum,
            ],
        ];
    }

    public function test_valid_event_is_accepted_and_normalized(): void
    {
        $tx = $this->gateway()->parseWebhook($this->event());

        $this->assertNotNull($tx);
        $this->assertSame('PAY-XYZ', $tx->reference);
        $this->assertSame(PaymentTransactionStatus::Approved, $tx->status);
        $this->assertSame(1500000, $tx->amountInCents);
    }

    public function test_status_mapping(): void
    {
        $this->assertSame(PaymentTransactionStatus::Declined, $this->gateway()->parseWebhook($this->event('DECLINED'))->status);
        $this->assertSame(PaymentTransactionStatus::Voided, $this->gateway()->parseWebhook($this->event('VOIDED'))->status);
        $this->assertSame(PaymentTransactionStatus::Error, $this->gateway()->parseWebhook($this->event('ERROR'))->status);
        $this->assertSame(PaymentTransactionStatus::Pending, $this->gateway()->parseWebhook($this->event('PENDING'))->status);
    }

    public function test_wrong_secret_is_rejected(): void
    {
        $this->expectException(InvalidWebhookSignature::class);
        $this->gateway('otro_secreto')->parseWebhook($this->event());
    }

    public function test_tampered_amount_is_rejected(): void
    {
        $event = $this->event();
        $event['data']['transaction']['amount_in_cents'] = 100;

        $this->expectException(InvalidWebhookSignature::class);
        $this->gateway()->parseWebhook($event);
    }

    public function test_event_without_signature_is_rejected(): void
    {
        $event = $this->event();
        unset($event['signature']);

        $this->expectException(InvalidWebhookSignature::class);
        $this->gateway()->parseWebhook($event);
    }

    public function test_missing_events_secret_is_rejected(): void
    {
        $this->expectException(InvalidWebhookSignature::class);
        (new WompiGateway([]))->parseWebhook($this->event());
    }

    public function test_header_checksum_is_used_when_body_has_none(): void
    {
        $event    = $this->event();
        $checksum = $event['signature']['checksum'];
        unset($event['signature']['checksum']);

        $this->assertNotNull($this->gateway()->parseWebhook($event, $checksum));
    }

    public function test_valid_event_of_another_type_is_ignored(): void
    {
        $this->assertNull($this->gateway()->parseWebhook($this->event(name: 'nequi_token.updated')));
    }

    public function test_transition_rules(): void
    {
        $this->assertTrue(PaymentTransactionStatus::Declined->canTransitionTo(PaymentTransactionStatus::Approved));
        $this->assertTrue(PaymentTransactionStatus::Pending->canTransitionTo(PaymentTransactionStatus::Approved));
        $this->assertTrue(PaymentTransactionStatus::Approved->canTransitionTo(PaymentTransactionStatus::Voided));
        $this->assertFalse(PaymentTransactionStatus::Approved->canTransitionTo(PaymentTransactionStatus::Declined));
        $this->assertFalse(PaymentTransactionStatus::Voided->canTransitionTo(PaymentTransactionStatus::Approved));
        $this->assertFalse(PaymentTransactionStatus::Review->canTransitionTo(PaymentTransactionStatus::Approved));
    }
}
