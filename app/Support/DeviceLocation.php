<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;

/**
 * Ubicación que el visitante dio en el navegador y quedó en su sesión (session('device_location')).
 * Viene del cliente: se valida (numérica, en rango, de menos de 24 h) antes de usarla.
 */
class DeviceLocation
{
    /** @return array{lat: float, lng: float}|null */
    public static function fromRequest(Request $request): ?array
    {
        if (!$request->hasSession()) {
            return null;
        }

        $saved = $request->session()->get('device_location');

        if (!is_array($saved)) {
            return null;
        }

        $lat = $saved['latitude'] ?? null;
        $lng = $saved['longitude'] ?? null;

        if (!is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if (abs($lat) > 90 || abs($lng) > 180 || ($lat == 0.0 && $lng == 0.0)) {
            return null;
        }

        try {
            if (!empty($saved['saved_at']) && Carbon::parse($saved['saved_at'])->lt(now()->subDay())) {
                return null;
            }
        } catch (Throwable $e) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    /** Distancia en km entre dos puntos (fórmula de Haversine). */
    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r    = 6371.0088;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
