<?php

declare(strict_types=1);

namespace App\Services\Appointments;

use App\Mail\AppointmentDoctorChangedMail;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use App\Services\AppointmentEventSourcing;
use App\Services\Availability\AvailabilityQuery;
use App\Services\Availability\AvailabilityResolver;
use App\Services\Availability\SlotNotAvailableException;
use App\Services\Availability\SlotState;
use Carbon\Carbon;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * ÚNICA puerta para reasignar una cita de clínica a otro médico de la misma clínica.
 *
 * La fecha, la hora, la sede, el servicio y el precio NO cambian: solo cambia quién atiende.
 * El turno del médico nuevo se valida y se guarda en la misma transacción (con ese médico bloqueado),
 * igual que en AppointmentRescheduler, así dos personas no pueden ocupar el mismo turno.
 *
 * Zoom: no se toca. La reunión sigue siendo la misma; la sala se autoriza por doctor_id, así que el médico
 * nuevo puede entrar y el anterior deja de poder. AppointmentObserver solo reacciona a cambios de fecha/hora/estado.
 *
 * Correos: paciente, médico anterior y médico nuevo (en cola). La auditoría queda en appointment_events.
 */
final class AppointmentDoctorChanger
{
    public const EVENT_DOCTOR_CHANGED = 'doctor_changed';

    public function __construct(private readonly AvailabilityResolver $resolver)
    {
    }

    /**
     * Datos para el modal: la cita y los médicos de la clínica con su disponibilidad a esa misma hora.
     *
     * @throws AuthorizationException
     */
    public function options(Appointment $appointment, User $user): array
    {
        $this->authorize($appointment, $user);
        $appointment->loadMissing(['doctor.user', 'service', 'address']);

        $startAt = $this->startAt($appointment);

        $data = [
            'reference'      => (string) $appointment->reference,
            'current_doctor' => (string) ($appointment->doctor?->user?->name ?? 'Sin asignar'),
            'date_label'     => $startAt->translatedFormat('l d \d\e F, Y'),
            'time_label'     => $startAt->format('g:i A'),
            'can_change'     => true,
            'message'        => null,
            'doctors'        => [],
        ];

        if ($error = $this->stateError($appointment, $startAt)) {
            $data['can_change'] = false;
            $data['message'] = $error;

            return $data;
        }

        $list = [];
        foreach ($this->eligibleDoctors($appointment) as $doctor) {
            if ((int) $doctor->id === (int) $appointment->doctor_id) {
                continue;
            }

            [$available, $reason] = $this->availability($appointment, (int) $doctor->id, $startAt);

            $list[] = [
                'id'        => (int) $doctor->id,
                'name'      => (string) ($doctor->user?->name ?? 'Sin nombre'),
                'available' => $available,
                'reason'    => $reason,
            ];
        }

        // Primero los que pueden tomar la cita, luego por nombre.
        usort($list, fn ($a, $b) => [$b['available'], $a['name']] <=> [$a['available'], $b['name']]);

        $data['doctors'] = $list;

        return $data;
    }

