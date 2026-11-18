<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Enums\PaymentKind;
use App\Enums\PaymentTransactionStatus;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Payment;
use App\Models\PromoCode;
use App\Models\Setting;
use App\Models\User;
use App\Payments\PaymentService;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Cobro de citas sobre la capa de pagos.
 *
 * Reglas de dinero (en centavos, sin decimales flotantes):
 *   - El doctor/clínica recibe SIEMPRE el precio completo de la cita.
 *   - El paciente paga: precio − descuento + comisión de la plataforma.
 *   - El código promocional lo asume OpenDoctor (no sale del precio del doctor).
 *   - Si el código cubre todo el precio, el paciente no paga nada (ni siquiera la comisión) y la cita se confirma sin cobro.
 *   - Margen de OpenDoctor = comisión − descuento − fee estimado de la pasarela.
 */
final class AppointmentCheckout
{
    public function __construct(private readonly PaymentService $payments)
    {
    }

    /**
     * Desglose de lo que se cobraría hoy, con o sin código promocional. No escribe nada.
     */
    public function quote(Appointment $appointment, ?PromoCode $promo = null): AppointmentQuote
    {
        $isClinic = $appointment->clinic_id !== null;

        $commissionRate = (float) (Setting::where('key', $isClinic ? 'virtual_commission_clinic' : 'virtual_commission_doctor')->value('value') ?? 15);
        $feeRate = (float) (Setting::where('key', 'wompi_fee')->value('value') ?? 2.9);

        $baseCents = (int) round(((float) $appointment->price) * 100);
        $commissionCents = (int) round($baseCents * $commissionRate / 100);

        $discountCents = 0;

        if ($promo !== null) {
            $reward = (float) $promo->reward;

            // El descuento se calcula sobre el precio del doctor, en pesos enteros (igual que antes).
            $discountPesos = $promo->type === 'percent'
                ? floor(((float) $appointment->price) * $reward / 100)
                : floor(min($reward, (float) $appointment->price));

            $discountCents = (int) round($discountPesos * 100);

            // Cubre todo el precio: el paciente no paga nada, tampoco la comisión.
            if ($discountCents >= $baseCents) {
                $discountCents = $baseCents + $commissionCents;
            }
        }

        $totalCents = max(0, $baseCents - $discountCents + $commissionCents);

        return new AppointmentQuote(
            baseCents: $baseCents,
            commissionCents: $commissionCents,
            discountCents: $discountCents,
            totalCents: $totalCents,
            commissionRate: $commissionRate,
            feeRate: $feeRate,
            isClinic: $isClinic,
            promoCode: $promo?->code,
            promoId: $promo?->id,
        );
    }

    /**
     * Crea un intento de cobro nuevo (cada intento es un Payment distinto) y devuelve el pago.
     * Si el total es 0 (código del 100 %), lo registra aprobado sin cobro y la cita se confirma sola.
     *
     * @throws DomainException si la cita ya no se puede pagar.
     */
    public function start(Appointment $appointment, ?User $payer, ?PromoCode $promo = null): Payment
    {
        return DB::transaction(function () use ($appointment, $payer, $promo) {
            $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            $this->assertPayable($locked);

            if ($promo !== null && !$promo->isRedeemable()) {
                throw new DomainException('El código promocional ya no está disponible.');
            }

            $quote = $this->quote($locked, $promo);
            $payee = $locked->clinic_id ? Clinic::find($locked->clinic_id) : Doctor::find($locked->doctor_id);

            // Intentos anteriores sin resultado: ya no se van a usar.
            Payment::where('payable_type', $locked->getMorphClass())
                ->where('payable_id', $locked->id)
                ->where('kind', PaymentKind::Appointment->value)
                ->where('status', PaymentTransactionStatus::Pending->value)
                ->get()
                ->each(fn (Payment $old) => $this->payments->markExpired($old));

            // Se guarda el desglose en la cita (lo leen el desembolso, los recibos y las pantallas existentes).
            $locked->update([
                'commission_amount' => $quote->commissionCents / 100,
                'doctor_amount'     => $quote->baseCents / 100,
                'platform_amount'   => $quote->platformMarginCents() / 100,
                'promo_code'        => $quote->promoCode,
                'discount_amount'   => $quote->discountCents / 100,
            ]);

            if ($quote->totalCents === 0) {
                return $this->payments->settleWithoutCharge(
                    $locked, $payer, PaymentKind::Appointment,
                    $quote->baseCents, $quote->commissionCents, $quote->discountCents,
                    $quote->promoId, $payee,
                );
            }

            $payment = $this->payments->start(
                payable: $locked,
                payer: $payer,
                kind: PaymentKind::Appointment,
                baseInCents: $quote->baseCents,
                commissionInCents: $quote->commissionCents,
                discountInCents: $quote->discountCents,
                providerFeeInCents: $quote->estimatedFeeCents(),
                expiresAt: $locked->payment_expires_at,
                promoCodeId: $quote->promoId,
                payee: $payee,
            );

            // Referencia heredada: la usan reportes y pantallas anteriores.
            $locked->update(['wompi_reference' => $payment->reference]);

            return $payment;
        });
    }

    /** URL de la pasarela para un pago creado con start(). */
    public function checkoutUrl(Payment $payment, string $redirectUrl): string
    {
        return $this->payments->checkoutUrl($payment, $redirectUrl);
    }

    /** ¿Esta cita se paga en línea? (virtual siempre; presencial si el doctor/clínica acepta pagos en línea). */
    public static function requiresOnlinePayment(Appointment $appointment): bool
    {
        $isVirtual = ($appointment->service?->type === 'virtual') || ($appointment->address?->type === 'virtual');

        if ($isVirtual) {
            return true;
        }

        $settings = $appointment->clinic_id
            ? $appointment->clinic?->settings
            : $appointment->doctor?->settings;

        return (bool) ($settings->accepts_online_payments ?? false);
    }

    private function assertPayable(Appointment $appointment): void
    {
        $status = $appointment->status instanceof \BackedEnum ? $appointment->status->value : (string) $appointment->status;
        $payment = $appointment->payment_status instanceof \BackedEnum ? $appointment->payment_status->value : (string) $appointment->payment_status;

        if ($payment === 'paid') {
            throw new DomainException('Esta cita ya está pagada.');
        }

        if (!in_array($status, ['pending', 'confirmed'], true)) {
            throw new DomainException('Esta reserva ya no está disponible. Agenda una nueva cita.');
        }

        if ($appointment->payment_expires_at !== null && $appointment->payment_expires_at->isPast()) {
            throw new DomainException('El tiempo para pagar esta reserva terminó y el turno fue liberado.');
        }
    }
}
