<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Payments\PaymentGatewayManager;
use App\Payments\PaymentService;
use Illuminate\Http\Request;

/**
 * Wompi tiene configurada UNA sola URL de eventos (/webhooks/wompi). Este puente reparte:
 *   - referencia que existe en `payments`  → capa de pagos nueva (idempotente, firma verificada);
 *   - cualquier otra (planes, cobros anteriores a la capa nueva) → el webhook anterior, sin cambios.
 * Cuando todo viva en la capa nueva, la ruta apunta directo a PaymentWebhookController y este archivo se borra.
 */
class WompiWebhookBridgeController extends Controller
{
    public function __invoke(Request $request, PaymentGatewayManager $gateways, PaymentService $payments)
    {
        $reference = (string) data_get($request->all(), 'data.transaction.reference', '');

        if ($reference !== '' && Payment::where('reference', $reference)->exists()) {
            return app(PaymentWebhookController::class)($request, 'wompi', $gateways, $payments);
        }

        return app(SubscriptionController::class)->webhook($request);
    }
}