    /**
     * @throws AuthorizationException si el usuario no tiene permiso sobre la cita.
     */
    public function change(Appointment $appointment, User $user, int $newDoctorId, ?string $reason = null): ChangeDoctorResult
    {
        $this->authorize($appointment, $user);
        $appointment->loadMissing(['doctor.user']);

        $startAt = $this->startAt($appointment);
        $reason = $reason !== null ? trim($reason) : null;
        $reason = $reason === '' ? null : $reason;

        if ($error = $this->stateError($appointment, $startAt)) {
            return ChangeDoctorResult::failure($error);
        }

        $previousDoctorId = (int) $appointment->doctor_id;

        if ($newDoctorId === $previousDoctorId) {
            return ChangeDoctorResult::failure('La cita ya está asignada a ese médico.');
        }

        if (!$this->eligibleDoctors($appointment)->contains('id', $newDoctorId)) {
            return ChangeDoctorResult::failure('El médico seleccionado no hace parte de la clínica o no está activo.');
        }

        $previousDoctorName = (string) ($appointment->doctor?->user?->name ?? 'el médico anterior');

        try {
            $this->resolver->withBookableSlot(
                $this->query($appointment, $newDoctorId),
                $startAt,
                function () use ($appointment, $previousDoctorId, $newDoctorId) {
                    // Con el médico nuevo bloqueado se relee la cita: dos cambios simultáneos no se pisan.
                    $fresh = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();

                    if (!in_array($this->statusValue($fresh), ['pending', 'confirmed'], true)) {
                        throw new DomainException('Esta cita ya no se puede modificar.');
                    }

                    if ((int) $fresh->doctor_id !== $previousDoctorId) {
                        throw new DomainException('Esta cita acaba de ser modificada por otra persona. Recarga la agenda.');
                    }

                    $fresh->forceFill(['doctor_id' => $newDoctorId])->save();
                    $appointment->setRawAttributes($fresh->getAttributes(), true);
                }
            );
        } catch (SlotNotAvailableException $e) {
            return ChangeDoctorResult::failure('Ese médico ya no tiene libre este horario: ' . $e->getMessage());
        } catch (DomainException $e) {
            return ChangeDoctorResult::failure($e->getMessage());
        }

        $appointment->unsetRelation('doctor');
        $newDoctor = Doctor::with('user')->find($newDoctorId);
        $newDoctorName = (string) ($newDoctor?->user?->name ?? 'el nuevo médico');

        $this->afterChange($appointment, $previousDoctorId, $previousDoctorName, $newDoctorId, $newDoctorName, $reason);

        return ChangeDoctorResult::success(
            "La cita {$appointment->reference} ahora está a cargo de {$newDoctorName}. Se avisó al paciente y a los médicos."
        );
    }

    // ------------------------------------------------------------------ reglas

    private function stateError(Appointment $appointment, Carbon $startAt): ?string
    {
        if ($appointment->clinic_id === null) {
            return 'Solo las citas de una clínica se pueden reasignar a otro médico.';
        }

        if (!in_array($this->statusValue($appointment), ['pending', 'confirmed'], true)) {
            return 'Solo se puede cambiar el médico de citas pendientes o confirmadas.';
        }

        if ($appointment->zoom_started) {
            return 'La consulta ya inició; no se puede cambiar el médico.';
        }

        if ($startAt->isPast()) {
            return 'La cita ya pasó; no se puede cambiar el médico.';
        }

        return null;
    }

    /** Médicos aprobados y activos que son miembros aprobados de la clínica de la cita. */
    private function eligibleDoctors(Appointment $appointment)
    {
        return Doctor::query()
            ->where('doctors.validation_status', 'approved')
            ->where('doctors.active', true)
            ->whereHas('clinics', function ($q) use ($appointment) {
                $q->where('clinics.id', $appointment->clinic_id)
                    ->where('clinic_doctor.status', 'approved');
            })
            ->with('user')
            ->get();
    }

    /**
     * ¿Ese médico tiene libre justo este turno (misma hora, sede y servicio)?
     *
     * @return array{0: bool, 1: ?string}
     */
    private function availability(Appointment $appointment, int $doctorId, Carbon $startAt): array
    {
        try {
            $slots = $this->resolver->slotsForDay(
                $this->query($appointment, $doctorId),
                $startAt->copy()->startOfDay()
            );
        } catch (Throwable $e) {
            Log::warning('AppointmentDoctorChanger: no se pudo consultar la agenda del médico', [
                'doctor_id' => $doctorId,
                'error'     => $e->getMessage(),
            ]);

            return [false, 'No se pudo consultar su agenda.'];
        }

        foreach ($slots as $slot) {
            if ($slot->start->format('H:i') !== $startAt->format('H:i')) {
                continue;
            }

            if ($appointment->address_id && (int) $slot->addressId !== (int) $appointment->address_id) {
                continue;
            }

            if ($slot->isAvailable()) {
                return [true, null];
            }

            return [false, match ($slot->state) {
                SlotState::Booked  => 'Ya tiene otra cita a esa hora',
                SlotState::Blocked => 'Tiene una ausencia o bloqueo en ese horario',
                SlotState::Full    => 'Alcanzó su máximo de citas ese día',
                default            => 'No está disponible en ese horario',
            }];
        }

        return [false, 'No atiende a esa hora en esta sede'];
    }

