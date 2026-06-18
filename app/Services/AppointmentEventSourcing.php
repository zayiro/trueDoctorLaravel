<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 📋 SERVICIO DE EVENT SOURCING
 * 
 * Centraliza el registro de eventos para auditoría y reconstrucción de estado.
 * Cada cambio en una cita genera un evento inmutable.
 */
class AppointmentEventSourcing
{
    /**
     * Tipos de eventos soportados
     */
    public const EVENT_CREATED = 'created';
    public const EVENT_CONFIRMED = 'confirmed';
    public const EVENT_RESCHEDULED = 'rescheduled';
    public const EVENT_CANCELLED = 'cancelled';
    public const EVENT_EXECUTED = 'executed';
    public const EVENT_COMPLETED = 'completed';
    public const EVENT_NO_SHOW = 'no_show';
    public const EVENT_ZOOM_CREATED = 'zoom_created';
    public const EVENT_ZOOM_UPDATED = 'zoom_updated';
    public const EVENT_ZOOM_DELETED = 'zoom_deleted';
    public const EVENT_ZOOM_FAILED = 'zoom_failed';
    public const EVENT_PAYMENT_PROCESSED = 'payment_processed';
    public const EVENT_PAYMENT_FAILED = 'payment_failed';

    /**
     * Registra un evento de creación de cita
     */
    public static function recordCreated(Appointment $appointment, array $data = []): AppointmentEvent
    {
        return self::recordEvent(
            appointment: $appointment,
            eventType: self::EVENT_CREATED,
            payload: $data ?: $appointment->toArray(),
            description: "Cita creada por {$appointment->patient->user->name}"
        );
    }

    /**
     * Registra un evento de confirmación de cita
     */
    public static function recordConfirmed(Appointment $appointment, ?string $reason = null): AppointmentEvent
    {
        return self::recordEvent(
            appointment: $appointment,
            eventType: self::EVENT_CONFIRMED,
            payload: [
                'status' => $appointment->status->value,
                'confirmed_at' => now()->toIso8601String(),
                'reason' => $reason,
            ],
            description: "Cita confirmada" . ($reason ? ": {$reason}" : "")
        );
    }

    /**
     * Registra un evento de reagendamiento
     */
    public static function recordRescheduled(
        Appointment $appointment,
        array $originalData,
        array $newData,
        ?string $reason = null
    ): AppointmentEvent {
        return self::recordEvent(
            appointment: $appointment,
            eventType: self::EVENT_RESCHEDULED,
            payload: [
                'original' => $originalData,
                'new' => $newData,
                'rescheduled_at' => now()->toIso8601String(),
                'reason' => $reason,
            ],
            description: "Cita reagendada" . ($reason ? ": {$reason}" : "")
        );
    }

    /**
     * Registra un evento de cancelación
     */
    public static function recordCancelled(Appointment $appointment, ?string $reason = null): AppointmentEvent
    {
        return self::recordEvent(
            appointment: $appointment,
            eventType: self::EVENT_CANCELLED,
            payload: [
                'status' => $appointment->status->value,
                'cancelled_at' => now()->toIso8601String(),
                'reason' => $reason,
            ],
            description: "Cita cancelada" . ($reason ? ": {$reason}" : "")
        );
    }

    /**
     * Registra un evento de ejecución de cita
     */
    public static function recordExecuted(Appointment $appointment, array $data = []): AppointmentEvent
    {
        return self::recordEvent(
            appointment: $appointment,
            eventType: self::EVENT_EXECUTED,
            payload: array_merge([
                'status' => $appointment->status->value,
                'executed_at' => now()->toIso8601String(),
            ], $data),
            description: "Cita en ejecución"
        );
    }

    /**
     * Registra un evento de completación de cita
     */
    public static function recordCompleted(Appointment $appointment, array $data = []): AppointmentEvent
    {
        return self::recordEvent(
            appointment: $appointment,
            eventType: self::EVENT_COMPLETED,
            payload: array_merge([
                'status' => $appointment->status->value,
                'completed_at' => now()->toIso8601String(),
            ], $data),
            description: "Cita completada"
        );
    }

    /**
     * Registra un evento de no presentación
     */
    public static function recordNoShow(Appointment $appointment, ?string $reason = null): AppointmentEvent
    {
        return self::recordEvent(
            appointment: $appointment,
            eventType: self::EVENT_NO_SHOW,
            payload: [
                'status' => $appointment->status->value,
                'no_show_at' => now()->toIso8601String(),
                'reason' => $reason,
            ],
            description: "Paciente no se presentó" . ($reason ? ": {$reason}" : "")
        );
    }

