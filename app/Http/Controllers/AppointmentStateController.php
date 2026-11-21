<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Notifications\AppointmentStatusChangedNotification;
use App\Services\AppointmentStateManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AppointmentStateController extends Controller
{
    /**
     * Etiquetas legibles de roles para auditoría.
     */
    private const ROLE_LABELS = [
        'clinic'  => 'Clínica',
        'doctor'  => 'Especialista',
        'patient' => 'Paciente',
        'admin'   => 'Administrador',
    ];

    /**
     * Cambio de estado de una cita desde la interfaz.
     *
     * La validación de la transición, la auditoría y los eventos (Zoom, correos, caché de disponibilidad)
     * viven en AppointmentStateManager, que es la ÚNICA puerta para cambiar el estado de una cita.
     * Este controlador solo comprueba QUIÉN puede pedir el cambio.
     */
    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        $validated = $request->validate([
            'status'             => ['required', Rule::in(['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])],
            'cancellation_notes' => ['nullable', 'string', 'max:500'],
        ], [
            'status.required' => 'El estado de la cita es un campo obligatorio.',
            'status.in'       => 'El estado seleccionado no es válido.',
        ]);

        $user = Auth::user();
        abort_unless($user, 401, 'Debes iniciar sesión.');

        $newStatus = $validated['status'];

        // 🔒 Primero quién eres; solo después se revela algo del estado de la cita.
        $this->validateRolePermissions($user, $appointment, $newStatus);

        $fromStatus = $appointment->status instanceof \BackedEnum ? $appointment->status->value : (string) $appointment->status;
        $reason = $newStatus === 'cancelled' && !empty($validated['cancellation_notes'])
            ? trim($validated['cancellation_notes'])
            : null;

        $additional = [];
        $notes = $this->buildAuditNotes($appointment, $newStatus, $validated, $user);
        if ($notes !== $appointment->notes) {
            $additional['notes'] = $notes;
        }

        try {
            app(AppointmentStateManager::class)->transitionTo(
                $appointment,
                $newStatus,
                $reason ?? ('Cambio de estado por ' . (self::ROLE_LABELS[$user->role] ?? 'usuario')),
                $additional,
                $user->role === 'patient' ? 'patient' : 'doctor'
            );
        } catch (\InvalidArgumentException $e) {
            // Mensaje apto para mostrar (transición no permitida, falta de pago, cita que no ha comenzado…).
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('AppointmentStateController: Error al actualizar estado', [
                'appointment_id' => $appointment->id,
                'error'          => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Ocurrió un error al actualizar el estado de la cita. Por favor, intenta nuevamente.');
        }

        $this->notifyPatient($appointment->fresh(), $newStatus, $user);

        Log::info('AppointmentStateController: Estado actualizado', [
            'appointment_id' => $appointment->id,
            'reference'      => $appointment->reference,
            'from'           => $fromStatus,
            'to'             => $newStatus,
            'user_id'        => $user->id,
            'user_role'      => $user->role,
        ]);

        return $this->buildSuccessResponse($user, $appointment, $newStatus);
    }

    /**
     * Quién puede pedir el cambio:
     *  - paciente: solo cancelar SU cita;
     *  - doctor: solo las citas donde él es el médico;
     *  - clínica: las citas de su clínica (aunque el médico ya no esté activo: justo entonces hay que poder cancelarlas);
     *  - admin: todas.
     */
    private function validateRolePermissions($user, Appointment $appointment, string $newStatus): void
    {
        switch ($user->role) {
            case 'patient':
                if (!$user->patient || (int) $appointment->patient_id !== (int) $user->patient->id) {
                    abort(403, 'Acción no autorizada. No eres el propietario de esta reserva.');
                }
                if ($newStatus !== 'cancelled') {
                    abort(403, 'Los pacientes únicamente tienen permitido cancelar citas.');
                }
                return;

            case 'doctor':
                if (!$user->doctor || (int) $appointment->doctor_id !== (int) $user->doctor->id) {
                    abort(403, 'Acción no autorizada. No tienes permisos sobre esta cita.');
                }
                return;

            case 'clinic':
                if (!$user->clinic || (int) $appointment->clinic_id !== (int) $user->clinic->id) {
                    abort(403, 'Acción no autorizada. Esta cita no pertenece a tu centro médico.');
                }
                return;

            case 'admin':
                return;

            default:
                abort(403, 'Rol no autorizado para modificar estados médicos.');
        }
    }

    /**
     * Construye las notas de auditoría con el formato estandarizado.
     * Maneja el caso donde $appointment->notes es null.
     */
    private function buildAuditNotes(Appointment $appointment, string $newStatus, array $validated, $user): ?string
    {
        $notes = $appointment->notes ?? '';

        if ($newStatus === 'cancelled' && !empty($validated['cancellation_notes'])) {
            $roleLabel = self::ROLE_LABELS[$user->role] ?? 'Usuario';
            $timestamp = now()->toDateTimeString();
            $reason = trim($validated['cancellation_notes']);

            return $notes . "\n[Cancelado por {$roleLabel} el {$timestamp}]: {$reason}";
        }

        return $notes !== '' ? $notes : null;
    }

    /**
     * Notifica al paciente sobre el cambio de estado de su cita.
     */
    private function notifyPatient(Appointment $appointment, string $newStatus, $user): void
    {
        try {
            $patient = $appointment->patient;

            if (!$patient || !$patient->user) {
                return;
            }

            // Solo notificar si el cambio fue hecho por el proveedor (no por el propio paciente)
            if ($user->role === 'patient') {
                return;
            }

            $patient->user->notify(new AppointmentStatusChangedNotification($appointment, $newStatus, $user));
        } catch (\Throwable $e) {
            Log::error('AppointmentStateController: Error al notificar al paciente', [
                'appointment_id' => $appointment->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    /**
     * Construye la respuesta de éxito adaptativa según el rol.
     */
    private function buildSuccessResponse($user, Appointment $appointment, string $newStatus): RedirectResponse
    {
        $statusLabels = [
            'pending'   => 'pendiente',
            'confirmed' => 'confirmada',
            'completed' => 'completada',
            'cancelled' => 'cancelada',
            'no_show'   => 'marcada como no asistida',
        ];

        $label = $statusLabels[$newStatus] ?? $newStatus;
        $successMessage = "El estado de la reservación con referencia {$appointment->reference} ha sido {$label} correctamente.";

        if ($user->role === 'clinic') {
            return redirect()->route('partner.clinic.appointments.index')
                ->with('success', $successMessage);
        }

        return redirect()->back()->with('success', $successMessage);
    }
}
