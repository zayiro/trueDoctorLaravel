<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * 🔄 PATRÓN SAGA: Mecanismo de compensación automática para fallos de Zoom
 * 
 * Cuando la API de Zoom falla, este servicio ejecuta transacciones de compensación
 * para mantener la integridad de datos sin romper el flujo de la aplicación.
 */
class ZoomSagaCompensation
{
    /**
     * Compensa el fallo de creación de reunión Zoom
     * Marca la cita con estado de error y libera el slot reservado
     */
    public static function compensateCreateMeetingFailure(Appointment $appointment, string $errorMessage): void
    {
        try {
            DB::transaction(function () use ($appointment, $errorMessage) {
                // 1. Marcar la cita con estado de error específico
                $appointment->update([
                    'status' => 'zoom_creation_failed',
                    'notes' => ($appointment->notes ?? '') . "\n[ZOOM ERROR] " . $errorMessage . " [" . now()->format('Y-m-d H:i:s') . "]"
                ]);

                // 2. Registrar el fallo en la tabla de auditoría
                DB::table('zoom_creation_failures')->updateOrInsert(
                    ['appointment_id' => $appointment->id],
                    [
                        'attempts' => DB::raw('COALESCE(attempts, 0) + 1'),
                        'status' => 'failed',
                        'last_error' => $errorMessage,
                        'updated_at' => now(),
                        'created_at' => now()
                    ]
                );

                // 3. Liberar el slot en la tabla de bloques de horario (si existe)
                if ($appointment->address_id && $appointment->doctor_id) {
                    DB::table('schedule_blocks')
                        ->where('appointment_id', $appointment->id)
                        ->delete();
                }

                Log::warning('ZoomSagaCompensation: Fallo de creación compensado', [
                    'appointment_id' => $appointment->id,
                    'error' => $errorMessage,
                    'timestamp' => now()
                ]);
            });
        } catch (Throwable $e) {
            Log::error('ZoomSagaCompensation: Error durante compensación de creación', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Compensa el fallo de actualización/reagendamiento de reunión Zoom
     * Revierte la cita al estado anterior y notifica al usuario
     */
    public static function compensateUpdateMeetingFailure(Appointment $appointment, string $errorMessage, array $originalData = []): void
    {
        try {
            DB::transaction(function () use ($appointment, $errorMessage, $originalData) {
                // 1. Revertir los datos de la cita al estado anterior si se proporcionan
                if (!empty($originalData)) {
                    $appointment->update($originalData);
                }

                // 2. Marcar con estado de error de reagendamiento
                $appointment->update([
                    'status' => 'zoom_update_failed',
                    'notes' => ($appointment->notes ?? '') . "\n[ZOOM RESCHEDULE ERROR] " . $errorMessage . " [" . now()->format('Y-m-d H:i:s') . "]"
                ]);

                // 3. Registrar el intento fallido
                DB::table('zoom_creation_failures')->updateOrInsert(
                    ['appointment_id' => $appointment->id],
                    [
                        'attempts' => DB::raw('COALESCE(attempts, 0) + 1'),
                        'status' => 'update_failed',
                        'last_error' => $errorMessage,
                        'updated_at' => now(),
                        'created_at' => now()
                    ]
                );

                Log::warning('ZoomSagaCompensation: Fallo de actualización compensado', [
                    'appointment_id' => $appointment->id,
                    'error' => $errorMessage,
                    'original_data' => $originalData,
                    'timestamp' => now()
                ]);
            });
        } catch (Throwable $e) {
            Log::error('ZoomSagaCompensation: Error durante compensación de actualización', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Compensa el fallo de eliminación de reunión Zoom
     * Mantiene la cita en estado de error pero registra el intento fallido
     */
    public static function compensateDeleteMeetingFailure(Appointment $appointment, string $errorMessage): void
    {
        try {
            DB::transaction(function () use ($appointment, $errorMessage) {
                // 1. Marcar con estado de error de eliminación
                $appointment->update([
                    'status' => 'zoom_delete_failed',
                    'notes' => ($appointment->notes ?? '') . "\n[ZOOM DELETE ERROR] " . $errorMessage . " [" . now()->format('Y-m-d H:i:s') . "]"
                ]);

                // 2. Registrar el fallo para reintento manual
                DB::table('zoom_creation_failures')->updateOrInsert(
                    ['appointment_id' => $appointment->id],
                    [
                        'attempts' => DB::raw('COALESCE(attempts, 0) + 1'),
                        'status' => 'delete_failed',
                        'last_error' => $errorMessage,
                        'updated_at' => now(),
                        'created_at' => now()
                    ]
                );

                Log::warning('ZoomSagaCompensation: Fallo de eliminación compensado', [
                    'appointment_id' => $appointment->id,
                    'error' => $errorMessage,
                    'timestamp' => now()
                ]);
            });
        } catch (Throwable $e) {
            Log::error('ZoomSagaCompensation: Error durante compensación de eliminación', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Compensa el fallo de cierre de reunión en vivo
     * Registra el intento fallido pero mantiene la cita activa
     */
    public static function compensateEndMeetingFailure(Appointment $appointment, string $errorMessage): void
    {
        try {
            DB::transaction(function () use ($appointment, $errorMessage) {
                // 1. Registrar el fallo de cierre
                DB::table('zoom_creation_failures')->updateOrInsert(
                    ['appointment_id' => $appointment->id],
                    [
                        'attempts' => DB::raw('COALESCE(attempts, 0) + 1'),
                        'status' => 'end_failed',
                        'last_error' => $errorMessage,
                        'updated_at' => now(),
                        'created_at' => now()
                    ]
                );

                // 2. Agregar nota a la cita
                $appointment->update([
                    'notes' => ($appointment->notes ?? '') . "\n[ZOOM END ERROR] " . $errorMessage . " [" . now()->format('Y-m-d H:i:s') . "]"
                ]);

                Log::warning('ZoomSagaCompensation: Fallo de cierre compensado', [
                    'appointment_id' => $appointment->id,
                    'error' => $errorMessage,
                    'timestamp' => now()
                ]);
            });
        } catch (Throwable $e) {
            Log::error('ZoomSagaCompensation: Error durante compensación de cierre', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Obtiene el estado de compensación de una cita
     * Útil para dashboards y auditoría
     */
    public static function getCompensationStatus(Appointment $appointment): ?array
    {
        $failure = DB::table('zoom_creation_failures')
            ->where('appointment_id', $appointment->id)
            ->first();

        if (!$failure) {
            return null;
        }

        return [
            'status' => $failure->status,
            'attempts' => $failure->attempts,
            'last_error' => $failure->last_error,
            'last_updated' => $failure->updated_at,
            'created_at' => $failure->created_at
        ];
    }
}