    /**
     * Registra un evento de creación de reunión Zoom
     */
    public static function recordZoomCreated(Appointment $appointment, array $zoomData): AppointmentEvent
    {
        return self::recordEvent(
            appointment: $appointment,
            eventType: self::EVENT_ZOOM_CREATED,
            payload: [
                'zoom_meeting_id' => $zoomData['meeting_id'] ?? null,
                'zoom_created_at' => now()->toIso8601String(),
            ],
            description: "Reunión Zoom creada"
        );
    }

    /**
     * Registra un evento de actualización de reunión Zoom
     */
    public static function recordZoomUpdated(Appointment $appointment, array $originalData, array $newData): AppointmentEvent
    {
        return self::recordEvent(
            appointment: $appointment,
            eventType: self::EVENT_ZOOM_UPDATED,
            payload: [
                'original' => $originalData,
                'new' => $newData,
                'zoom_updated_at' => now()->toIso8601String(),
            ],
            description: "Reunión Zoom actualizada"
        );
    }

    /**
     * Registra un evento de fallo en Zoom
     */
    public static function recordZoomFailed(Appointment $appointment, string $errorMessage): AppointmentEvent
    {
        return self::recordEvent(
            appointment: $appointment,
            eventType: self::EVENT_ZOOM_FAILED,
            payload: [
                'error' => $errorMessage,
                'failed_at' => now()->toIso8601String(),
            ],
            description: "Error en Zoom: {$errorMessage}"
        );
    }

    /**
     * Registra un evento de procesamiento de pago
     */
    public static function recordPaymentProcessed(Appointment $appointment, array $paymentData): AppointmentEvent
    {
        return self::recordEvent(
            appointment: $appointment,
            eventType: self::EVENT_PAYMENT_PROCESSED,
            payload: [
                'amount' => $paymentData['amount'] ?? null,
                'currency' => $paymentData['currency'] ?? 'COP',
                'payment_method' => $paymentData['method'] ?? null,
                'transaction_id' => $paymentData['transaction_id'] ?? null,
                'processed_at' => now()->toIso8601String(),
            ],
            description: "Pago procesado: {$paymentData['amount']} {$paymentData['currency'] ?? 'COP'}"
        );
    }

    /**
     * Registra un evento de fallo en pago
     */
    public static function recordPaymentFailed(Appointment $appointment, string $errorMessage): AppointmentEvent
    {
        return self::recordEvent(
            appointment: $appointment,
            eventType: self::EVENT_PAYMENT_FAILED,
            payload: [
                'error' => $errorMessage,
                'failed_at' => now()->toIso8601String(),
            ],
            description: "Error en pago: {$errorMessage}"
        );
    }

    /**
     * Método genérico para registrar eventos
     */
    public static function recordEvent(
        Appointment $appointment,
        string $eventType,
        array $payload = [],
        ?string $description = null,
        ?string $userType = null
    ): AppointmentEvent {
        try {
            $user = Auth::user();
            $userType = $userType ?? self::determineUserType($user);

            $event = AppointmentEvent::create([
                'appointment_id' => $appointment->id,
                'event_type' => $eventType,
                'payload' => $payload,
                'metadata' => [
                    'timestamp' => now()->toIso8601String(),
                    'timezone' => config('app.timezone'),
                ],
                'user_id' => $user?->id,
                'user_type' => $userType,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'description' => $description,
            ]);

            Log::info('AppointmentEventSourcing: Evento registrado', [
                'appointment_id' => $appointment->id,
                'event_type' => $eventType,
                'user_id' => $user?->id,
                'user_type' => $userType,
            ]);

            return $event;
        } catch (Throwable $e) {
            Log::error('AppointmentEventSourcing: Error al registrar evento', [
                'appointment_id' => $appointment->id,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Determina el tipo de usuario basado en su rol
     */
    private static function determineUserType($user): string
    {
        if (!$user) {
            return 'system';
        }

        if ($user->hasRole('doctor')) {
            return 'doctor';
        }

        if ($user->hasRole('clinic')) {
            return 'clinic';
        }

        if ($user->hasRole('patient')) {
            return 'patient';
        }

        if ($user->hasRole('admin')) {
            return 'admin';
        }

        return 'unknown';
    }

    /**
     * Obtiene el timeline completo de una cita
     */
    public static function getAppointmentTimeline(int $appointmentId)
    {
        return AppointmentEvent::getAppointmentTimeline($appointmentId);
    }

    /**
     * Reconstruye el estado de una cita en un momento específico
     */
    public static function reconstructState(int $appointmentId, ?\Carbon\Carbon $atTime = null)
    {
        return AppointmentEvent::reconstructAppointmentState($appointmentId, $atTime);
    }

    /**
     * Obtiene estadísticas de eventos
     */
    public static function getEventStats(int $appointmentId): array
    {
        return AppointmentEvent::getAppointmentEventStats($appointmentId);
    }
}
