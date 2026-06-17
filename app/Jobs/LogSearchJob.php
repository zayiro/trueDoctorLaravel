<?php

namespace App\Jobs;

use App\Models\SearchLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class LogSearchJob implements ShouldQueue
{
    use Queueable;

    /**
     * Constructor del Job preparado para recibir parámetros opcionales.
     */
    public function __construct(
        protected string $specialty,
        protected ?string $city,
        protected string $clientIp
    ) {}

    /**
     * Ejecuta la lógica de geolocalización y guardado en segundo plano.
     */
    public function handle(): void
    {
        // 1. Limpieza de especialidad garantizando formato slug
        $specialtySlug = Str::slug($this->specialty);
        
        // 2. Control de procedencia para el campo de la ciudad
        if (!empty($this->city)) {
            $finalCity = $this->city;
        } else {
            $geoData = geoip($this->clientIp);
            $finalCity = $geoData->city ?? 'Unknown';
        }

        $citySlug = Str::slug($finalCity) ?: 'unknown';

        // 3. Resolución complementaria del país mediante IP
        $geoDataForCountry = isset($geoData) ? $geoData : geoip($this->clientIp);
        $countryName = $geoDataForCountry->country ?? 'Unknown';

        // Guardado persistente en base de datos indexada
        SearchLog::create([
            'specialty'  => substr($specialtySlug, 0, 100),
            'city'       => $citySlug,
            'country'    => $countryName,
            'ip_address' => $this->clientIp,
        ]);
    }
}
