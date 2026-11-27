<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\Filter\StringFilter;
use Google\Analytics\Data\V1beta\Filter\StringFilter\MatchType;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\FilterExpressionList;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Illuminate\Support\Facades\Cache;

/**
 * Lecturas de Google Analytics para las líneas de negocio: UNA consulta para todos los eventos y UNA para
 * las páginas de las líneas sin eventos (en vez de una consulta por evento). Resultado en caché 10 minutos.
 * No sustituye ni modifica GoogleAnalyticsService (que sigue como estaba).
 */
final class GoogleAnalyticsReports
{
    private const TTL = 600;

    /** Prefijos de ruta que se consultan (líneas sin eventos de gtag). */
    public const PAGE_PREFIXES = ['/skin-analysis', '/chat-salud-ia'];

    private ?object $client = null;

    /** @return array<string, array{count:int, users:int}> */
    public function getEventCounts(string $start, string $end): array
    {
        return $this->cached("bl.events.$start.$end", function () use ($start, $end) {
            $request = (new RunReportRequest())
                ->setProperty($this->property())
                ->setDateRanges([new DateRange(['start_date' => $start, 'end_date' => $end])])
                ->setDimensions([new Dimension(['name' => 'eventName'])])
                ->setMetrics([new Metric(['name' => 'eventCount']), new Metric(['name' => 'totalUsers'])])
                ->setLimit(500);

            $out = [];

            foreach ($this->client()->runReport($request)->getRows() as $row) {
                $out[$row->getDimensionValues()[0]->getValue()] = [
                    'count' => (int) $row->getMetricValues()[0]->getValue(),
                    'users' => (int) $row->getMetricValues()[1]->getValue(),
                ];
            }

            return $out;
        });
    }

    /** @return array<string, array{views:int, users:int}> ruta (sin parámetros) => vistas */
    public function getPageViewsByPath(string $start, string $end): array
    {
        return $this->cached("bl.pages.$start.$end", function () use ($start, $end) {
            $filters = [];

            foreach (self::PAGE_PREFIXES as $prefix) {
                $filters[] = new FilterExpression(['filter' => new Filter([
                    'field_name' => 'pagePath',
                    'string_filter' => new StringFilter(['match_type' => MatchType::BEGINS_WITH, 'value' => $prefix]),
                ])]);
            }

            $request = (new RunReportRequest())
                ->setProperty($this->property())
                ->setDateRanges([new DateRange(['start_date' => $start, 'end_date' => $end])])
                ->setDimensions([new Dimension(['name' => 'pagePath'])])
                ->setMetrics([new Metric(['name' => 'screenPageViews']), new Metric(['name' => 'totalUsers'])])
                ->setDimensionFilter(new FilterExpression(['or_group' => new FilterExpressionList(['expressions' => $filters])]))
                ->setLimit(1000);

            $out = [];

            foreach ($this->client()->runReport($request)->getRows() as $row) {
                $path = (string) strtok($row->getDimensionValues()[0]->getValue(), '?#');
                $views = (int) $row->getMetricValues()[0]->getValue();
                $users = (int) $row->getMetricValues()[1]->getValue();
                $out[$path] = ['views' => ($out[$path]['views'] ?? 0) + $views, 'users' => max($out[$path]['users'] ?? 0, $users)];
            }

            return $out;
        });
    }

    public static function clearCache(): void
    {
        try {
            if (Cache::supportsTags()) {
                Cache::tags(['analytics'])->flush();
            }
        } catch (\Throwable) {
            // sin caché que limpiar
        }
    }

    private function cached(string $key, callable $fn): array
    {
        $repo = Cache::supportsTags() ? Cache::tags(['analytics']) : Cache::store();

        return $repo->remember($key, self::TTL, $fn);
    }

    private function property(): string
    {
        $id = (string) config('analytics.property_id');

        if ($id === '') {
            throw new \RuntimeException('Falta configurar el ID de propiedad de Google Analytics (analytics.property_id).');
        }

        return 'properties/' . $id;
    }

    /** La librería de Google cambió de carpeta el cliente según la versión; se usa el que exista. */
    public static function clientClass(): ?string
    {
        foreach (['Google\\Analytics\\Data\\V1beta\\Client\\BetaAnalyticsDataClient', 'Google\\Analytics\\Data\\V1beta\\BetaAnalyticsDataClient'] as $c) {
            if (class_exists($c)) {
                return $c;
            }
        }

        return null;
    }

    private function client(): object
    {
        if ($this->client === null) {
            $path = (string) config('analytics.service_account_credentials_json', '');

            if ($path === '' || !is_file($path)) {
                $path = storage_path('app/analytics/service-account-credentials.json');
            }

            if (!is_file($path)) {
                throw new \RuntimeException('No se encontró el archivo de credenciales de Google Analytics.');
            }

            $class = self::clientClass();

            if ($class === null) {
                throw new \RuntimeException('No se encontró la clase BetaAnalyticsDataClient de Google en vendor.');
            }

            $this->client = new $class(['credentials' => $path]);
        }

        return $this->client;
    }
}
