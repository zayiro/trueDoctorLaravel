<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NearbyDoctorsController extends Controller
{
    /**
     * Obtener médicos y staff de clínicas cercanos utilizando SQL puro sobre tu estructura real.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function fetchNearby(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius'    => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $userLat  = (float) $validated['latitude'];
        $userLng  = (float) $validated['longitude'];
        $radiusKm = (int) ($validated['radius'] ?? 50);

        try {
            // Fórmula de Haversine para cálculo preciso en MariaDB
            $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(addresses.latitude)) * cos(radians(addresses.longitude) - radians(?)) + sin(radians(?)) * sin(radians(addresses.latitude))))";

            // Unificamos la consulta cruzando direcciones de doctores individuales y staff de clínicas
            $doctors = DB::table('doctors')
                ->join('users as doc_user', 'doc_user.id', '=', 'doctors.user_id')
                ->join('addresses', function ($join) {
                    // La dirección puede estar vinculada al doctor_id directo o al clinic_id al que pertenece
                    $join->on('addresses.doctor_id', '=', 'doctors.id')
                         ->orOn('addresses.clinic_id', '=', 'addresses.clinic_id'); 
                })
                // Traemos el nombre de la clínica si la dirección pertenece a una clínica
                ->leftJoin('clinics', 'clinics.id', '=', 'addresses.clinic_id')
                ->leftJoin('users as clinic_user', 'clinic_user.id', '=', 'clinics.user_id')
                ->select([
                    'doctors.id',
                    'doctors.slug',
                    'addresses.latitude',
                    'addresses.longitude',
                    DB::raw("CASE 
                        WHEN addresses.clinic_id IS NOT NULL THEN CONCAT(doc_user.name, ' (', clinic_user.name, ')')
                        ELSE doc_user.name 
                    END as display_name"),
                    DB::raw("{$haversine} AS distance")
                ])
                ->addBinding([$userLat, $userLng, $userLat], 'select')
                ->where('doctors.active', 1)
                ->whereNull('addresses.deleted_at')
                ->having('distance', '<=', $radiusKm)
                ->orderBy('distance', 'asc')
                ->get();

            // Formatear la colección para el mapa estilo Google Maps
            $formattedDoctors = $doctors->map(function ($doc) {
                return [
                    'id'              => $doc->id,
                    'name'            => $doc->display_name,
                    'slug'            => $doc->slug,
                    'latitude'        => (float) $doc->latitude,
                    'longitude'       => (float) $doc->longitude,
                    'distance'        => round((float) $doc->distance, 1),
                    'nextAppointment' => 'Mañana, 10:00 AM', // Cambiar por tu lógica de turnos si aplica
                    'profileUrl'      => route('partner.public.profile', $doc->slug),
                ];
            });

            return response()->json([
                'success' => true,
                'doctors' => $formattedDoctors,
                'total'   => $formattedDoctors->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la consulta geoespacial de OpenDoctor.',
                'error'   => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], 500);
        }
    }
}
