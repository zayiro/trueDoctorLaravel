<?php

namespace App\Jobs;

use App\Models\SearchLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

// metricabusquedas: registra solo búsquedas de personas reales (sin bots, sin IPs locales, sin repetidas).
class LogSearchJob implements ShouldQueue
{
    use Queueable;

    /** Rastreadores, monitores y herramientas automáticas. */
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|googlebot|google-inspectiontool|googleother|apis-google|adsbot|bingpreview|facebookexternalhit|preview|monitor|uptime|pingdom|curl|wget|python|scrapy|httpclient|go-http|java\/|okhttp|axios|node-fetch|headless|phantom|lighthouse|gptbot|claude|anthropic|perplexity|ahrefs|semrush|mj12|dotbot|petalbot|bytespider|yandex|baidu|duckduck|archive\.org|libwww|postman|insomnia|scanner|zgrab|masscan|nmap|nikto/i';

    // Datos opcionales con valor por defecto: así no se rompen los trabajos que ya estén en la cola.
    protected ?string $userAgent = null;
    protected ?string $countryName = null;

    public function __construct(
        protected string $specialty,
        protected ?string $city,
        protected string $clientIp,
        ?string $userAgent = null,
        ?string $countryName = null
    ) {
        $this->userAgent = $userAgent;
        $this->countryName = $countryName;
    }

    /** ¿Esta visita cuenta como una búsqueda de una persona? */
    public static function shouldCount(string $ip, ?string $userAgent): bool
    {
        // IPs locales/privadas/reservadas (pruebas del propio servidor).
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }
        // Googlebot (aunque cambie el user-agent).
        if (str_starts_with($ip, '66.249.')) {
            return false;
        }
        $ua = trim((string) $userAgent);
        if ($ua === '') {
            return false;
        }

        return !preg_match(self::BOT_PATTERN, $ua);
    }

    public function handle(): void
    {
        if (!self::shouldCount($this->clientIp, $this->userAgent)) {
            return;
        }

        $specialtySlug = substr(Str::slug($this->specialty), 0, 100);
        if ($specialtySlug === '') {
            return;
        }

        // Ciudad buscada (la que eligió el visitante). Un número suelto es un código sin resolver: no sirve.
        $city = trim((string) $this->city);
        if ($city !== '' && ctype_digit($city)) {
            $city = '';
        }
        $country = trim((string) $this->countryName);

        // Solo se consulta la IP si falta la ciudad o el país.
        $geo = null;
        if ($city === '' || $country === '') {
            try {
                $geo = geoip($this->clientIp);
                if ($geo && ($geo->default ?? false)) {
                    $geo = null; // IP sin ubicación real: no inventar «New Haven».
                }
            } catch (\Throwable $e) {
                $geo = null;
            }
        }

        $citySlug = Str::slug($city !== '' ? $city : (string) ($geo->city ?? '')) ?: 'unknown';
        $countryName = $country !== '' ? $country : (string) ($geo->country ?? 'Unknown');

        // La misma persona repitiendo la misma búsqueda en una hora cuenta una sola vez.
        $repetida = SearchLog::where('ip_address', $this->clientIp)
            ->where('specialty', $specialtySlug)
            ->where('city', $citySlug)
            ->where('created_at', '>=', now()->subHour())
            ->exists();
        if ($repetida) {
            return;
        }

        SearchLog::create([
            'specialty'  => $specialtySlug,
            'city'       => $citySlug,
            'country'    => $countryName,
            'ip_address' => $this->clientIp,
        ]);
    }
}
