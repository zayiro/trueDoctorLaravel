<?php

namespace App\Http\Controllers;

use App\Enums\PaymentTransactionStatus;
use App\Models\Payment;
use App\Services\Plans\PlanRules;
use Illuminate\Support\Facades\Auth;

/**
 * Pantalla a la que PaymentReturnController envía al médico después de pagar un plan.
 * Muestra solo el resultado (aprobado / rechazado / en proceso); el plan ya lo activó el listener del pago.
 */
class PlanPaymentResultController extends Controller
{
    public function show(Payment $payment)
    {
        $status = match ($payment->status) {
            PaymentTransactionStatus::Approved => 'approved',
            PaymentTransactionStatus::Declined,
            PaymentTransactionStatus::Voided,
            PaymentTransactionStatus::Error    => 'declined',
            default                            => 'pending', // pendiente, expirado o en revisión
        };

        $user = Auth::user();
        $isDoctor = $user && $user->role === 'doctor';

        return response()
            ->view('plans.payment.result', [
                'status'    => $status,
                'homeUrl'   => $user ? app(PlanRules::class)->homeFor($user) : route('home'),
                'homeLabel' => $isDoctor ? 'Ir a mi perfil' : 'Continuar',
            ])
            ->withHeaders([
                'X-Robots-Tag'    => 'noindex, nofollow, noarchive',
                'Cache-Control'   => 'no-store, private',
                'Referrer-Policy' => 'no-referrer',
            ]);
    }
}
