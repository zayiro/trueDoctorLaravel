<?php

namespace App\Services;

use App\Models\Appointment;
use App\Enums\AppointmentStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * 🔄 GESTOR DE ESTADOS DE CITAS
 *
 * Única puerta para cambiar el estado de una cita:
 *  - Valida la transición (matriz + reglas de negocio).
 *  - Aplica el cambio dentro de una transacción y deja auditoría.
 *  - Dispara los eventos DESPUÉS del commit (los listeners en cola ven siempre el dato confirmado).
 *
 * Los estados de la matriz son EXACTAMENTE los casos del enum App\Enums\AppointmentStatus
 * (pending, confirmed, cancelled, completed). La versión anterior usaba estados que no existen
 * en el enum (executed, no_show, rescheduled, zoom_*_failed): update() habría lanzado ValueError.
 *
 * Qué hace cada transición, vía eventos (los listeners se descubren solos en app/Listeners):
 *   confirmed → AppointmentConfirmed → InvalidateAvailabilityCacheOnAppointmentConfirmed
 *   cancelled → AppointmentCancelled → SendCancellationEmail   (la reunión de Zoom la elimina AppointmentObserver)
 *   completed → AppointmentCompleted → EndZoomMeetingOnAppointmentCompleted
 */
class AppointmentStateManager
{
    /**
     * Transiciones permitidas. Formato: 'estado_actual' => ['estado_permitido', ...]
     * completed y cancelled son estados finales.
     */
    private const VALID_TRANSITIONS = [
        'pending'   => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled', 'pending'], // 'pending': devolver a revisión (flujos con aprobación)
        'completed' => [],
        'cancelled' => [],
    ];

    private const STATE_DESCRIPTIONS = [
        'pending'   => 'Cita pendiente de confirmación',
        'confirmed' => 'Cita confirmada',
        'completed' => 'Cita completada',
        'cancelled' => 'Cita cancelada',
    ];

