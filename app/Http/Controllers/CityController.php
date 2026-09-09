<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CityController extends Controller
{
    /**
     * Ciudades principales de Colombia
     */
    private const MAIN_CITIES = [
        ['id' => 3686110, 'name' => 'Bogotá', 'country' => 'Colombia', 'countryCode' => 'CO', 'adminName1' => 'Bogotá', 'lat' => 4.7110, 'lng' => -74.0721],
        ['id' => 3674730, 'name' => 'Medellín', 'country' => 'Colombia', 'countryCode' => 'CO', 'adminName1' => 'Antioquia', 'lat' => 6.2442, 'lng' => -75.5812],
        ['id' => 3687238, 'name' => 'Cali', 'country' => 'Colombia', 'countryCode' => 'CO', 'adminName1' => 'Valle del Cauca', 'lat' => 3.4516, 'lng' => -76.5319],
        ['id' => 3668740, 'name' => 'Barranquilla', 'country' => 'Colombia', 'countryCode' => 'CO', 'adminName1' => 'Atlántico', 'lat' => 10.9639, 'lng' => -74.7964],
        ['id' => 3657050, 'name' => 'Cartagena', 'country' => 'Colombia', 'countryCode' => 'CO', 'adminName1' => 'Bolívar', 'lat' => 10.3932, 'lng' => -75.4830],
        ['id' => 3659203, 'name' => 'Bucaramanga', 'country' => 'Colombia', 'countryCode' => 'CO', 'adminName1' => 'Santander', 'lat' => 7.1269, 'lng' => -73.1122],
        ['id' => 3671368, 'name' => 'Santa Marta', 'country' => 'Colombia', 'countryCode' => 'CO', 'adminName1' => 'Magdalena', 'lat' => 11.2429, 'lng' => -74.2247],
        ['id' => 3662110, 'name' => 'Pereira', 'country' => 'Colombia', 'countryCode' => 'CO', 'adminName1' => 'Risaralda', 'lat' => 4.8133, 'lng' => -75.6969],
    ];

    /**
     * Configuración de caché y API
     */
    private const CACHE_TTL = 60 * 60 * 24; // 24 horas
    private const CACHE_TAG = 'geonames_cities';
    private const MIN_QUERY_LENGTH = 2;
    private const API_TIMEOUT = 5; // segundos
    private const GEONAMES_URL = 'http://api.geonames.org';
    private const REVERSE_GEOCODE_RADIUS = 50; // km
    private const MAX_RESULTS = 10;

    /**
     * Buscar ciudades por texto
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim($request->get('q', ''));
        $country = trim($request->get('country', ''));
        
        // Validación básica
        if (strlen($query) < self::MIN_QUERY_LENGTH) {
            return $this->successResponse(self::MAIN_CITIES, 'default');
        }

        $cacheKey = $this->generateCacheKey('search', $query, $country);
        
        return $this->searchCities($query, $country, $cacheKey);
    }

    /**
     * Buscar ciudad más cercana por coordenadas (geolocalización)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function searchByCoordinates(Request $request): JsonResponse
    {
        $lat = $request->get('lat');
        $lng = $request->get('lng');

        // Validación
        if (!$this->isValidCoordinate($lat) || !$this->isValidCoordinate($lng)) {
            return $this->errorResponse('Coordenadas inválidas', 422);
        }

        $cacheKey = $this->generateCacheKey('reverse', $lat, $lng);

        // Intenta obtener del caché
        $cached = $this->getCachedData($cacheKey);
        if ($cached !== null) {
            return $this->successResponse($cached, 'cache');
        }

        return $this->reverseGeocode($lat, $lng, $cacheKey);
    }

    /**
     * Limpiar caché de búsquedas
     * 
     * @return JsonResponse
     */
    public function clearCache(): JsonResponse
    {
        Cache::tags([self::CACHE_TAG])->flush();
        
        return response()->json([
            'success' => true,
            'message' => 'Caché limpiado exitosamente'
        ]);
    }

    /**
     * ==================== MÉTODOS PRIVADOS ====================
     */

    /**
     * Buscar ciudades por query de texto
     * 
     * @param string $query
     * @param string $country
     * @param string $cacheKey
     * @return JsonResponse
     */
    private function searchCities(string $query, string $country, string $cacheKey): JsonResponse
    {
        $username = config('services.geonames.username');
        
        if (!$username) {
            Log::warning('Geonames username no configurado');
            return $this->errorResponse('Configuración incompleta', 500);
        }

        $params = [
            'name_startsWith' => $query,
            'featureClass' => 'P', // Populated place
            'maxRows' => self::MAX_RESULTS,
            'username' => $username,
            'lang' => 'es'
        ];
        
        if (!empty($country)) {
            $params['country'] = $country;
        }

        try {
            $response = Http::timeout(self::API_TIMEOUT)
                ->get(self::GEONAMES_URL . '/searchJSON', $params);

            // Manejo de errores HTTP
            if ($response->status() === 403) {
                return $this->fallbackResponse('API limitada - reintenta en unos minutos');
            }

            if (!$response->ok()) {
                Log::warning("Geonames error: HTTP {$response->status()}", [
                    'query' => $query,
                    'country' => $country
                ]);
                return $this->fallbackResponse('Error en el servicio de ciudades');
            }

            // Procesa los datos
            $cities = $this->processCitiesResponse($response->json());

            if (empty($cities)) {
                return $this->fallbackResponse('No se encontraron ciudades', self::MAIN_CITIES);
            }

            // Cachea el resultado
            $this->cacheData($cacheKey, $cities);
            
            return $this->successResponse($cities, 'api');

        } catch (\Exception $e) {
            Log::error('Geonames API error', [
                'message' => $e->getMessage(),
                'query' => $query,
                'country' => $country,
                'exception' => get_class($e)
            ]);

            return $this->fallbackResponse('Error al conectar con el servicio');
        }
    }

    /**
     * Búsqueda inversa: coordenadas a ciudad
     * 
     * @param float $lat
     * @param float $lng
     * @param string $cacheKey
     * @return JsonResponse
     */
    private function reverseGeocode(float $lat, float $lng, string $cacheKey): JsonResponse
    {
        $username = config('services.geonames.username');
        
        if (!$username) {
            Log::warning('Geonames username no configurado');
            return $this->errorResponse('Configuración incompleta', 500);
        }

        $params = [
            'lat' => $lat,
            'lng' => $lng,
            'radius' => self::REVERSE_GEOCODE_RADIUS,
            'featureClass' => 'P',
            'maxRows' => self::MAX_RESULTS,
            'username' => $username,
            'lang' => 'es'
        ];

        try {
            $response = Http::timeout(self::API_TIMEOUT)
                ->get(self::GEONAMES_URL . '/findNearbyPlacesJSON', $params);

            if (!$response->ok()) {
                Log::warning("Geonames reverse error: HTTP {$response->status()}", [
                    'lat' => $lat,
                    'lng' => $lng
                ]);
                return $this->fallbackResponse('Error en geolocalización');
            }

            // Procesa los datos
            $cities = $this->processCitiesResponse($response->json());

            if (empty($cities)) {
                return $this->fallbackResponse('No se encontraron ciudades cercanas');
            }

            // Cachea el resultado
            $this->cacheData($cacheKey, $cities);

            return $this->successResponse($cities, 'api');

        } catch (\Exception $e) {
            Log::error('Geonames reverse geocode error', [
                'message' => $e->getMessage(),
                'lat' => $lat,
                'lng' => $lng,
                'exception' => get_class($e)
            ]);

            return $this->fallbackResponse('Error al conectar con el servicio');
        }
    }

    /**
     * Procesa y normaliza la respuesta de Geonames
     * 
     * @param array $data
     * @return array
     */
    private function processCitiesResponse(array $data): array
    {
        // Valida estructura
        if (!isset($data['geonames']) || !is_array($data['geonames'])) {
            Log::warning('Respuesta Geonames con estructura inválida');
            return [];
        }

        return collect($data['geonames'])
            ->filter(fn($city) => isset($city['geonameId'], $city['name']))
            ->map(fn($city) => $this->normalizeCityData($city))
            ->values()
            ->all();
    }

    /**
     * Normaliza los datos de una ciudad de Geonames
     * 
     * @param array $city
     * @return array
     */
    private function normalizeCityData(array $city): array
    {
        //Log::debug($city);

        return [
            'id' => (int) $city['geonameId'],
            'name' => $city['name'] ?? '',
            'country' => $city['countryName'] ?? '',
            'countryCode' => $city['countryCode'] ?? '',
            'lat' => (float) ($city['lat'] ?? 0),
            'lng' => (float) ($city['lng'] ?? 0),
            'adminName1' => $city['adminName1'] ?? '',
            'population' => $city['population'] ?? 0
        ];
    }

    /**
     * Genera una clave de caché consistente
     * 
     * @param string $type
     * @param mixed ...$parts
     * @return string
     */
    private function generateCacheKey(string $type, ...$parts): string
    {
        $key = implode('_', array_filter($parts));
        return 'geonames_' . $type . '_' . hash('sha256', $key);
    }

    /**
     * Obtiene datos del caché
     * 
     * @param string $key
     * @return array|null
     */
    private function getCachedData(string $key): ?array
    {
        return Cache::tags([self::CACHE_TAG])->get($key);
    }

    /**
     * Guarda datos en caché
     * 
     * @param string $key
     * @param array $data
     * @return void
     */
    private function cacheData(string $key, array $data): void
    {
        Cache::tags([self::CACHE_TAG])->put($key, $data, self::CACHE_TTL);
    }

    /**
     * Valida que un valor sea una coordenada válida
     * 
     * @param mixed $value
     * @return bool
     */
    private function isValidCoordinate($value): bool
    {
        return is_numeric($value) && $value >= -90 && $value <= 90;
    }

    /**
     * ==================== RESPUESTAS JSON ====================
     */

    /**
     * Respuesta exitosa
     * 
     * @param array $data
     * @param string $source 'api', 'cache', o 'default'
     * @return JsonResponse
     */
    private function successResponse(array $data, string $source = 'api'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'cached' => $source === 'cache',
            'source' => $source
        ]);
    }

    /**
     * Respuesta con fallback a ciudades principales
     * 
     * @param string $message
     * @param array|null $fallbackData
     * @return JsonResponse
     */
    private function fallbackResponse(string $message, ?array $fallbackData = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => $fallbackData ?? self::MAIN_CITIES,
            'fallback' => true,
            'message' => $message,
            'cached' => false
        ], 200); // HTTP 200 porque devolvemos datos válidos
    }

    /**
     * Respuesta de error
     * 
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    private function errorResponse(string $message, int $code = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => []
        ], $code);
    }
}