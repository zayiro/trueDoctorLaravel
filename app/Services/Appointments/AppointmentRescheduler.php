<?php

declare(strict_types=1);

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\Doctor;
use App\Models\DoctorSetting;
use App\Models\User;
use App\Notifications\AppointmentRescheduledNotification;
use App\Services\AppointmentEventSourcing;
use App\Services\Availability\AvailabilityQuery;
use App\Services\Availability\AvailabilityResolver;
use App\Services\Availability\Slot;
use App\Services\Availability\SlotNotAvailableException;
use Carbon\Carbon;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ÚNICA puerta para reprogramar una cita. La usan el paciente, el doctor, la clínica y el admin.
 *
 * Los controladores solo autentican, validan el formulario y llaman a esta clase.
 *
 * Reglas comunes: solo citas activas, una cita virtual sin pagar no se reprograma, el nuevo horario no
 * puede estar en el pasado, y el turno se valida y se guarda en la misma transacción (doctor bloqueado).
 *
 * Reglas del paciente: lo permite el doctor/clínica, límite de reprogramaciones, anticipación mínima
 * (cancellation_notice_hours) y la anticipación/horizonte normales de la agenda. Puede quedar pendiente de aprobación.
 *
 * Reglas del personal (doctor, clínica, admin): modo `staffBooking` (sin anticipación mínima ni horizonte);
 * la cita queda confirmada. Choques, bloqueos, festivos, buffer y cupo diario aplican siempre.
 */
final class AppointmentRescheduler
{
    public const MAX_PATIENT_RESCHEDULES = 2;

    public const ACTOR_PATIENT = 'patient';
    public const ACTOR_DOCTOR = 'doctor';
    public const ACTOR_CLINIC = 'clinic';
    public const ACTOR_ADMIN = 'admin';

    public function __construct(private readonly AvailabilityResolver $resolver)
    {
    }

    /**
     * Turnos de un día para reprogramar esta cita, con las reglas del actor.
     * Para que paciente y personal pinten exactamente lo que luego se va a validar.
     *
     * @return Slot[]
     *
     * @throws AuthorizationException
     */
    public function slotsFor(Appointment $appointment, User $user, string $date): array
    {
        $actor = $this->resolveActor($appointment, $user);

        return $this->resolver->slotsForDay(
            $this->query($appointment, $actor !== self::ACTOR_PATIENT),
            Carbon::parse($date, $this->tz())->startOfDay()
        );
    }

