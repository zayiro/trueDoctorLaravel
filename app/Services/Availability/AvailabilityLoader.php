<?php

declare(strict_types=1);

namespace App\Services\Availability;

use App\Models\Schedule;
use App\Services\ColombiaHolidayService;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Carga de la base de datos los datos que necesita el calculador, con un número FIJO de consultas
 * (no depende de cuántos días se consulten). Es la única parte del módulo que toca la base de datos.
 */
final class AvailabilityLoader
{
    public function load(AvailabilityQuery $query, DateTimeImmutable $from, DateTimeImmutable $to): AvailabilityData
    {
        $doctorIds = $this->approvedDoctorIds($query->doctorIds);

        if ($doctorIds === []) {
            return AvailabilityData::empty();
        }

        $schedules = $this->schedules($query, $doctorIds);

        if ($schedules === []) {
            return AvailabilityData::empty();
        }

        $addressIds = array_values(array_unique(array_column($schedules, 'address_id')));
        $durations = $this->durations($query, $addressIds);

        // Si se pidió un servicio, solo cuentan las sedes donde ese servicio se ofrece.
        if ($query->serviceId !== null) {
            $schedules = array_values(array_filter($schedules, fn (array $s) => isset($durations[$s['address_id']])));

            if ($schedules === []) {
                return AvailabilityData::empty();
            }
        }

        return new AvailabilityData(
            schedules: $schedules,
            absences: $this->absences($doctorIds, $from, $to),
            appointments: $this->appointments($query, $doctorIds, $from, $to),
            settings: $this->settings($schedules, $addressIds),
            durations: $durations,
            holidays: $this->holidays($from, $to),
        );
    }

