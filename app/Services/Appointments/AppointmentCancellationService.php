<?php

declare(strict_types=1);

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use App\Notifications\AppointmentCancelledNotification;
use App\Services\AppointmentEventSourcing;
use App\Services\AppointmentStateManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Única fuente de verdad para cancelar una cita.
 *
 * - policyFor(): la política (¿se permite? ¿con cuántas horas de aviso?) que antes estaba duplicada.
 * - cancel(): cancela por AppointmentStateManager (valida la transición, audita y dispara AppointmentCancelled,
 *   cuyo listener abre el reembolso y retira el desembolso pendiente). El Zoom lo elimina AppointmentObserver.
 * - abandonBooking(): botón "Cancelar" de la vista previa de la reserva. Nunca borra: una reserva sin pagar se
 *   libera (igual que el vencimiento por falta de pago), una cita pagada se rechaza y una confirmada sin pago
 *   pasa por la cancelación normal.
 */
class AppointmentCancellationService
{
    public function __construct(private readonly AppointmentStateManager $states)
    {
    }

    /** @return array{allowed: bool, notice_hours: int} */
    public function policyFor(Appointment $appointment): array
    {
        if ($appointment->clinic_id && $appointment->clinic) {
            $settings = $appointment->clinic->settings;

            return [
                'allowed'      => (bool) ($settings->allow_patient_cancellation ?? true),
                'notice_hours' => (int) ($settings->cancellation_notice_hours ?? 2),
            ];
        }

        $settings = $appointment->doctor?->settings;

        return [
            'allowed'      => (bool) ($settings->allow_patient_cancellation ?? true),
            'notice_hours' => (int) ($settings->cancellation_notice_hours ?? 24),
        ];
    }

    /**
     * @param string $actor  'patient' (respeta política y plazo), 'doctor' o 'system' (sin plazo)
     * @param string $origin 'web' o 'api'
     */
    public function cancel(Appointment $appointment, string $actor, ?string $motivo = null, string $origin = 'web'): CancellationResult
    {
        $appointment->loadMissing(['doctor.settings', 'clinic.settings']);

        if (!$appointment->isActive()) {
            return CancellationResult::failure('Esta consulta ya no se puede cancelar.');
        }

        if ($actor === 'patient') {
            $now   = Carbon::now('America/Bogota');
            $start = Carbon::parse($appointment->date->format('Y-m-d') . ' ' . $appointment->start_time, 'America/Bogota');

            if ($now->greaterThanOrEqualTo($start)) {
                return CancellationResult::failure('No es posible cancelar una consulta médica que ya se encuentra en desarrollo o que pertenece al pasado.');
            }

            $policy = $this->policyFor($appointment);

            if (!$policy['allowed']) {
                return CancellationResult::failure('Las políticas vigentes no permiten la cancelación autónoma de citas. Contacta soporte.');
            }

            $hours = $policy['notice_hours'];
            if ($now->diffInHours($start, false) < $hours) {
                return CancellationResult::failure("Las políticas de este centro médico o especialista exigen un mínimo de {$hours} horas de anticipación para cancelar el turno.");
            }
        }

        $reason = $this->reasonFor($actor, $motivo, $origin);

        try {
            $this->states->transitionTo(
                $appointment,
                'cancelled',
                $reason,
                ['notes' => ($appointment->notes ? $appointment->notes . "\n" : '') . "[Auditoría: {$reason}]"],
                $actor
            );
        } catch (InvalidArgumentException $e) {
            return CancellationResult::failure($e->getMessage());
        }

        if ($actor === 'patient') {
            $this->notifyProvider($appointment);
        }

        if ($origin === 'api') {
            try {
                AppointmentEventSourcing::recordCancelled($appointment, 'Cancelada vía API Gateway');
            } catch (Throwable $e) {
                Log::warning('cancel.audit_failed', ['appointment_id' => $appointment->id, 'error' => $e->getMessage()]);
            }
        }

        return CancellationResult::success('La consulta médica ha sido cancelada.', 'cancelled');
    }

    public function abandonBooking(Appointment $appointment, string $actor): CancellationResult
    {
        $status  = $this->raw($appointment->status);
        $payment = $this->raw($appointment->payment_status);

        if (!in_array($status, ['pending', 'confirmed'], true)) {
            return CancellationResult::success('', 'noop'); // ya estaba cancelada o completada: no hay nada que hacer
        }

        $notPaidMessage = 'Esta cita ya está pagada. Para cancelarla usa "Cancelar" en Mis citas; si corresponde, se te devolverá el pago.';

        if ($payment === 'paid') {
            return CancellationResult::failure($notPaidMessage);
        }

        if ($status === 'pending' && $payment === 'pending' && $appointment->paid_at === null) {
            // Atómico, igual que el vencimiento por falta de pago: si el pago se confirma justo ahora, no toca nada.
            $released = Appointment::whereKey($appointment->id)
                ->where('status', 'pending')
                ->where('payment_status', 'pending')
                ->whereNull('paid_at')
                ->update(['status' => 'cancelled', 'payment_status' => 'cancelled']);

            if (!$released) {
                return CancellationResult::failure('Tu pago se está confirmando. Revisa tu cita en unos segundos en Mis citas.');
            }

            try {
                AppointmentEventSourcing::recordCancelled($appointment, 'Reserva abandonada por el usuario antes de pagar.');
            } catch (Throwable $e) {
                Log::warning('cancel.audit_failed', ['appointment_id' => $appointment->id, 'error' => $e->getMessage()]);
            }

            return CancellationResult::success('', 'released');
        }

        // Confirmada y sin pago (presencial): cancelación normal, con plazo, aviso y auditoría.
        return $this->cancel($appointment, $actor === 'patient' ? 'patient' : 'doctor');
    }

    private function reasonFor(string $actor, ?string $motivo, string $origin): string
    {
        $motivo = $motivo !== null ? trim($motivo) : null;

        if ($origin === 'api') {
            return 'Cancelada vía API Gateway' . ($motivo ? ". Motivo: {$motivo}" : '');
        }

        if ($actor === 'patient') {
            return $motivo
                ? 'Cancelado por el paciente. Motivo: ' . $motivo
                : 'Cancelado de forma autónoma por el paciente desde la plataforma web.';
        }

        return $motivo
            ? 'Cancelado por el profesional. Motivo: ' . $motivo
            : 'Cancelado por el profesional desde la plataforma.';
    }

    private function notifyProvider(Appointment $appointment): void
    {
        try {
            $doctor = Doctor::find($appointment->doctor_id);
            $user   = $doctor ? User::find($doctor->user_id) : null;
            $user?->notify(new AppointmentCancelledNotification($appointment, 'patient'));
        } catch (Throwable $e) {
            Log::warning('cancel.notify_failed', ['appointment_id' => $appointment->id, 'error' => $e->getMessage()]);
        }
    }

    private function raw(mixed $value): string
    {
        return $value instanceof \BackedEnum ? (string) $value->value : (string) $value;
    }
}
