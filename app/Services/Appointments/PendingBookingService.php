<?php

declare(strict_types=1);

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * La "reserva en curso" del paciente: la videoconsulta que empezó a agendar y dejó sin pagar.
 *
 * - current(): cuál mostrar en el aviso de la página de inicio (solo si todavía está viva).
 * - discard(): "Olvidar". Descarta la reserva de verdad por la puerta oficial (AppointmentExpirer):
 *   consulta la pasarela por si el pago ya se aprobó, libera el turno, marca los pagos pendientes como
 *   vencidos y no avisa a nadie (para el médico y la clínica esa reserva nunca existió).
 */
final class PendingBookingService
{
    public function __construct(private readonly AppointmentExpirer $expirer)
    {
    }

    /** Reserva virtual pendiente de pago del paciente, que aún retiene el turno. */
    public function current(?User $user): ?Appointment
    {
        $patientId = $user?->patient?->id;

        // Sin perfil de paciente no hay reserva que mostrar (un where con null buscaría citas sin paciente).
        if (!$patientId) {
            return null;
        }

        $holdMinutes = (int) config('booking.pending_hold_minutes', 120);
        $now = now();

        return Appointment::query()
            ->with(['doctor.user', 'service'])
            ->where('patient_id', $patientId)
            ->where('status', 'pending')
            ->where('payment_status', 'pending')
            ->whereNull('paid_at')
            ->whereHas('service', fn ($q) => $q->where('type', 'virtual'))
            ->where(function ($q) use ($now, $holdMinutes) {
                // Solo mientras el turno siga retenido: si ya venció, "Retomar" llevaría a una reserva muerta.
                $q->where('payment_expires_at', '>', $now)
                    ->orWhere(fn ($legacy) => $legacy->whereNull('payment_expires_at')
                        ->where('created_at', '>=', $now->copy()->subMinutes($holdMinutes)));
            })
            ->latest()
            ->first();
    }

    /**
     * @return array{ok: bool, message: string}
     *
     * @throws AuthorizationException si la reserva no es del paciente.
     */
    public function discard(Appointment $appointment, User $user): array
    {
        $patientId = $user->patient?->id;

        if (!$patientId || (int) $appointment->patient_id !== (int) $patientId) {
            throw new AuthorizationException('Esta reserva no te pertenece.');
        }

        if ($this->raw($appointment->status) !== 'pending'
            || $this->raw($appointment->payment_status) !== 'pending'
            || $appointment->paid_at !== null) {
            return ['ok' => false, 'message' => 'Esa reserva ya no está pendiente de pago.'];
        }

        try {
            $done = $this->expirer->expire(
                (int) $appointment->id,
                'Reserva descartada por el paciente',
                'patient',
                false
            );
        } catch (Throwable $e) {
            Log::error('appointments.discard_failed', ['appointment_id' => $appointment->id, 'error' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'No pudimos descartar la reserva. Intenta de nuevo en unos minutos.'];
        }

        if (!$done) {
            return ['ok' => false, 'message' => 'Esa reserva ya no está pendiente de pago (puede que ya se haya pagado o vencido).'];
        }

        Log::info('appointments.discarded_by_patient', ['appointment_id' => $appointment->id]);

        return ['ok' => true, 'message' => 'Listo, descartamos tu reserva y el turno quedó libre.'];
    }

    private function raw(mixed $v): string
    {
        return $v instanceof \BackedEnum ? (string) $v->value : (string) $v;
    }
}