    /** @return int[] */
    private function approvedDoctorIds(array $doctorIds): array
    {
        return DB::table('doctors')
            ->whereIn('id', $doctorIds)
            ->where('validation_status', 'approved')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** Horarios activos de sedes reservables, respetando el tenant. */
    private function schedules(AvailabilityQuery $query, array $doctorIds): array
    {
        return Schedule::query()
            ->where('is_active', true)
            ->whereIn('doctor_id', $doctorIds)
            ->when($query->addressId, fn ($builder) => $builder->where('address_id', $query->addressId))
            ->whereHas('address', function ($address) use ($query) {
                $address->bookable();

                $query->clinicId === null
                    ? $address->whereNull('clinic_id')
                    : $address->where('clinic_id', $query->clinicId);
            })
            ->get(['doctor_id', 'address_id', 'day', 'start_time', 'end_time'])
            ->map(fn (Schedule $schedule) => [
                'doctor_id' => (int) $schedule->doctor_id,
                'address_id' => (int) $schedule->address_id,
                'day' => (int) $schedule->day,
                // Valores crudos de la columna TIME (el modelo los convierte en fechas de hoy).
                'start' => (string) $schedule->getRawOriginal('start_time'),
                'end' => (string) $schedule->getRawOriginal('end_time'),
            ])
            ->all();
    }

    /**
     * Ausencias expandidas por fecha. Una ausencia con horas aplica esa franja CADA día del rango.
     */
    private function absences(array $doctorIds, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $rows = DB::table('unavailabilities')
            ->whereIn('doctor_id', $doctorIds)
            ->whereDate('start_date', '<=', $to->format('Y-m-d'))
            ->whereDate('end_date', '>=', $from->format('Y-m-d'))
            ->get();

        $absences = [];

        foreach ($rows as $row) {
            $first = max($row->start_date, $from->format('Y-m-d'));
            $last = min($row->end_date, $to->format('Y-m-d'));

            for ($day = new DateTimeImmutable($first); $day->format('Y-m-d') <= $last; $day = $day->modify('+1 day')) {
                $absences[] = [
                    'doctor_id' => (int) $row->doctor_id,
                    'address_id' => $row->address_id !== null ? (int) $row->address_id : null,
                    'date' => $day->format('Y-m-d'),
                    'start' => $row->start_time,
                    'end' => $row->end_time,
                ];
            }
        }

        return $absences;
    }

    /**
     * Citas que ocupan al doctor, en CUALQUIER sede: confirmadas, completadas y las pendientes
     * que aún retienen el turno (menos de `pending_hold_minutes` desde que se crearon, o ya pagadas).
     */
    private function appointments(AvailabilityQuery $query, array $doctorIds, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        // created_at se guarda en la zona de la aplicación: se compara con now() de Laravel, no con el reloj del negocio.
        $holdCutoff = now()->subMinutes((int) config('booking.pending_hold_minutes', 120))->toDateTimeString();

        return DB::table('appointments')
            ->whereIn('doctor_id', $doctorIds)
            ->whereBetween('date', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->when($query->excludeAppointmentId, fn ($builder) => $builder->where('id', '!=', $query->excludeAppointmentId))
            ->where(function ($builder) use ($holdCutoff) {
                $builder->whereIn('status', ['confirmed', 'completed'])
                    ->orWhere(function ($pending) use ($holdCutoff) {
                        $pending->where('status', 'pending')
                            ->where(fn ($hold) => $hold->where('created_at', '>=', $holdCutoff)->orWhere('payment_status', 'paid'));
                    });
            })
            ->get(['doctor_id', 'date', 'start_time', 'end_time'])
            ->map(fn ($row) => [
                'doctor_id' => (int) $row->doctor_id,
                'date' => substr((string) $row->date, 0, 10),
                'start' => (string) $row->start_time,
                'end' => (string) $row->end_time,
            ])
            ->all();
    }

    /**
     * Política de cada doctor+sede: en una sede de clínica manda la de la clínica; en una particular, la del doctor.
     * Solo se incluyen los valores que existen; el calculador pone los por defecto.
     */
    private function settings(array $schedules, array $addressIds): array
    {
        $addressClinic = DB::table('addresses')->whereIn('id', $addressIds)->pluck('clinic_id', 'id');
        $doctorSettings = DB::table('doctor_settings')
            ->whereIn('doctor_id', array_values(array_unique(array_column($schedules, 'doctor_id'))))
            ->get()
            ->keyBy('doctor_id');

        $clinicIds = $addressClinic->filter()->unique()->values();
        $clinicSettings = $clinicIds->isEmpty()
            ? collect()
            : DB::table('clinic_settings')->whereIn('clinic_id', $clinicIds)->get()->keyBy('clinic_id');

        $settings = [];

        foreach ($schedules as $schedule) {
            $key = $schedule['doctor_id'] . ':' . $schedule['address_id'];

            if (isset($settings[$key])) {
                continue;
            }

            $clinicId = $addressClinic[$schedule['address_id']] ?? null;
            $row = $clinicId ? $clinicSettings->get($clinicId) : $doctorSettings->get($schedule['doctor_id']);

            $settings[$key] = array_filter([
                'min_notice_hours' => $this->nullableInt(data_get($row, 'min_notice_hours')),
                'max_advance_days' => $this->nullableInt(data_get($row, 'max_advance_days')),
                'buffer_minutes' => $this->nullableInt(data_get($row, 'buffer_time_minutes')),
                'max_per_day' => $this->nullableInt(data_get($row, 'max_appointments_per_day')),
            ], fn ($value) => $value !== null);
        }

        return $settings;
    }

    /** Duración por sede: la del servicio pedido o, sin servicio, la MENOR de la sede. */
    private function durations(AvailabilityQuery $query, array $addressIds): array
    {
        return DB::table('address_service')
            ->whereIn('address_id', $addressIds)
            ->when($query->serviceId, fn ($builder) => $builder->where('service_id', $query->serviceId))
            ->groupBy('address_id')
            ->selectRaw('address_id, MIN(duration) as duration')
            ->pluck('duration', 'address_id')
            ->map(fn ($minutes) => (int) $minutes)
            ->filter(fn (int $minutes) => $minutes > 0)
            ->all();
    }

    private function holidays(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        if (method_exists(ColombiaHolidayService::class, 'getHolidaysBetween')) {
            return ColombiaHolidayService::getHolidaysBetween(CarbonImmutable::instance($from), CarbonImmutable::instance($to));
        }

        $holidays = [];

        for ($year = (int) $from->format('Y'); $year <= (int) $to->format('Y'); $year++) {
            $holidays += ColombiaHolidayService::getHolidays($year);
        }

        return $holidays;
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }
}