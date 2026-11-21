<?php

declare(strict_types=1);

namespace App\Services\Appointments;

use App\Enums\PaymentKind;
use App\Enums\PaymentTransactionStatus;
use App\Mail\AppointmentExpiredMail;
use App\Models\Appointment;
use App\Models\Payment;
use App\Payments\PaymentService;
use App\Services\AppointmentStateManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Libera las reservas que no se pagaron a tiempo. No avisa al doctor: para él esa cita nunca existió.
 */
class AppointmentExpirer
{
    public function __construct(
        private readonly AppointmentStateManager $states,
        private readonly PaymentService $payments,
    ) {
    }

    /** @return int citas expiradas */
    public function sweep(int $limit = 200): int
    {
        $holdMinutes = (int) config('booking.pending_hold_minutes', 120);
        $now = now();

        $ids = Appointment::query()
            ->where('status', 'pending')
            ->where('payment_status', 'pending')
            ->whereNull('paid_at')
            ->where(function ($q) use ($now, $holdMinutes) {
                $q->where('payment_expires_at', '<=', $now)
                    // Reservas virtuales anteriores a esta versión (sin vencimiento guardado).
                    ->orWhere(fn ($q2) => $q2->whereNull('payment_expires_at')
                        ->where('created_at', '<=', $now->copy()->subMinutes($holdMinutes))
                        ->whereHas('service', fn ($sq) => $sq->where('type', 'virtual')));
            })
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $expired = 0;

        foreach ($ids as $id) {
            try {
                if ($this->expire((int) $id)) {
                    $expired++;
                }
            } catch (Throwable $e) {
                Log::error('appointments.expire_failed', ['appointment_id' => $id, 'error' => $e->getMessage()]);
            }
        }

        return $expired;
    }

    /**
     * Expira una reserva sin pagar y libera el turno.
     *
     * Por defecto es la expiración automática (motivo "sin pago a tiempo", actor 'system', con correo al paciente).
     * Cuando el propio paciente descarta la reserva se llama con otro motivo, actor 'patient' y sin correo.
     */
    public function expire(int $appointmentId, string $reason = 'Reserva sin pago a tiempo', string $actor = 'system', bool $notifyPatient = true): bool
    {
        // Antes de soltar el turno se le pregunta a la pasarela: el pago pudo aprobarse sin que llegara el webhook.
        Payment::where('payable_type', (new Appointment)->getMorphClass())
            ->where('payable_id', $appointmentId)
            ->where('kind', PaymentKind::Appointment->value)
            ->where('status', PaymentTransactionStatus::Pending->value)
            ->get()
            ->each(function (Payment $p) {
                try {
                    $this->payments->syncByReference($p->reference);
                } catch (Throwable $e) {
                    Log::warning('appointments.expire_sync_failed', ['payment' => $p->reference, 'error' => $e->getMessage()]);
                }
            });

        $done = DB::transaction(function () use ($appointmentId, $reason, $actor) {
            $appointment = Appointment::whereKey($appointmentId)->lockForUpdate()->first();

            if (!$appointment
                || $this->raw($appointment->status) !== 'pending'
                || $this->raw($appointment->payment_status) !== 'pending'
                || $appointment->paid_at !== null) {
                return false;
            }

            $this->states->transitionTo($appointment, 'expired', $reason, [
                'payment_status'     => 'cancelled',
                'payment_expires_at' => null,
            ], $actor);

            Payment::where('payable_type', $appointment->getMorphClass())
                ->where('payable_id', $appointment->id)
                ->where('status', PaymentTransactionStatus::Pending->value)
                ->get()
                ->each(fn (Payment $p) => $this->payments->markExpired($p));

            return true;
        });

        if (!$done) {
            return false;
        }

        $appointment = Appointment::with(['patient.user', 'doctor.user', 'service'])->find($appointmentId);
        $email = $appointment?->patient?->user?->email;

        if ($email && $notifyPatient) {
            try {
                Mail::to($email)->send(new AppointmentExpiredMail($appointment));
            } catch (Throwable $e) {
                Log::error('appointments.expire_mail_failed', ['appointment_id' => $appointmentId, 'error' => $e->getMessage()]);
            }
        }

        Log::info('appointments.expired', ['appointment_id' => $appointmentId]);

        return true;
    }

    private function raw(mixed $v): string
    {
        return $v instanceof \BackedEnum ? (string) $v->value : (string) $v;
    }
}
