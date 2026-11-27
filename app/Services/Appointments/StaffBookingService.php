<?php

declare(strict_types=1);

namespace App\Services\Appointments;

use App\Jobs\FinalizeAppointmentJob;
use App\Models\Address;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Services\Availability\AvailabilityQuery;
use App\Services\Availability\AvailabilityResolver;
use App\Services\Availability\SlotNotAvailableException;

/**
 * Agendamiento hecho por un doctor o una clínica ("Crear cita"). Toda la lógica vive aquí; el controlador es delgado.
 *
 * - Modo `staffBooking` del motor: sin anticipación mínima ni horizonte, pero SÍ choques, bloqueos, festivos y cupo.
 * - Cita virtual → siempre pago en línea con enlace. Cita presencial → pago en línea con enlace o "pago en consulta".
 */
class StaffBookingService
{
    public function __construct(
        private readonly AvailabilityResolver $availability,
        private readonly PaymentLinkService $links,
    ) {
    }

    // ------------------------------------------------------------------ contexto del usuario

    /** @return array{clinic_id: int|null, doctor_ids: int[]} */
    public function context(User $user): array
    {
        if ($user->role === 'clinic' && $user->clinic) {
            return ['clinic_id' => (int) $user->clinic->id, 'doctor_ids' => $this->approvedClinicDoctorIds((int) $user->clinic->id)];
        }

        if ($user->role === 'doctor' && $user->doctor) {
            $ctx = session('doctor_context');
            $clinicId = (($ctx['type'] ?? 'particular') === 'clinic') ? (int) ($ctx['id'] ?? 0) : 0;

            if ($clinicId && !$this->doctorBelongsToClinic((int) $user->doctor->id, $clinicId)) {
                throw new DomainException('Ya no estás vinculado a esta clínica.');
            }

            return ['clinic_id' => $clinicId ?: null, 'doctor_ids' => [(int) $user->doctor->id]];
        }

        throw new DomainException('No tienes permisos para agendar citas.');
    }

    /** Doctores y sedes que este usuario puede usar en el formulario. */
    public function options(User $user): array
    {
        ['clinic_id' => $clinicId, 'doctor_ids' => $doctorIds] = $this->context($user);

        $doctors = Doctor::with('user')->whereIn('id', $doctorIds)->get()
            ->map(fn (Doctor $d) => ['id' => $d->id, 'name' => $d->user->name ?? ('Doctor #' . $d->id)])->values();

        $addresses = Address::query()
            ->where('status', true)
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->when(!$clinicId, fn ($q) => $q->whereNull('clinic_id')->whereIn('doctor_id', $doctorIds))
            ->orderBy('name')
            ->get();

        $doctorsByAddress = DB::table('schedules')
            ->where('is_active', true)
            ->whereIn('address_id', $addresses->pluck('id'))
            ->whereIn('doctor_id', $doctorIds)
            ->select('address_id', 'doctor_id')->distinct()->get()
            ->groupBy('address_id')
            ->map(fn ($rows) => $rows->pluck('doctor_id')->map(fn ($v) => (int) $v)->values());

        return [
            'doctors'   => $doctors,
            'addresses' => $addresses->map(fn (Address $a) => [
                'id'         => $a->id,
                'name'       => $a->name,
                'type'       => $a->type,
                'doctor_ids' => $doctorsByAddress[$a->id] ?? [],
            ])->values(),
        ];
    }

