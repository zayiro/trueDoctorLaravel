<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\PromoCode;
use App\Services\Appointments\PaymentLinkService;
use App\Services\Payments\AppointmentCheckout;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Página pública de pago de una cita creada por el doctor/clínica. La llave es el token del enlace (sin login).
 * Solo muestra lo mínimo para decidir pagar: especialista, servicio, fecha y valor. Nada del paciente.
 */
class PaymentLinkController extends Controller
{
    public function __construct(
        private readonly AppointmentCheckout $checkout,
        private readonly PaymentLinkService $links,
    ) {
    }

    public function show(string $token)
    {
        $appointment = $this->find($token);
        $state = $this->state($appointment);

        $quote = $state === 'open' ? $this->checkout->quote($appointment)->toArray() : null;

        return view('appointments.pay-link', [
            'appointment' => $appointment,
            'state'       => $state,
            'quote'       => $quote,
            'token'       => $token,
        ]);
    }

    /** Desglose con un código promocional (no guarda nada). */
    public function promo(Request $request, string $token)
    {
        $appointment = $this->find($token);
        abort_unless($this->state($appointment) === 'open', 410);

        $data = $request->validate(['code' => 'required|string|max:255']);

        $promo = PromoCode::forModule(PromoCode::MODULE_APPOINTMENT)->where('code', strtoupper(trim($data['code'])))->first();

        if (!$promo || !$promo->isValid($appointment->patient?->user_id)) {
            return response()->json(['message' => 'El código promocional no es válido, expiró o superó el límite de usos.'], 422);
        }

        $original = $this->checkout->quote($appointment);
        $quote = $this->checkout->quote($appointment, $promo);

        return response()->json([
            'success'   => true,
            'code'      => $promo->code,
            'quote'     => $quote->toArray(),
            'discount'  => ($original->totalCents - $quote->totalCents) / 100,
            'total'     => $quote->totalCents / 100,
        ]);
    }

    public function start(Request $request, string $token)
    {
        $appointment = $this->find($token);

        if ($this->state($appointment) !== 'open') {
            return redirect()->route('appointments.pay-link.show', $token);
        }

        $data = $request->validate(['promo_code' => 'nullable|string|max:255']);
        $payer = $appointment->patient?->user;

        $promo = null;
        if (!empty($data['promo_code'])) {
            $promo = PromoCode::forModule(PromoCode::MODULE_APPOINTMENT)->where('code', strtoupper(trim($data['promo_code'])))->first();

            if (!$promo || !$promo->isValid($payer?->id)) {
                return back()->with('error', 'El código promocional no es válido, expiró o superó el límite de usos.');
            }
        }

        try {
            $payment = $this->checkout->start($appointment, $payer, $promo);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('payment_link.start_failed', ['appointment_id' => $appointment->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'No pudimos iniciar el pago. Inténtalo de nuevo en unos segundos.');
        }

        if ($payment->method === \App\Enums\PaymentMethod::Free) {
            return redirect()->route('payments.return', $payment->reference);
        }

        return redirect()->away($this->checkout->checkoutUrl($payment, route('payments.return', $payment->reference)));
    }

    private function find(string $token): Appointment
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{32,64}$/', $token) === 1, 404);

        return Appointment::with(['doctor.user', 'clinic', 'service', 'address', 'patient.user'])
            ->where('payment_token', $token)
            ->firstOrFail();
    }

    /** open | paid | expired */
    private function state(Appointment $appointment): string
    {
        $payment = $appointment->payment_status instanceof \BackedEnum ? $appointment->payment_status->value : (string) $appointment->payment_status;

        if ($payment === 'paid') {
            return 'paid';
        }

        return $this->links->isOpen($appointment) ? 'open' : 'expired';
    }
}