    private function query(Appointment $appointment, int $doctorId): AvailabilityQuery
    {
        return new AvailabilityQuery(
            doctorIds: [$doctorId],
            clinicId: (int) $appointment->clinic_id,
            addressId: $appointment->address_id ? (int) $appointment->address_id : null,
            serviceId: $appointment->service_id ? (int) $appointment->service_id : null,
            excludeAppointmentId: null,
            staffBooking: true,
        );
    }

    private function startAt(Appointment $appointment): Carbon
    {
        return Carbon::parse(
            Carbon::parse($appointment->date)->format('Y-m-d') . ' ' . $appointment->start_time,
            $this->tz()
        );
    }

    private function statusValue(Appointment $appointment): string
    {
        return $appointment->status instanceof \BackedEnum ? (string) $appointment->status->value : (string) $appointment->status;
    }

    // ------------------------------------------------------------ autorización

    /**
     * Solo la clínica dueña de la cita (o un admin) puede reasignarla.
     *
     * @throws AuthorizationException
     */
    private function authorize(Appointment $appointment, User $user): void
    {
        $role = (string) $user->role;

        if ($role === 'admin') {
            return;
        }

        if ($role === 'clinic') {
            $clinicId = $user->clinic?->id;
            if ($clinicId && $appointment->clinic_id !== null && (int) $appointment->clinic_id === (int) $clinicId) {
                return;
            }
            throw new AuthorizationException('No autorizado. Esta cita no pertenece a tu centro clínico.');
        }

        throw new AuthorizationException('Solo la clínica puede cambiar el médico de una cita.');
    }

    // ----------------------------------------------------------- efectos finales

    /**
     * Auditoría y avisos. Ninguno puede deshacer el cambio (la cita ya cambió).
     */
    private function afterChange(
        Appointment $appointment,
        int $previousDoctorId,
        string $previousDoctorName,
        int $newDoctorId,
        string $newDoctorName,
        ?string $reason
    ): void {
        try {
            AppointmentEventSourcing::recordEvent(
                $appointment,
                self::EVENT_DOCTOR_CHANGED,
                [
                    'previous_doctor_id'   => $previousDoctorId,
                    'previous_doctor_name' => $previousDoctorName,
                    'new_doctor_id'        => $newDoctorId,
                    'new_doctor_name'      => $newDoctorName,
                    'reason'               => $reason,
                    'changed_at'           => now()->toIso8601String(),
                ],
                "Médico reasignado: {$previousDoctorName} → {$newDoctorName}" . ($reason ? ". Motivo: {$reason}" : '')
            );
        } catch (Throwable $e) {
            Log::warning('AppointmentDoctorChanger: no se pudo registrar la auditoría', [
                'appointment_id' => $appointment->id,
                'error'          => $e->getMessage(),
            ]);
        }

        try {
            $appointment->load(['doctor.user', 'patient.user', 'service', 'address', 'clinic.user']);

            $previousUser = Doctor::with('user')->find($previousDoctorId)?->user;

            $recipients = [
                'patient'         => $appointment->patient?->user,
                'previous_doctor' => $previousUser,
                'new_doctor'      => $appointment->doctor?->user,
            ];

            $sentTo = [];
            foreach ($recipients as $role => $recipient) {
                $email = $recipient?->email;
                if (!$email || in_array(strtolower($email), $sentTo, true)) {
                    continue;
                }

                try {
                    Mail::to($email)->send(new AppointmentDoctorChangedMail(
                        $appointment,
                        $role,
                        (string) $recipient->name,
                        $previousDoctorName,
                        $newDoctorName,
                        $reason
                    ));
                    $sentTo[] = strtolower($email);
                } catch (Throwable $e) {
                    Log::warning('AppointmentDoctorChanger: no se pudo enviar el correo', [
                        'appointment_id' => $appointment->id,
                        'role'           => $role,
                        'error'          => $e->getMessage(),
                    ]);
                }
            }
        } catch (Throwable $e) {
            Log::warning('AppointmentDoctorChanger: no se pudieron preparar los correos', [
                'appointment_id' => $appointment->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    private function tz(): string
    {
        return config('booking.timezone', 'America/Bogota');
    }
}
