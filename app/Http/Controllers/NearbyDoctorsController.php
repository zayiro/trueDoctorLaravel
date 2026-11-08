<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class NearbyDoctorsController extends Controller
{
    /** Máximo de especialistas devueltos al mapa. */
    private const MAX_RESULTS = 50;

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
                        // No se inventa disponibilidad: hasta calcularla con el motor de turnos, el mapa debe ocultar este campo
                        'nextAppointment' => null,
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