    /**
     * Valida que doctor y sede pertenezcan a quien agenda. Es la ÚNICA puerta de autorización del módulo.
     *
     * @return array{0: Doctor, 1: Address}
     * @throws DomainException
     */
    public function authorizeScope(User $user, int $addressId, ?int $doctorId): array
    {
        ['clinic_id' => $clinicId, 'doctor_ids' => $doctorIds] = $this->context($user);

        $doctorId = $doctorId ?: (count($doctorIds) === 1 ? $doctorIds[0] : null);

        if (!$doctorId || !in_array($doctorId, $doctorIds, true)) {
            throw new DomainException('Selecciona un especialista válido.');
        }

        $doctor = Doctor::with(['user', 'settings'])->where('id', $doctorId)->where('validation_status', 'approved')->first();

        if (!$doctor) {
            throw new DomainException('El especialista no está habilitado.');
        }

        $address = Address::where('id', $addressId)->where('status', true)->first();

        $belongs = $address && (
            $clinicId
                ? (int) $address->clinic_id === $clinicId
                : ($address->clinic_id === null && (int) $address->doctor_id === (int) $doctor->id)
        );

        if (!$belongs) {
            throw new DomainException('La sede seleccionada no es válida.');
        }

        return [$doctor, $address];
    }

    public function servicesFor(Address $address): array
    {
        return $address->services()->where('services.active', true)->get()
            ->map(fn ($s) => [
                'id'       => $s->id,
                'name'     => $s->name,
                'type'     => $s->type,
                'duration' => (int) $s->pivot->duration,
                'price'    => (float) $s->pivot->price,
            ])->values()->all();
    }

    public function query(Doctor $doctor, Address $address, ?int $serviceId): AvailabilityQuery
    {
        return new AvailabilityQuery(
            doctorIds: [(int) $doctor->id],
            clinicId: $address->clinic_id ? (int) $address->clinic_id : null,
            addressId: (int) $address->id,
            serviceId: $serviceId,
            staffBooking: true,
        );
    }

    // ------------------------------------------------------------------ paciente

    /**
     * Busca al paciente por documento, luego por correo; si no existe lo crea.
     * @throws DomainException
     */
    public function resolvePatient(array $data): Patient
    {
        $identification = trim((string) $data['identification']);
        $email = strtolower(trim((string) ($data['email'] ?? '')));

        // 1) Por documento (índice ciego: el documento está cifrado).
        $byDocument = Patient::where('identification_blind_index', Patient::generateBlindIndex($identification))->first();

        if ($byDocument) {
            if (!$byDocument->active) {
                throw new DomainException('El paciente existe pero su cuenta está inactiva. Contacta al administrador.');
            }

            return $byDocument;
        }

        if ($email === '' || empty($data['name']) || empty($data['phone'])) {
            throw new DomainException('Para registrar al paciente se necesita nombre, correo y teléfono.');
        }

        $phone = ($data['country_code'] ?? '+57') . preg_replace('/[^0-9]/', '', (string) $data['phone']);

        return DB::transaction(function () use ($email, $data, $identification, $phone) {
            // 2) Por correo.
            $user = User::where('email', $email)->first();

            if ($user) {
                if ($user->role !== 'patient') {
                    throw new DomainException('Ese correo pertenece a personal del sistema, no a un paciente.');
                }

                $existing = Patient::where('user_id', $user->id)->first();

                if ($existing) {
                    if (!$existing->active) {
                        throw new DomainException('El paciente existe pero su cuenta está inactiva. Contacta al administrador.');
                    }

                    return $existing;
                }
            } else {
                // 3) Nuevo: clave aleatoria; la persona crea la suya con el enlace del correo de bienvenida.
                $user = User::create([
                    'name'     => trim((string) $data['name']),
                    'email'    => $email,
                    'password' => \App\Support\PasswordSetup::randomFor($email),
                    'role'     => 'patient',
                ]);
                $user->assignRole(Role::firstOrCreate(['name' => 'patient']));
            }

            return Patient::create([
                'user_id'        => $user->id,
                'identification' => $identification,
                'phone'          => $phone,
            ]);
        });
    }

    // ------------------------------------------------------------------ crear la cita