    /**
     * @param string $newDate 'Y-m-d'
     * @param string $newTime 'H:i' o 'H:i:s'
     *
     * @throws AuthorizationException si el usuario no tiene permiso sobre la cita.
     */
    public function reschedule(Appointment $appointment, User $user, string $newDate, string $newTime): RescheduleResult
    {
        $actor = $this->resolveActor($appointment, $user);
        $isPatient = $actor === self::ACTOR_PATIENT;

        try {
            $startAt = Carbon::parse("{$newDate} {$newTime}", $this->tz());
        } catch (Throwable) {
            return RescheduleResult::failure('La fecha u hora seleccionada no es válida.');
        }

        $settings = $this->settingsFor($appointment);

        if ($error = $this->businessError($appointment, $actor, $settings, $startAt)) {
            return RescheduleResult::failure($error);
        }

        $newStart = $startAt->format('H:i:s');
        $newEnd = $startAt->copy()->addMinutes((int) $appointment->duration)->format('H:i:s');
        $date = $startAt->format('Y-m-d');

        $original = [
            'date' => $appointment->date instanceof \DateTimeInterface ? $appointment->date->format('Y-m-d') : (string) $appointment->date,
            'start_time' => (string) $appointment->start_time,
        ];

        $requiresApproval = $isPatient && $this->requiresApproval($settings);

        try {
            $this->resolver->withBookableSlot(
                $this->query($appointment, !$isPatient),
                $startAt,
                function () use ($appointment, $actor, $isPatient, $date, $newStart, $newEnd, $requiresApproval) {
                    // Con el doctor bloqueado se relee la cita: dos reprogramaciones simultáneas no se pisan.
                    $fresh = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();

                    $status = $fresh->status instanceof \BackedEnum ? $fresh->status->value : (string) $fresh->status;
                    if (!in_array($status, ['pending', 'confirmed'], true)) {
                        throw new DomainException('Esta cita ya no se puede reprogramar.');
                    }

                    $count = (int) ($fresh->reschedule_count ?? 0);
                    if ($isPatient && $count >= self::MAX_PATIENT_RESCHEDULES) {
                        throw new DomainException($this->limitMessage());
                    }

                    $changes = [
                        'date' => $date,
                        'start_time' => $newStart,
                        'end_time' => $newEnd,
                        'status' => $requiresApproval ? 'pending' : 'confirmed',
                        'email_sent' => false, // AppointmentObserver envía el correo de reprogramación
                    ];

                    if ($isPatient) {
                        $changes['reschedule_count'] = $count + 1;
                    }

                    $fresh->update($changes);
                    $appointment->setRawAttributes($fresh->getAttributes(), true);
                }
            );
        } catch (SlotNotAvailableException $e) {
            return RescheduleResult::failure($e->getMessage());
        } catch (DomainException $e) {
            return RescheduleResult::failure($e->getMessage());
        }

        $this->afterReschedule($appointment, $actor, $original, $date, $newStart);

        if ($requiresApproval) {
            return RescheduleResult::success('Tu solicitud de cambio fue enviada y está sujeta a la aprobación de la clínica.', true);
        }

        return RescheduleResult::success(
            $isPatient ? 'Tu cita ha sido reprogramada exitosamente.' : 'La consulta médica ha sido reprogramada y confirmada exitosamente.'
        );
    }

    // ------------------------------------------------------------------ reglas

    private function businessError(Appointment $appointment, string $actor, mixed $settings, Carbon $startAt): ?string
    {
        $isPatient = $actor === self::ACTOR_PATIENT;
        $status = $appointment->status instanceof \BackedEnum ? $appointment->status->value : (string) $appointment->status;

        if (!in_array($status, ['pending', 'confirmed'], true)) {
            return 'Solo se pueden reprogramar citas pendientes o confirmadas.';
        }

        if ($appointment->isAwaitingPayment()) {
            return $isPatient
                ? 'Debes completar el pago de esta cita virtual antes de poder reprogramarla.'
                : 'El paciente aún no ha pagado esta cita virtual; no se puede reprogramar hasta que se confirme el pago.';
        }

        if ($startAt->isPast()) {
            return 'La nueva fecha y hora ya pasaron. Selecciona un horario futuro.';
        }

        if (!$isPatient) {
            return null;
        }

        if ((int) ($appointment->reschedule_count ?? 0) >= self::MAX_PATIENT_RESCHEDULES) {
            return $this->limitMessage();
        }

        if (!$settings) {
            return 'La configuración de la agenda médica no está disponible.';
        }

        if (!$settings->allow_patient_rescheduling) {
            return 'Este profesional o centro médico no permite reprogramar citas en línea.';
        }

        $noticeHours = (int) ($settings->cancellation_notice_hours ?? 2);
        $current = Carbon::parse(
            Carbon::parse($appointment->date)->format('Y-m-d') . ' ' . $appointment->start_time,
            $this->tz()
        );

        if (Carbon::now($this->tz())->diffInHours($current, false) < $noticeHours) {
            return "Debes reprogramar con al menos {$noticeHours} horas de anticipación.";
        }

        return null;
    }

    private function limitMessage(): string
    {
        return 'Has alcanzado el límite máximo de reprogramaciones permitidas para esta cita. Por favor, contacta con soporte.';
    }

    /** La aprobación manual de citas ya no existe: una reprogramación siempre queda confirmada. */
    private function requiresApproval(mixed $settings): bool
    {
        if (!$settings) {
            return false;
        }

        return false; // La aprobación manual de citas ya no existe.
    }

