<?php

namespace App\Http\Controllers;

use App\Payments\Exceptions\InvalidWebhookSignature;
use App\Payments\PaymentGatewayManager;
use App\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * POST /webhooks/payments/{provider}
 *
 * Responde 401 solo si la firma es inválida. Cualquier otro fallo propaga el error (500) para que
 * el proveedor reintente el evento.
 */
class PaymentWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $provider,
        PaymentGatewayManager $gateways,
        PaymentService $payments,
    ): JsonResponse {
        try {
            $gateway = $gateways->driver($provider);
        } catch (InvalidArgumentException) {
            return response()->json(['error' => 'Unknown provider'], 404);
        }

        try {
            $transaction = $gateway->parseWebhook($request->all(), $request->header('X-Event-Checksum'));
        } catch (InvalidWebhookSignature $e) {
            // Sin datos del cuerpo ni secretos en el log.
            Log::warning('payments.webhook.invalid_signature', [
                'provider' => $provider,
                'ip'       => $request->ip(),
                'reason'   => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        if ($transaction !== null) {
            $payments->handleTransaction($transaction);
        }

        return response()->json(['ok' => true]);
    }
}