    /**
     * Cambia el estado de una cita.
     *
     * @param Appointment              $appointment    Cita a actualizar
     * @param string|AppointmentStatus $newStatus      Estado deseado
     * @param string|null              $reason         Motivo (auditoría)
     * @param array                    $additionalData Campos extra a guardar en la misma actualización (notes, email_sent…)
     * @param string|null              $actor          Quién lo hace: 'patient', 'doctor' o 'system' (se pasa a los eventos)
     * @return bool
     * @throws \InvalidArgumentException si la transición no es válida (el mensaje es apto para mostrar al usuario)
     */
    public function transitionTo(
        Appointment $appointment,
        $newStatus,
        ?string $reason = null,
        array $additionalData = [],
        ?string $actor = null
    ): bool {
        $newStatusValue = $newStatus instanceof AppointmentStatus ? $newStatus->value : (string) $newStatus;
        $currentStatus  = $this->currentStatus($appointment);

        if ($error = $this->validationError($appointment, $newStatusValue)) {
            Log::warning('AppointmentStateManager: Transición rechazada', [
                'appointment_id'   => $appointment->id,
                'current_status'   => $currentStatus,
                'requested_status' => $newStatusValue,
                'error'            => $error,
            ]);

            throw new \InvalidArgumentException($error);
        }

        try {
            DB::transaction(function () use ($appointment, $newStatusValue, $currentStatus, $reason, $additionalData, $actor) {
                $appointment->update(array_merge(['status' => $newStatusValue], $additionalData));

                $this->logStateTransition($appointment, $currentStatus, $newStatusValue, $reason);

                // Los eventos salen cuando la transacción (incluida la de quien nos llame) ya se confirmó
                DB::afterCommit(function () use ($appointment, $newStatusValue, $actor) {
                    $this->dispatchTransitionEvents($appointment, $newStatusValue, $actor);
                });
            });

            Log::info('AppointmentStateManager: Transición exitosa', [
                'appointment_id' => $appointment->id,
                'from_status'    => $currentStatus,
                'to_status'      => $newStatusValue,
                'actor'          => $actor,
                'reason'         => $reason,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('AppointmentStateManager: Error durante transición', [
                'appointment_id' => $appointment->id,
                'error'          => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Devuelve el mensaje de error si el cambio NO es válido, o null si se puede hacer.
     * Reúne la matriz de transiciones y las reglas de negocio (pago y hora de inicio).
     */
    public function validationError(Appointment $appointment, string $target): ?string
    {
        $current = $this->currentStatus($appointment);

        if ($current === $target) {
            return 'La cita ya se encuentra en ese estado.';
        }

        if (!$this->isTransitionValid($current, $target)) {
            if (in_array($current, ['completed', 'cancelled'], true)) {
                $label = $current === 'completed' ? 'completada' : 'cancelada';
                return "La cita ya está {$label} y no admite más cambios de estado.";
            }

            return "Transición no permitida: {$current} → {$target}.";
        }

        if ($target === 'completed') {
            if ($appointment->isAwaitingPayment()) {
                return 'No se puede completar: el paciente aún no ha pagado esta cita virtual.';
            }

            if (now(config('app.timezone', 'America/Bogota'))->lessThan($appointment->starts_at)) {
                return 'No se puede completar una cita que aún no ha comenzado.';
            }
        }

        return null;
    }

    public function isTransitionValid(string $currentStatus, string $newStatus): bool
    {
        if ($currentStatus === $newStatus) {
            return false;
        }

        if (!isset(self::VALID_TRANSITIONS[$currentStatus])) {
            return false;
        }

        return in_array($newStatus, self::VALID_TRANSITIONS[$currentStatus], true);
    }

    public function getAvailableTransitions(Appointment $appointment): array
    {
        return self::VALID_TRANSITIONS[$this->currentStatus($appointment)] ?? [];
    }

    public function canBeCancelled(Appointment $appointment): bool
    {
        return $this->validationError($appointment, 'cancelled') === null;
    }

    public function canBeConfirmed(Appointment $appointment): bool
    {
        return $this->validationError($appointment, 'confirmed') === null;
    }

    public function canBeCompleted(Appointment $appointment): bool
    {
        return $this->validationError($appointment, 'completed') === null;
    }

    public function getStatusDescription(string $status): string
    {
        return self::STATE_DESCRIPTIONS[$status] ?? 'Estado desconocido';
    }

    public function getValidTransitions(): array
    {
        return self::VALID_TRANSITIONS;
    }

    public function getTransitionHistory(Appointment $appointment)
    {
        return DB::table('appointment_state_transitions')
            ->where('appointment_id', $appointment->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function currentStatus(Appointment $appointment): string
    {
        return $appointment->status instanceof \BackedEnum ? $appointment->status->value : (string) $appointment->status;
    }

    /**
     * Auditoría de la transición. Si la tabla no existe o falla, se registra en el log y NO se interrumpe el cambio.
     */
    private function logStateTransition(Appointment $appointment, string $fromStatus, string $toStatus, ?string $reason = null): void
    {
        try {
            DB::table('appointment_state_transitions')->insert([
                'appointment_id' => $appointment->id,
                'from_status'    => $fromStatus,
                'to_status'      => $toStatus,
                'reason'         => $reason,
                'user_id'        => auth()->id(),
                'ip_address'     => request()->ip(),
                'user_agent'     => substr((string) request()->userAgent(), 0, 255),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('AppointmentStateManager: Error al registrar transición en auditoría', [
                'appointment_id' => $appointment->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    /**
     * Dispara el evento que corresponde al nuevo estado. Cada uno va aislado: si uno falla, no impide al resto.
     */
    private function dispatchTransitionEvents(Appointment $appointment, string $toStatus, ?string $actor): void
    {
        try {
            match ($toStatus) {
                'confirmed' => event(new \App\Events\AppointmentConfirmed($appointment)),
                'cancelled' => event(new \App\Events\AppointmentCancelled($appointment, $actor)),
                'completed' => event(new \App\Events\AppointmentCompleted($appointment)),
                default     => null,
            };
        } catch (Throwable $e) {
            Log::error('AppointmentStateManager: Error al disparar eventos', [
                'appointment_id' => $appointment->id,
                'to_status'      => $toStatus,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}