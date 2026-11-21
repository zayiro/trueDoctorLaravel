<?php

namespace App\Http\Controllers;

use App\Support\DeviceLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/**
 * Landing de campañas "Doctores cerca de mí".
 *
 * No pide ubicación al abrir. Dónde centra el mapa, en orden:
 *   1. ?ciudad=cali del enlace de campaña (siempre gana);
 *   2. la ubicación que el visitante ya dio y quedó en su sesión (device_location, máx. 24 h);
 *   3. la ciudad por defecto (Cali).
 * La lista inicial se genera en el servidor (el HTML ya trae los especialistas: útil para SEO y carga rápida).
 */
class NearbyLandingController extends Controller
{
    public function index(Request $request): View
    {
        $cities  = (array) config('nearby_landing.cities', []);
        $default = (string) config('nearby_landing.default', 'cali');
        $radius  = (int) config('nearby_landing.radius_km', 30);

        if (!isset($cities['cali'])) {
            $cities = ['cali' => ['name' => 'Cali', 'lat' => 3.4516, 'lng' => -76.5320]] + $cities;
        }

        $requested = Str::slug((string) $request->query('ciudad', ''));
        $device    = DeviceLocation::fromRequest($request);

        if (isset($cities[$requested])) {
            $mode    = 'city';
            $cityKey = $requested;
        } elseif ($device !== null) {
            $mode    = 'exact';
            $cityKey = '';
        } else {
            $mode    = 'city';
            $cityKey = isset($cities[$default]) ? $default : 'cali';
        }

        $center = $mode === 'exact'
            ? $device
            : ['lat' => (float) $cities[$cityKey]['lat'], 'lng' => (float) $cities[$cityKey]['lng']];

        return view('public.nearby-landing', [
            'cities'     => $cities,
            'cityKey'    => $cityKey,
            'mode'       => $mode,
            'center'     => $center,
            'placeLabel' => $mode === 'exact' ? 'tu ubicación' : $cities[$cityKey]['name'],
            'radius'     => $radius,
            'doctors'    => $this->doctorsNear($center['lat'], $center['lng'], $radius),
            'endpoint'   => route('api.doctors.nearby'),
        ]);
    }

    /** Alias antiguos (/nearby-doctors, /doctors/nearby): 301 a la landing conservando ?ciudad= y los UTM. */
    public function redirect(Request $request): RedirectResponse
    {
        return redirect()->route('doctors.landing', $request->query(), 301);
    }

    /** Reutiliza la misma consulta del mapa (mismas reglas de visibilidad y próximo turno real). */
    private function doctorsNear(float $lat, float $lng, int $radius): array
    {
        try {
            $response = app(NearbyDoctorsController::class)->fetchNearby(
                Request::create('/api/doctors/nearby', 'GET', ['latitude' => $lat, 'longitude' => $lng, 'radius' => $radius])
            );
            $payload = $response->getData(true);

            return ($payload['success'] ?? false) ? array_values($payload['doctors'] ?? []) : [];
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }
}
