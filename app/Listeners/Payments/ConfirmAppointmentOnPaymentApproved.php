<?php

declare(strict_types=1);

namespace App\Listeners\Payments;

use App\Enums\PaymentKind;
use App\Jobs\FinalizeAppointmentJob;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\DoctorPayout;
use App\Models\Payment;
use App\Models\PromoCode;
use App\Payments\Events\PaymentApproved;
use App\Services\AppointmentEventSourcing;
use App\Services\AppointmentStateManager;
use App\Services\Availability\AvailabilityQuery;
use App\Services\Availability\AvailabilityResolver;
use App\Services\Availability\SlotNotAvailableException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Un pago de cita quedó aprobado → la cita se confirma, se crea el desembolso al doctor y se consume el código.
 *
 * Es idempotente: el evento solo sale una vez por pago (PaymentService), y aun si se repitiera, cada paso
 * revisa antes de actuar (cita ya pagada, desembolso ya creado).
 *
 * Corre en el proceso del webhook (no en cola) para que la cita quede confirmada antes de responder a la pasarela
 * y antes de que el paciente vuelva al sitio. Lo pesado (Zoom, correos, SMS) sale en un job aparte.
 */
class ConfirmAppointmentOnPaymentApproved
{
    public function __construct(
        private readonly AppointmentStateManager $states,
        private readonly AvailabilityResolver $availability,
    ) {
    }

    public function handle(PaymentApproved $event): void
    {
        $payment = $event->payment;

        if ($payment->kind !== PaymentKind::Appointment) {
            return;
        }

        $appointmentId = DB::transaction(function () use ($payment) {
            $appointment = Appointment::whereKey($payment->payable_id)->lockForUpdate()->first();

            if (!$appointment) {
                Log::error('payments.appointment_missing', ['payment' => $payment->reference]);

                return null;
            }

            $status = $this->raw($appointment->status);

            // Ya estaba pagada (evento repetido): solo se asegura el desembolso.
            if ($this->raw($appointment->payment_status) === 'paid') {
                $this->ensurePayout($appointment, $payment);

                return $appointment->id;
            }

            if (in_array($status, ['expired', 'cancelled'], true)) {
                // Solo una reserva que VENCIÓ se intenta recuperar; una cancelada a propósito no se revive.
                $this->handleLatePayment($appointment, $payment, $status === 'expired');
                $this->ensurePayout($appointment, $payment);
                $this->consumePromo($payment);

                return null;
            }

            $paidFields = [
                'payment_status'     => 'paid',
                'paid_at'            => $payment->approved_at ?? now(),
                'payment_expires_at' => null,
                'wompi_reference'    => $payment->reference,
            ];

            if ($status === 'pending') {
                $this->states->transitionTo($appointment, 'confirmed', 'Pago aprobado', $paidFields, 'system');
            } else {
                // Citas anteriores a la capa de pagos: ya estaban confirmadas con el pago pendiente.
                $appointment->update($paidFields);
            }

            $this->ensurePayout($appointment->refresh(), $payment);
            $this->consumePromo($payment);

            return $appointment->id;
        });

        if ($appointmentId) {
            // Se lanza después del commit (la transacción exterior del webhook puede seguir abierta).
            FinalizeAppointmentJob::dispatch($appointmentId)->afterCommit();

            try {
                $appointment = Appointment::find($appointmentId);
                if ($appointment) {
                    AppointmentEventSourcing::recordPaymentProcessed($appointment, [
                        'amount'         => $payment->total_in_cents / 100,
                        'currency'       => $payment->currency,
                        'method'         => $payment->provider,
                        'transaction_id' => $payment->provider_transaction_id,
                    ]);
                }
            } catch (Throwable $e) {
                Log::warning('payments.audit_failed', ['payment' => $payment->reference, 'error' => $e->getMessage()]);
            }
        }
    }

