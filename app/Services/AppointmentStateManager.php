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
 * Centraliza la lógica de transiciones de estado con validación estricta.
 * Previene transiciones inválidas (ej: cancelada → ejecutada).
 * Garantiza integridad de datos mediante transacciones.
 */
class AppointmentStateManager
{
    /**
     * Define las transiciones de estado permitidas
     * Formato: 'estado_actual' => ['estado_permitido_1', 'estado_permitido_2', ...]
     */
    private const VALID_TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['executed', 'cancelled', 'no_show'],
        'executed' => ['completed'],
        'completed' => [],
        'cancelled' => [],
        'no_show' => ['rescheduled'],
        'rescheduled' => ['confirmed', 'cancelled'],
        'zoom_creation_failed' => ['pending', 'cancelled'],
        'zoom_update_failed' => ['confirmed', 'cancelled'],
        'zoom_delete_failed' => ['cancelled'],
    ];

    /**
     * Descripciones de estados para logs y auditoría
     */
    private const STATE_DESCRIPTIONS = [
        'pending' => 'Cita pendiente de confirmación',
        'confirmed' => 'Cita confirmada',
        'executed' => 'Cita en ejecución',
        'completed' => 'Cita completada',
        'cancelled' => 'Cita cancelada',
        'no_show' => 'Paciente no se presentó',
        'rescheduled' => 'Cita reprogramada',
        'zoom_creation_failed' => 'Error al crear reunión Zoom',
        'zoom_update_failed' => 'Error al actualizar reunión Zoom',
        'zoom_delete_failed' => 'Error al eliminar reunión Zoom',
    ];

    /**
     * Intenta cambiar el estado de una cita
     * Valida la transición antes de guardar
     * 
     * @param Appointment $appointment Cita a actualizar
     * @param string|AppointmentStatus $newStatus Nuevo estado deseado
     * @param string|null $reason Razón del cambio (para auditoría)
     * @param array $additionalData Datos adicionales a actualizar
     * @return bool True si la transición fue exitosa
     * @throws \InvalidArgumentException Si la transición no es válida
     */
    public function transitionTo(
        Appointment $appointment,
        $newStatus,
        ?string $reason = null,
        array $additionalData = []
    ): bool {
        try {
            // 1. Normalizar el estado nuevo
            $newStatusValue = $newStatus instanceof AppointmentStatus 
                ? $newStatus->value 
                : $newStatus;

            // 2. Obtener el estado actual
            $currentStatus = $appointment->status->value;

            // 3. Validar que la transición sea permitida
            if (!$this->isTransitionValid($currentStatus, $newStatusValue)) {
                throw new \InvalidArgumentException(
                    "Transición inválida: {$currentStatus} → {$newStatusValue}"
                );
            }

            // 4. Ejecutar la transición en una transacción
            return DB::transaction(function () use ($appointment, $newStatusValue, $reason, $additionalData, $currentStatus) {
                // Preparar datos a actualizar
                $updateData = array_merge(
                    ['status' => $newStatusValue],
                    $additionalData
                );

                // Actualizar la cita
                $appointment->update($updateData);

                // Registrar el cambio en auditoría
                $this->logStateTransition($appointment, $currentStatus, $newStatusValue, $reason);

                // Disparar eventos específicos según la transición
                $this->dispatchTransitionEvents($appointment, $currentStatus, $newStatusValue);

                Log::info('AppointmentStateManager: Transición exitosa', [
                    'appointment_id' => $appointment->id,
                    'from_status' => $currentStatus,
                    'to_status' => $newStatusValue,
                    'reason' => $reason,
                    'timestamp' => now()
                ]);

                return true;
            });
        } catch (\InvalidArgumentException $e) {
            Log::warning('AppointmentStateManager: Transición rechazada', [
                'appointment_id' => $appointment->id,
                'current_status' => $appointment->status->value,
                'requested_status' => $newStatus,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } catch (Throwable $e) {
            Log::error('AppointmentStateManager: Error durante transición', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Valida si una transición de estado es permitida
     * 
     * @param string $currentStatus Estado actual
     * @param string $newStatus Estado deseado
     * @return bool True si la transición es válida
     */
    public function isTransitionValid(string $currentStatus, string $newStatus): bool
    {
        // No permitir cambiar al mismo estado
        if ($currentStatus === $newStatus) {
            return false;
        }

        // Verificar si la transición existe en la matriz de transiciones válidas
        if (!isset(self::VALID_TRANSITIONS[$currentStatus])) {
            return false;
        }

        return in_array($newStatus, self::VALID_TRANSITIONS[$currentStatus], true);
    }

    /**
     * Obtiene los estados a los que se puede transicionar desde el estado actual
     * 
     * @param Appointment $appointment Cita a evaluar
     * @return array Lista de estados permitidos
     */
    public function getAvailableTransitions(Appointment $appointment): array
    {
        $currentStatus = $appointment->status->value;
        return self::VALID_TRANSITIONS[$currentStatus] ?? [];
    }

    /**
     * Verifica si una cita puede ser cancelada
     * 
     * @param Appointment $appointment Cita a evaluar
     * @return bool True si puede ser cancelada
     */
    public function canBeCancelled(Appointment $appointment): bool
    {
        return $this->isTransitionValid($appointment->status->value, 'cancelled');
    }

    /**
     * Verifica si una cita puede ser ejecutada
     * 
     * @param Appointment $appointment Cita a evaluar
     * @return bool True si puede ser ejecutada
     */
    public function canBeExecuted(Appointment $appointment): bool
    {
        return $this->isTransitionValid($appointment->status->value, 'executed');
    }

    /**
     * Verifica si una cita puede ser confirmada
     * 
     * @param Appointment $appointment Cita a evaluar
     * @return bool True si puede ser confirmada
     */
    public function canBeConfirmed(Appointment $appointment): bool
    {
        return $this->isTransitionValid($appointment->status->value, 'confirmed');
    }

    /**
     * Registra el cambio de estado en auditoría
     * 
     * @param Appointment $appointment Cita actualizada
     * @param string $fromStatus Estado anterior
     * @param string $toStatus Estado nuevo
     * @param string|null $reason Razón del cambio
     * @return void
     */
    private function logStateTransition(
        Appointment $appointment,
        string $fromStatus,
        string $toStatus,
        ?string $reason = null
    ): void {
        try {
            DB::table('appointment_state_transitions')->insert([
                'appointment_id' => $appointment->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'reason' => $reason,
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (Throwable $e) {
            Log::error('AppointmentStateManager: Error al registrar transición en auditoría', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Dispara eventos específicos según la transición realizada
     * 
     * @param Appointment $appointment Cita actualizada
     * @param string $fromStatus Estado anterior
     * @param string $toStatus Estado nuevo
     * @return void
     */
    private function dispatchTransitionEvents(
        Appointment $appointment,
        string $fromStatus,
        string $toStatus
    ): void {
        try {
            // Evento cuando se confirma una cita
            if ($toStatus === 'confirmed') {
                event(new \App\Events\AppointmentConfirmed($appointment));
            }

            // Evento cuando se cancela una cita
            if ($toStatus === 'cancelled') {
                event(new \App\Events\AppointmentCancelled($appointment));
            }

            // Evento cuando se ejecuta una cita
            if ($toStatus === 'executed') {
                event(new \App\Events\AppointmentExecuted($appointment));
            }

            // Evento cuando se completa una cita
            if ($toStatus === 'completed') {
                event(new \App\Events\AppointmentCompleted($appointment));
            }
        } catch (Throwable $e) {
            Log::error('AppointmentStateManager: Error al disparar eventos', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtiene la descripción de un estado
     * 
     * @param string $status Estado a describir
     * @return string Descripción del estado
     */
    public function getStatusDescription(string $status): string
    {
        return self::STATE_DESCRIPTIONS[$status] ?? 'Estado desconocido';
    }

    /**
     * Obtiene todas las transiciones válidas (matriz completa)
     * Útil para debugging y documentación
     * 
     * @return array Matriz de transiciones válidas
     */
    public function getValidTransitions(): array
    {
        return self::VALID_TRANSITIONS;
    }

    /**
     * Obtiene el historial de transiciones de una cita
     * 
     * @param Appointment $appointment Cita a evaluar
     * @return \Illuminate\Database\Eloquent\Collection Historial de transiciones
     */
    public function getTransitionHistory(Appointment $appointment)
    {
        return DB::table('appointment_state_transitions')
            ->where('appointment_id', $appointment->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
