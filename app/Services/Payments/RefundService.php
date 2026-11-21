<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Enums\PaymentTransactionStatus;
use App\Mail\RefundProcessedMail;
use App\Models\Appointment;
use App\Models\DoctorPayout;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\User;
use App\Services\AppointmentEventSourcing;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Reembolsos de citas pagadas en línea que se cancelan.
 *
 * - openForCancelledAppointment(): al cancelar, abre UN reembolso pendiente por el total que pagó el paciente
 *   (idempotente) y retira el pago pendiente al médico/clínica, si existía.
 * - complete(): el admin registra que ya devolvió el dinero en Wompi; el pago pasa a "reembolsado" y el paciente
 *   recibe el correo de confirmación.
 */
class RefundService
{
    public function openForCancelledAppointment(Appointment $appointment, ?string $actor = null): ?PaymentRefund
    {
        $payment = Payment::query()
            ->where('payable_type', Appointment::class)
            ->where('payable_id', $appointment->id)
            ->where('kind', 'appointment')
            ->where('method', 'online')
            ->where('status', PaymentTransactionStatus::Approved->value)
            ->orderByDesc('id')
            ->first();

        if (!$payment || (int) $payment->total_in_cents <= 0) {
            return null; // gratis, pago en consulta o sin pago aprobado: no hay nada que devolver
        }

        $created = false;

        try {
            $refund = DB::transaction(function () use ($appointment, $payment, $actor, &$created) {
                $existing = PaymentRefund::where('payment_id', $payment->id)->first();
                if ($existing) {
                    return $existing;
                }

                $notes = $this->releasePayouts($appointment);

                $created = true;

                return PaymentRefund::create([
                    'payment_id'      => $payment->id,
                    'appointment_id'  => $appointment->id,
                    'patient_user_id' => $appointment->patient?->user_id,
                    'amount_in_cents' => (int) $payment->total_in_cents,
                    'status'          => PaymentRefund::PENDING,
                    'cancelled_by'    => $actor,
                    'reason'          => $this->reasonOf($appointment),
                    'notes'           => $notes,
                ]);
            });

            if ($created) {
                $this->audit($appointment, 'refund_opened', [
                    'refund_id'       => $refund->id,
                    'payment_id'      => $payment->id,
                    'amount_in_cents' => $refund->amount_in_cents,
                    'cancelled_by'    => $actor,
                ], 'Reembolso pendiente por ' . $this->money($refund->amount_in_cents) . ' (cita pagada en línea cancelada).');
            }

            return $refund;
        } catch (UniqueConstraintViolationException) {
            return PaymentRefund::where('payment_id', $payment->id)->first();
        }
    }

    /**
     * Registra que el reembolso ya se hizo en Wompi.
     *
     * @throws \DomainException si ya estaba registrado
     */
    public function complete(PaymentRefund $refund, User $admin, string $providerReference, ?string $adminNote = null): PaymentRefund
    {
        $done = DB::transaction(function () use ($refund, $admin, $providerReference, $adminNote) {
            $r = PaymentRefund::whereKey($refund->id)->lockForUpdate()->firstOrFail();

            if (!$r->isPending()) {
                throw new \DomainException('Este reembolso ya estaba registrado como devuelto.');
            }

            $r->update([
                'status'             => PaymentRefund::COMPLETED,
                'provider_reference' => trim($providerReference),
                'admin_note'         => $adminNote ? trim($adminNote) : null,
                'processed_by'       => $admin->id,
                'processed_at'       => now(),
            ]);

            $payment = Payment::whereKey($r->payment_id)->lockForUpdate()->first();
            if ($payment && $payment->status instanceof PaymentTransactionStatus
                && $payment->status->canTransitionTo(PaymentTransactionStatus::Refunded)) {
                $payment->update(['status' => PaymentTransactionStatus::Refunded]);
            }

            return $r;
        });

        $appointment = Appointment::withTrashed()->find($done->appointment_id);
        if ($appointment) {
            $this->audit($appointment, 'refund_completed', [
                'refund_id'          => $done->id,
                'amount_in_cents'    => $done->amount_in_cents,
                'provider_reference' => $done->provider_reference,
                'processed_by'       => $admin->id,
            ], 'Reembolso devuelto: ' . $this->money($done->amount_in_cents) . ' (ref. ' . $done->provider_reference . ').');
        }

        $this->notifyPatient($done);

        return $done;
    }

    /** Trazabilidad en la línea de tiempo de la cita. Un fallo aquí nunca deshace el reembolso. */
    private function audit(Appointment $appointment, string $event, array $payload, string $description): void
    {
        try {
            AppointmentEventSourcing::recordEvent($appointment, $event, $payload, $description);
        } catch (Throwable $e) {
            Log::warning('refund.audit_failed', ['appointment_id' => $appointment->id, 'event' => $event, 'error' => $e->getMessage()]);
        }
    }

    private function money(int $cents): string
    {
        return '$' . number_format($cents / 100, 0, ',', '.') . ' COP';
    }

    private function notifyPatient(PaymentRefund $refund): void
    {
        try {
            $appointment = Appointment::withTrashed()->with('patient.user')->find($refund->appointment_id);
            $email = $appointment?->patient?->user?->email;

            if ($email) {
                Mail::to($email)->send(new RefundProcessedMail($refund));
            }
        } catch (Throwable $e) {
            Log::error('refund.mail_failed', ['refund_id' => $refund->id, 'error' => $e->getMessage()]);
        }
    }

    /** Retira el pago pendiente al médico/clínica; si ya se había pagado, deja la alerta en las notas. */
    private function releasePayouts(Appointment $appointment): ?string
    {
        $lines = [];

        foreach (DoctorPayout::where('appointment_id', $appointment->id)->get() as $payout) {
            $amount = '$' . number_format((float) $payout->amount_to_pay, 0, ',', '.');

            if ($payout->status === 'pending') {
                $payout->delete();
                $lines[] = "Se retiró el pago pendiente al médico/clínica ({$amount}).";
            } else {
                $lines[] = "ATENCIÓN: el pago al médico/clínica ({$amount}) ya estaba pagado"
                    . ($payout->transfer_reference ? " (ref. {$payout->transfer_reference})" : '')
                    . '. Hay que recuperarlo aparte.';
            }
        }

        return $lines ? implode("\n", $lines) : null;
    }

    private function reasonOf(Appointment $appointment): ?string
    {
        if ($appointment->notes && preg_match('/Motivo:\s*(.+?)\s*\]?\s*$/mu', (string) $appointment->notes, $m)) {
            return mb_substr(trim($m[1]), 0, 255);
        }

        return null;
    }
}