    /** Desembolso al doctor/clínica: uno por cita, con el precio completo (el descuento lo asume OpenDoctor). */
    private function ensurePayout(Appointment $appointment, Payment $payment): void
    {
        // El dinero de un pago en sede nunca pasa por OpenDoctor: no hay nada que liquidar.
        if ($payment->method === \App\Enums\PaymentMethod::OnSite) {
            return;
        }

        $payable = $appointment->clinic_id ? Clinic::find($appointment->clinic_id) : Doctor::find($appointment->doctor_id);

        if (!$payable) {
            Log::error('payments.payout_without_payable', ['appointment_id' => $appointment->id]);

            return;
        }

        $total = $payment->total_in_cents / 100;
        $commission = $payment->commission_in_cents / 100;
        $discount = $payment->discount_in_cents / 100;
        // Código creado por el doctor/clínica: el descuento sale de lo que recibe, no de la comisión.
        $ownerBears = (($payment->payload['discount_borne_by'] ?? 'platform') === 'owner');
        $fee = $payment->provider_fee_in_cents > 0
            ? $payment->provider_fee_in_cents / 100
            : 0.0;

        DoctorPayout::firstOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'payment_id'          => $payment->id,
                'payable_id'          => $payable->id,
                'payable_type'        => get_class($payable),
                'total_charged'       => $total,
                'wompi_fee'           => $fee,
                'platform_commission' => $commission - ($ownerBears ? 0 : $discount), // puede ser negativo con códigos del 100 %
                'amount_to_pay'       => ($payment->base_amount_in_cents - ($ownerBears ? $payment->discount_in_cents : 0)) / 100,
                'status'              => 'pending',
                'due_date'            => now()->addDays(3),
            ]
        );
    }

    private function consumePromo(Payment $payment): void
    {
        if (!$payment->promo_code_id) {
            return;
        }

        $consumed = PromoCode::whereKey($payment->promo_code_id)
            ->where(fn ($q) => $q->whereNull('max_uses')->orWhereColumn('uses', '<', 'max_uses'))
            ->increment('uses');

        if (!$consumed) {
            // El pago ya se cobró: la cita se respeta. Solo se deja constancia.
            Log::warning('payments.promo_limit_reached_after_payment', [
                'payment' => $payment->reference,
                'promo_id' => $payment->promo_code_id,
            ]);
        }
    }

    /**
     * El pago llegó cuando la reserva ya había expirado. Si el turno sigue libre se recupera la cita;
     * si no, el dinero queda recibido y se avisa a soporte para reprogramar o reembolsar.
     */
    private function handleLatePayment(Appointment $appointment, Payment $payment, bool $tryRevive): void
    {
        $paidFields = [
            'payment_status'     => 'paid',
            'paid_at'            => $payment->approved_at ?? now(),
            'payment_expires_at' => null,
            'wompi_reference'    => $payment->reference,
        ];

        try {
            if (!$tryRevive) {
                throw SlotNotAvailableException::notOffered();
            }

            $startAt = Carbon::parse($appointment->date->format('Y-m-d') . ' ' . $appointment->start_time, config('booking.timezone', 'America/Bogota'));

            $query = new AvailabilityQuery(
                doctorIds: [(int) $appointment->doctor_id],
                clinicId: $appointment->clinic_id ? (int) $appointment->clinic_id : null,
                addressId: $appointment->address_id ? (int) $appointment->address_id : null,
                serviceId: (int) $appointment->service_id,
                excludeAppointmentId: (int) $appointment->id,
                staffBooking: true,
            );

            $this->availability->withBookableSlot($query, $startAt, function () use ($appointment, $paidFields) {
                $appointment->update($paidFields + ['status' => 'confirmed']);
            });

            DB::afterCommit(fn () => FinalizeAppointmentJob::dispatch($appointment->id));

            Log::notice('payments.late_payment_recovered', ['appointment_id' => $appointment->id, 'payment' => $payment->reference]);

            return;
        } catch (SlotNotAvailableException) {
            // sigue abajo
        } catch (Throwable $e) {
            Log::error('payments.late_payment_error', ['appointment_id' => $appointment->id, 'error' => $e->getMessage()]);
        }

        // No se pudo recuperar el turno: se registra el pago y se avisa.
        $appointment->update($paidFields);

        Log::critical('payments.paid_but_slot_unavailable', [
            'appointment_id' => $appointment->id,
            'reference'      => $appointment->reference,
            'payment'        => $payment->reference,
            'amount'         => $payment->total_in_cents / 100,
        ]);

        $to = config('mail.admin_address') ?: config('services.mail_site.mail_admin_address');
        if ($to) {
            try {
                Mail::raw(
                    "Pago recibido para una cita que ya había expirado y cuyo turno ya no está libre.\n\n"
                    . "Cita: {$appointment->reference}\nPago: {$payment->reference}\nValor: " . ($payment->total_in_cents / 100) . " COP\n\n"
                    . 'Hay que reprogramar la cita con el paciente o reembolsar el pago.',
                    fn ($m) => $m->to($to)->subject('Pago recibido sobre una cita expirada')
                );
            } catch (Throwable) {
                // el log crítico ya quedó
            }
        }
    }

    private function raw(mixed $value): string
    {
        return $value instanceof \BackedEnum ? (string) $value->value : (string) $value;
    }
}