    private function settingsFor(Appointment $appointment): mixed
    {
        return $appointment->clinic_id
            ? ClinicSetting::where('clinic_id', $appointment->clinic_id)->first()
            : DoctorSetting::where('doctor_id', $appointment->doctor_id)->first();
    }

    private function query(Appointment $appointment, bool $staff): AvailabilityQuery
    {
        return new AvailabilityQuery(
            doctorIds: [(int) $appointment->doctor_id],
            clinicId: $appointment->clinic_id ? (int) $appointment->clinic_id : null,
            addressId: $appointment->address_id ? (int) $appointment->address_id : null,
            serviceId: $appointment->service_id ? (int) $appointment->service_id : null,
            excludeAppointmentId: (int) $appointment->id,
            staffBooking: $staff,
        );
    }

    // ------------------------------------------------------------ autorización

    /**
     * Quién es el usuario respecto a esta cita. Lanza AuthorizationException si no tiene permiso.
     */
    private function resolveActor(Appointment $appointment, User $user): string
    {
        $role = (string) $user->role;

        if ($role === 'admin' || (method_exists($user, 'hasRole') && $user->hasRole('admin'))) {
            return self::ACTOR_ADMIN;
        }

        if ($role === 'patient') {
            $patientId = $user->patient?->id;
            if ($patientId && (int) $appointment->patient_id === (int) $patientId) {
                return self::ACTOR_PATIENT;
            }
            throw new AuthorizationException('No autorizado.');
        }

        if ($role === 'clinic') {
            $clinicId = $user->clinic?->id;
            if ($clinicId && $appointment->clinic_id !== null && (int) $appointment->clinic_id === (int) $clinicId) {
                return self::ACTOR_CLINIC;
            }
            throw new AuthorizationException('No autorizado. Esta cita no pertenece a tu centro clínico.');
        }

        if ($role === 'doctor') {
            $doctorId = $user->doctor?->id;
            $context = session('doctor_context');
            $type = $context['type'] ?? null;

            if ($type === 'clinic') {
                $allowed = $appointment->clinic_id !== null && (int) $appointment->clinic_id === (int) ($context['id'] ?? 0);
            } elseif ($type === 'particular') {
                $allowed = $doctorId && (int) $appointment->doctor_id === (int) $doctorId && $appointment->clinic_id === null;
            } else {
                $allowed = $doctorId && (int) $appointment->doctor_id === (int) $doctorId;
            }

            if ($allowed) {
                return self::ACTOR_DOCTOR;
            }
            throw new AuthorizationException('No autorizado. Esta cita pertenece a otra agenda médica.');
        }

        throw new AuthorizationException('No tienes permiso para reprogramar citas.');
    }

    // ----------------------------------------------------------- efectos finales

    /**
     * Auditoría y avisos. Ninguno puede romper la reprogramación (la cita ya cambió).
     * El correo al paciente lo envía AppointmentObserver (evento AppointmentRescheduled);
     * la sincronización de Zoom y la invalidación de caché también salen del observer.
     */
    private function afterReschedule(Appointment $appointment, string $actor, array $original, string $date, string $newStart): void
    {
        try {
            AppointmentEventSourcing::recordRescheduled(
                $appointment,
                $original,
                ['date' => $date, 'start_time' => $newStart],
                "Reprogramada por {$actor}"
            );
        } catch (Throwable $e) {
            Log::warning('AppointmentRescheduler: no se pudo registrar la auditoría', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Si reprograma el paciente, se avisa también al doctor (en el panel). Si reprograma el personal, el paciente ya recibe el correo del observer.
        if ($actor === self::ACTOR_PATIENT) {
            try {
                $doctorUserId = Doctor::whereKey($appointment->doctor_id)->value('user_id');
                $doctorUser = $doctorUserId ? User::find($doctorUserId) : null;
                $doctorUser?->notify(new AppointmentRescheduledNotification($appointment, 'patient'));
            } catch (Throwable $e) {
                Log::warning('AppointmentRescheduler: no se pudo notificar al doctor', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function tz(): string
    {
        return config('booking.timezone', 'America/Bogota');
    }
}
