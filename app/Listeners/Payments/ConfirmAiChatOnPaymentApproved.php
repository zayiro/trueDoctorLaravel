<?php

declare(strict_types=1);

namespace App\Listeners\Payments;

use App\Enums\PaymentKind;
use App\Mail\AiChatPurchased;
use App\Models\AiChatSession;
use App\Payments\Events\PaymentApproved;
use App\Services\AiChat\AiChatSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Un pago del Chat de Salud con IA quedó aprobado → la sesión pasa de "pago_pendiente" a "pendiente",
 * arranca su plazo de vigencia y se envía el correo con el enlace.
 * Es idempotente: el cambio de estado es atómico y solo el primero avanza.
 */
class ConfirmAiChatOnPaymentApproved
{
    public function handle(PaymentApproved $event): void
    {
        $payment = $event->payment;

        if ($payment->kind !== PaymentKind::AiChat) {
            return;
        }

        $days = max(1, AiChatSettings::int('ai_chat_expiry_days'));

        $updated = AiChatSession::whereKey($payment->payable_id)
            ->where('status', AiChatSession::STATUS_AWAITING_PAYMENT)
            ->update([
                'status' => AiChatSession::STATUS_PENDING,
                'payment_id' => $payment->id,
                'paid_at' => now(),
                'expires_at' => now()->addDays($days),
                'updated_at' => now(),
            ]);

        if (! $updated) {
            $session = AiChatSession::find($payment->payable_id);

            // Pago aprobado de una sesión que ya no espera pago (y no es este mismo pago): se atiende a mano.
            if (! $session || (int) $session->payment_id !== (int) $payment->id) {
                Log::critical('payments.ai_chat_approved_unexpected_state', [
                    'payment' => $payment->reference,
                    'session_id' => $payment->payable_id,
                    'status' => $session?->status,
                ]);
            }

            return;
        }

        $session = AiChatSession::with('user')->find($payment->payable_id);

        if (! $session || ! $session->user?->email) {
            Log::critical('payments.ai_chat_paid_without_email', ['payment' => $payment->reference, 'session_id' => $payment->payable_id]);

            return;
        }

        try {
            Mail::to($session->user->email)->queue(new AiChatPurchased($session));
        } catch (Throwable $e) {
            // Un fallo del correo no debe romper el flujo: la sesión ya quedó pagada y visible en "Mis chats".
            Log::warning('payments.ai_chat_mail_failed', ['session_id' => $session->id, 'message' => $e->getMessage()]);
        }
    }
}
