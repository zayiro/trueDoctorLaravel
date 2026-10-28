<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Address;
use App\Events\AppointmentCancelled;
use App\Notifications\AppointmentStatusChangedNotification;
use App\Repositories\AvailabilityRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AppointmentStateController extends Controller
{
    /**
     * Transiciones de estado permitidas.
     * Previene mutaciones ilegales (ej: de cancelled a confirmed).
     */
    private const ALLOWED_TRANSITIONS = [
        'pending'   => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled', 'no_show'],
        'completed' => [], // Estado terminal
        'cancelled' => [], // Estado terminal
        'no_show'   => ['confirmed'], // Permite reactivar si fue error
    ];

    /**
     * Etiquetas legibles de roles para auditoría.
     */
    private const ROLE_LABELS = [
        'clinic'  => 'Clínica',
        'doctor'  => 'Especialista',
        'patient' => 'Paciente',
        'admin'   => 'Administrador',
    ];

    private AvailabilityRepository $cacheRepo;

    public function __construct(AvailabilityRepository $cacheRepo)
    {
        $this->cacheRepo = $cacheRepo;
    }

    /**
     * Controlador universal de estados.
     * El procesamiento de Zoom y correos se delega al AppointmentObserver y eventos.
     * 
     *  CORRECCIONES:
     * - Invalida caché de disponibilidad al cambiar estado
     * - Dispara evento AppointmentCancelled cuando aplica
     * - Notifica al paciente sobre cambios de estado
     * - Valida transiciones de estado permitidas
     * - Logging de auditoría
     */
    public function updateStatus(Request $request, Appointment $appointment): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'status'             => ['required', Rule::in(['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])],
            'cancellation_notes' => ['nullable', 'string', 'max:500'],
        ], [
            'status.required' => 'El estado de la cita es un campo obligatorio.',
            'status.in'       => 'El estado seleccionado no es válido.',
        ]);

        $user = Auth::user();
        $newStatus = $validated['status'];

        // 🔒 VALIDACIÓN 1: No modificar citas en estados terminales
        if (in_array($appointment->status, ['cancelled', 'completed'], true)) {
            return redirect()->back()->with('error', 
                "No puedes modificar una cita que ya se encuentra en estado '{$appointment->status}'.");
        }

        //  VALIDACIÓN 2: Transición de estado permitida
        $allowedTransitions = self::ALLOWED_TRANSITIONS[$appointment->status] ?? [];
        if (!in_array($newStatus, $allowedTransitions, true)) {
            Log::warning('AppointmentStateController: Transición de estado no permitida', [
                'appointment_id' => $appointment->id,
                'from' => $appointment->status,
                'to' => $newStatus,
                'user_id' => $user->id,
            ]);
            return redirect()->back()->with('error', 
                "No es posible cambiar el estado de '{$appointment->status}' a '{$newStatus}'.");
        }

        // 🔒 VALIDACIÓN 3: Permisos por rol
        $this->validateRolePermissions($user, $appointment, $newStatus);

        // 🔒 EJECUCIÓN ATÓMICA
        try {
            DB::transaction(function () use ($appointment, $newStatus, $validated, $user) {
                $notes = $this->buildAuditNotes($appointment, $newStatus, $validated, $user);

                $appointment->update([
                    'status' => $newStatus,
                    'notes'  => $notes,
                ]);

                // 🔥 DISPARAR EVENTO si es cancelación (libera Zoom, notificaciones asíncronas)
                if ($newStatus === 'cancelled') {
                    event(new AppointmentCancelled($appointment));
                }
            });

            // 🔥 INVALIDAR CACHÉ DE DISPONIBILIDAD
            // Crucial: al cancelar/completar una cita, el turno debe volver a estar disponible
            $this->invalidateAppointmentCache($appointment);

            //  NOTIFICAR AL PACIENTE sobre el cambio de estado
            $this->notifyPatient($appointment, $newStatus, $user);

            // 📝 LOGGING DE AUDITORÍA
            Log::info('AppointmentStateController: Estado actualizado', [
                'appointment_id' => $appointment->id,
                'reference' => $appointment->reference,
                'from' => $appointment->getOriginal('status'),
                'to' => $newStatus,
                'user_id' => $user->id,
                'user_role' => $user->role,
            ]);

            // 🎯 REDIRECCIÓN ADAPTATIVA
            return $this->buildSuccessResponse($user, $appointment, $newStatus);
        } catch (\Exception $e) {
            Log::error('AppointmentStateController: Error al actualizar estado', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 
                'Ocurrió un error al actualizar el estado de la cita. Por favor, intenta nuevamente.');
        }
    }

    /**
     * Valida los permisos según el rol del usuario autenticado.
     */
    private function validateRolePermissions($user, Appointment $appointment, string $newStatus): void
    {
        if ($user->role === 'patient') {
            // Un paciente SOLO puede cancelar su propia cita
            if ($appointment->patient_id !== $user->patient?->id) {
                abort(403, 'Acción no autorizada. No eres el propietario de esta reserva.');
            }
            if ($newStatus !== 'cancelled') {
                abort(403, 'Los pacientes únicamente tienen permitido cancelar citas.');
            }
            return;
        }

        if (in_array($user->role, ['doctor', 'clinic'], true)) {
            $this->validateProviderPermissions($user, $appointment);
            return;
        }

        if ($user->role !== 'admin') {
            abort(403, 'Rol no autorizado para modificar estados médicos.');
        }
    }

    /**
     * Valida que el proveedor (doctor/clínica) tenga permisos sobre la cita.
     */
    private function validateProviderPermissions($user, Appointment $appointment): void
    {
        // Cargar la sede con Eloquent en lugar de DB::table
        $address = Address::find($appointment->address_id);
        
        if (!$address) {
            abort(404, 'La sede vinculada a la cita médica no existe en el sistema.');
        }

        if ($user->role === 'clinic') {
            // La sede debe pertenecer a la clínica
            if ($address->clinic_id !== $user->clinic?->id) {
                abort(403, 'Acción no autorizada. Esta sede no pertenece a tu centro médico.');
            }
            
            // 🔒 CORRECCIÓN: Validar que el doctor esté aprobado en la clínica
            $isDoctorApproved = DB::table('clinic_doctor')
                ->where('clinic_id', $user->clinic->id)
                ->where('doctor_id', $appointment->doctor_id)
                ->where('status', 'approved')
                ->exists();
            
            if (!$isDoctorApproved) {
                abort(403, 'El especialista de esta cita no está aprobado en tu clínica.');
            }
            return;
        }

        if ($user->role === 'doctor') {
            // El doctor debe ser el dueño de la cita O atender en una sede de la clínica donde está aprobado
            $isOwner = $appointment->doctor_id === $user->doctor?->id;
            
            if (!$isOwner) {
                // Verificar si atiende en la sede de la clínica
                $isStaffInClinic = $address->clinic_id !== null 
                    && DB::table('clinic_doctor')
                        ->where('clinic_id', $address->clinic_id)
                        ->where('doctor_id', $user->doctor->id)
                        ->where('status', 'approved')
                        ->exists();
                
                if (!$isStaffInClinic) {
                    abort(403, 'Acción no autorizada. No tienes permisos sobre esta cita.');
                }
            }
            return;
        }
    }

    /**
     * Construye las notas de auditoría con el formato estandarizado.
     * 🔒 CORRECCIÓN: Maneja el caso donde $appointment->notes es null.
     */
    private function buildAuditNotes(Appointment $appointment, string $newStatus, array $validated, $user): ?string
    {
        $notes = $appointment->notes ?? '';
        
        if ($newStatus === 'cancelled' && !empty($validated['cancellation_notes'])) {
            $roleLabel = self::ROLE_LABELS[$user->role] ?? 'Usuario';
            $timestamp = now()->toDateTimeString();
            $reason = trim($validated['cancellation_notes']);
            
            $auditEntry = "\n[Cancelado por {$roleLabel} el {$timestamp}]: {$reason}";
            
            // Evitar duplicados si ya existe una nota similar
            if (str_contains($notes, $auditEntry)) {
                return $notes;
            }
            
            return $notes . $auditEntry;
        }

        return $notes ?: null;
    }

    /**
     * Invalida el caché de disponibilidad para la sede de la cita.
     * 🔒 CRÍTICO: Sin esto, los turnos cancelados siguen apareciendo como ocupados.
     */
    private function invalidateAppointmentCache(Appointment $appointment): void
    {
        try {
            $addressId = $appointment->address_id;
            $doctorId = $appointment->doctor_id;
            $clinicId = $appointment->clinic_id;

            if ($addressId && $doctorId) {
                $this->cacheRepo->invalidateAvailabilityCache(
                    $clinicId,
                    [$doctorId],
                    $addressId
                );
            }
        } catch (\Exception $e) {
            // No romper el flujo si falla la invalidación de caché
            Log::error('AppointmentStateController: Error al invalidar caché', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
        }
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
        } catch (\Exception $e) {
            Log::error('AppointmentStateController: Error al notificar al paciente', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Construye la respuesta de éxito adaptativa según el rol.
     */
    private function buildSuccessResponse($user, Appointment $appointment, string $newStatus): \Illuminate\Http\RedirectResponse
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