    /**
     * @param array{date: string, start_time: string, service_id: int, payment_method: string, notes?: ?string} $data
     * @return array{appointment: Appointment, mode: string, link: ?string, mail_sent: bool}
     * @throws DomainException|SlotNotAvailableException
     */
    public function create(User $creator, Patient $patient, Doctor $doctor, Address $address, array $data): array
    {
        $service = $address->services()->where('services.id', (int) $data['service_id'])->where('services.active', true)->first();

        if (!$service || !$service->pivot) {
            throw new DomainException('El servicio ya no está disponible en esta sede.');
        }

        $duration = (int) $service->pivot->duration;
        $price = (float) $service->pivot->price;
        $isVirtual = $service->type === 'virtual' || $address->type === 'virtual';

        $tz = config('booking.timezone', 'America/Bogota');
        $startAt = Carbon::parse($data['date'] . ' ' . $data['start_time'], $tz);
        $endAt = $startAt->copy()->addMinutes($duration);

        if ($startAt->lte(now())) {
            throw new DomainException('Ese horario ya pasó.');
        }

        $method = $data['payment_method'];

        if ($method === 'onsite' && $isVirtual) {
            throw new DomainException('Las citas virtuales se cobran en línea: envía el enlace de pago.');
        }

        $needsLink = $method === 'online' && $price > 0;

        if ($needsLink && $startAt->lt(now()->addMinutes((int) config('booking.payment_link_min_minutes', 30)))) {
            throw new DomainException('Falta muy poco para la cita como para enviar un enlace de pago. Usa "Pago en consulta" o elige otra hora.');
        }

        $fields = [
            'patient_id'         => (int) $patient->id,
            'doctor_id'          => (int) $doctor->id,
            'clinic_id'          => $address->clinic_id ? (int) $address->clinic_id : null,
            'service_id'         => (int) $service->id,
            'address_id'         => (int) $address->id,
            'date'               => $startAt->format('Y-m-d'),
            'start_time'         => $startAt->format('H:i:s'),
            'end_time'           => $endAt->format('H:i:s'),
            'duration'           => $duration,
            'price'              => $price,
            'channel'            => 'doctor',
            'created_by_user_id' => $creator->id,
            'notes'              => isset($data['notes']) ? trim((string) $data['notes']) : null,
        ];

        if ($needsLink) {
            $fields += [
                'status'             => 'pending',
                'payment_status'     => 'pending',
                'payment_token'      => $this->links->newToken(),
                'payment_expires_at' => $this->links->expiryFor($startAt),
            ];
            $mode = 'link';
        } elseif ($method === 'online') {
            // Servicio sin costo: nada que cobrar.
            $fields += ['status' => 'confirmed', 'payment_status' => 'paid', 'paid_at' => now()];
            $mode = 'confirmed';
        } else {
            // Pago en consulta: queda confirmada y se cobra en el consultorio.
            $fields += ['status' => 'confirmed', 'payment_status' => 'pending'];
            $mode = 'confirmed';
        }

        $appointment = $this->availability->withBookableSlot(
            $this->query($doctor, $address, (int) $service->id),
            $startAt,
            fn () => Appointment::create($fields),
        );

        $mailSent = false;

        if ($mode === 'link') {
            $mailSent = $this->links->send($appointment);
        } else {
            FinalizeAppointmentJob::dispatch($appointment->id);
        }

        return [
            'appointment' => $appointment,
            'mode'        => $mode,
            'link'        => $mode === 'link' ? $this->links->url($appointment) : null,
            'mail_sent'   => $mailSent,
        ];
    }

    // ------------------------------------------------------------------ utilidades

    private function approvedClinicDoctorIds(int $clinicId): array
    {
        return DB::table('clinic_doctor')->where('clinic_id', $clinicId)->where('status', 'approved')
            ->pluck('doctor_id')->map(fn ($v) => (int) $v)->all();
    }

    private function doctorBelongsToClinic(int $doctorId, int $clinicId): bool
    {
        return DB::table('clinic_doctor')->where('clinic_id', $clinicId)->where('doctor_id', $doctorId)
            ->where('status', 'approved')->exists();
    }
}
