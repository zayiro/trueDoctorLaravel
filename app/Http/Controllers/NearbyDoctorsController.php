<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\Availability\AvailabilityQuery;
use App\Services\Availability\AvailabilityResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class NearbyDoctorsController extends Controller
{
    /** Máximo de especialistas devueltos al mapa. */
    private const MAX_RESULTS = 50;

    /** Cuántos resultados (los más cercanos) calculan su próximo turno: es lo más costoso del endpoint. */
    private const NEXT_TURN_LIMIT = 10;

    /**
     * Especialistas con sede física cerca de la ubicación del usuario.
     *
     * Reglas:
     *  - Solo sedes físicas, activas y no eliminadas (Address::bookable() + SoftDeletes), con coordenadas.
     *  - Solo doctores activos, aprobados y con perfil público (slug).
     *  - Sede propia: su doctor. Sede de clínica: los doctores aprobados en la clínica con horarios activos en esa sede.
     *  - Las coordenadas se redondean a 4 decimales (~11 m): suficiente para el mapa sin exponer el punto exacto.
     */
    public function fetchNearby(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius'    => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'No pudimos leer tu ubicación. Intenta de nuevo.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $latitude  = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];
        $radiusKm  = (float) ($validated['radius'] ?? 50);

        try {
            // near() acota por "caja" (usa el índice latitude/longitude) y calcula la distancia exacta con LEAST(1, …),
            // por lo que un doctor exactamente en tu posición ya no se pierde por error de redondeo en ACOS.
            $addresses = Address::query()
                ->bookable()
                ->where('addresses.type', 'physical')
                ->near($latitude, $longitude, $radiusKm)
                ->limit(self::MAX_RESULTS)
                ->get();

            [$doctorIdsByAddress, $doctors] = $this->resolveDoctors($addresses);
            $clinicNames = $this->clinicNames($addresses);

            $results = collect();

            foreach ($addresses as $address) {
                foreach (array_unique($doctorIdsByAddress[$address->id] ?? []) as $doctorId) {
                    $doctor = $doctors->get($doctorId);

                    if (!$doctor) {
                        continue;
                    }

                    $clinicName = $address->clinic_id ? ($clinicNames[$address->clinic_id] ?? null) : null;

                    $results->push([
                        'key'             => "{$doctor->id}-{$address->id}",
                        'id'              => $doctor->id,
                        'name'            => $clinicName ? "{$doctor->user->name} ({$clinicName})" : $doctor->user->name,
                        'slug'            => $doctor->slug,
                        'address'         => $address->name,
                        'latitude'        => round((float) $address->latitude, 4),
                        'longitude'       => round((float) $address->longitude, 4),
                        'distance'        => round((float) $address->distance_km, 1),
                        // Próximo turno real (motor de turnos), solo para los más cercanos. null = el mapa no muestra la cajita.
                        'nextAppointment' => $this->nextTurnLabel($doctor, $address, $results->count() < self::NEXT_TURN_LIMIT),
                        'profileUrl'      => route('partner.public.profile', $doctor->slug),
                    ]);
                }
            }

            $results = $results->take(self::MAX_RESULTS)->values();

            return response()->json([
                'success' => true,
                'doctors' => $results,
                'total'   => $results->count(),
            ]);
        } catch (Throwable $e) {
            report($e); // el detalle técnico va al log, nunca a la respuesta pública

            return response()->json([
                'success' => false,
                'message' => 'No pudimos consultar los especialistas cercanos. Intenta de nuevo en unos minutos.',
            ], 500);
        }
    }

    /**
     * Próximo turno del médico EN ESA SEDE ("Hoy 3:00 PM", "Mañana 8:00 AM", "Lun 23 nov · 8:00 AM"),
     * o null si no hay turnos en 14 días o no se pudo calcular. Se guarda 60 s para no recalcular en cada mapa abierto.
     */
    private function nextTurnLabel(Doctor $doctor, Address $address, bool $compute): ?string
    {
        if (!$compute) {
            return null;
        }

        try {
            $label = Cache::remember("nearby_next_turn:{$doctor->id}:{$address->id}", 60, function () use ($doctor, $address) {
                $tz       = config('booking.timezone', 'America/Bogota');
                $now      = Carbon::now($tz);
                $resolver = app(AvailabilityResolver::class);
                $query    = new AvailabilityQuery(
                    [(int) $doctor->id],
                    $address->clinic_id ? (int) $address->clinic_id : null,
                    (int) $address->id
                );

                $dates = array_values($resolver->enabledDates(
                    $query,
                    $now->copy()->startOfDay(),
                    $now->copy()->addDays(14)->startOfDay()
                ));

                foreach (array_slice($dates, 0, 3) as $date) {
                    $day = Carbon::parse($date, $tz)->startOfDay();

                    foreach ($resolver->slotsForDay($query, $day) as $slot) {
                        if (!$slot->isAvailable()) {
                            continue;
                        }

                        $time = $slot->start->format('g:i A');

                        if ($day->isSameDay($now)) {
                            return 'Hoy ' . $time;
                        }
                        if ($day->isSameDay($now->copy()->addDay())) {
                            return 'Mañana ' . $time;
                        }

                        return $day->translatedFormat('D d M') . ' · ' . $time;
                    }
                }

                return ''; // sin turnos (Cache no guarda null)
            });

            return $label !== '' ? $label : null;
        } catch (Throwable $e) {
            Log::warning('Nearby: no se pudo calcular el próximo turno', [
                'doctor_id'  => $doctor->id,
                'address_id' => $address->id,
                'error'      => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Doctores de cada sede encontrada.
     *
     * @return array{0: array<int, int[]>, 1: Collection<int, Doctor>} [address_id => doctor_ids[], doctores por id]
     */
    private function resolveDoctors(Collection $addresses): array
    {
        $byAddress = [];

        // Sede propia: el doctor dueño
        foreach ($addresses->whereNotNull('doctor_id') as $address) {
            $byAddress[$address->id][] = (int) $address->doctor_id;
        }

        // Sede de clínica: doctores aprobados en la clínica con horarios activos en ESA sede.
        // (Antes el JOIN `addresses.clinic_id = addresses.clinic_id` era siempre verdadero y mostraba a todos los
        // doctores en todas las clínicas.)
        $clinicAddressIds = $addresses->whereNull('doctor_id')->whereNotNull('clinic_id')->pluck('id');

        if ($clinicAddressIds->isNotEmpty()) {
            DB::table('schedules as s')
                ->join('addresses as a', 'a.id', '=', 's.address_id')
                ->join('clinic_doctor as cd', function ($join) {
                    $join->on('cd.clinic_id', '=', 'a.clinic_id')->on('cd.doctor_id', '=', 's.doctor_id');
                })
                ->whereIn('s.address_id', $clinicAddressIds)
                ->where('s.is_active', 1)
                ->where('cd.status', 'approved')
                ->whereNotNull('s.doctor_id')
                ->select('s.address_id', 's.doctor_id')
                ->distinct()
                ->get()
                ->each(function ($row) use (&$byAddress) {
                    $byAddress[(int) $row->address_id][] = (int) $row->doctor_id;
                });
        }

        $doctorIds = collect($byAddress)->flatten()->unique()->values();

        $doctors = Doctor::query()
            ->with('user')
            ->whereIn('id', $doctorIds)
            ->where('active', 1)
            ->where('validation_status', 'approved')
            ->whereNotNull('slug')
            ->get()
            ->keyBy('id');

        return [$byAddress, $doctors];
    }

    /**
     * Nombre de cada clínica (clinic_id => nombre), tomado del usuario de la clínica.
     */
    private function clinicNames(Collection $addresses): Collection
    {
        $clinicIds = $addresses->pluck('clinic_id')->filter()->unique()->values();

        if ($clinicIds->isEmpty()) {
            return collect();
        }

        return DB::table('clinics')
            ->join('users', 'users.id', '=', 'clinics.user_id')
            ->whereIn('clinics.id', $clinicIds)
            ->pluck('users.name', 'clinics.id');
    }
}