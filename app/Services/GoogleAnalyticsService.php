<?php

namespace App\Services;

use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\Filter;

use Google\Analytics\Data\V1beta\Filter\StringFilter;
use Google\Analytics\Data\V1beta\Filter\StringFilter\MatchType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class GoogleAnalyticsService
{
    private BetaAnalyticsDataClient $client;
    private string $propertyId;
    private const CACHE_TTL = 3600; // 1 hora

    /**
     * Inicializar el cliente de Google Analytics
     */
    public function __construct()
    {
        try {
            $this->propertyId = config('analytics.property_id');
            
            if (!$this->propertyId) {
                throw new Exception('Google Analytics Property ID no configurado');
            }

            $this->client = new BetaAnalyticsDataClient([
                'credentials' => $this->getCredentials()
            ]);
        } catch (Exception $e) {
            /*Log::error('Error inicializando Google Analytics Service', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);*/
            throw $e;
        }
    }

    /**
     * Obtener credenciales de la configuración
     */
    private function getCredentials()
    {
        $credentialsPath = config('analytics.service_account_credentials_json');
        
        if (!$credentialsPath || !file_exists($credentialsPath)) {
            throw new Exception('Archivo de credenciales de Google Analytics no encontrado');
        }

        return $credentialsPath;
    }

    /**
     * Obtener métricas de eventos personalizados
     * 
     * @param array $eventNames Nombres de eventos a filtrar
     * @param string $startDate Fecha inicio (Y-m-d)
     * @param string $endDate Fecha fin (Y-m-d)
     * @return array
     */
    public function getEventMetrics($eventNames, $startDate, $endDate)
    {
        // Generar key de caché
        $cacheKey = "ga_events_" . md5(implode(',', $eventNames) . $startDate . $endDate);

        // Intentar recuperar del caché
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $metrics = $this->fetchEventMetrics($eventNames, $startDate, $endDate);
            
            // Guardar en caché por 1 hora
            Cache::put($cacheKey, $metrics, self::CACHE_TTL);
            
            return $metrics;

        } catch (Exception $e) {
            /*Log::error('Error obteniendo métricas de eventos', [
                'events' => $eventNames,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);*/

            return [
                'error' => $e->getMessage(),
                'total_events' => 0,
                'total_users' => 0,
                'conversion_rate' => 0,
                'events' => [],
                'daily_data' => []
            ];
        }
    }

    /**
     * Obtener métricas de página específicas
     */
    public function getPageMetrics($pagePath, $startDate, $endDate)
    {
        $cacheKey = "ga_page_" . md5($pagePath . $startDate . $endDate);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $request = new RunReportRequest();
            $request->setProperty("properties/{$this->propertyId}");

            $request->setDateRanges([
                new DateRange([
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ])
            ]);

            $request->setDimensions([
                new Dimension(['name' => 'pagePath']),
                new Dimension(['name' => 'date']),
            ]);

            $request->setMetrics([
                new Metric(['name' => 'screenPageViews']),
                new Metric(['name' => 'totalUsers']),
            ]);

            // Filtro por página específica
            $filterExpression = new FilterExpression();
            $filterExpression->setFilter(new Filter([
                'field_name' => 'pagePath',
                'string_filter' => new StringFilter([
                    'match_type' => 'CONTAINS',
                    'value' => $pagePath,
                ])
            ]));
            $request->setWhereFilter($filterExpression);

            $response = $this->client->runReport($request);

            $metrics = [
                'total_views' => 0,
                'total_users' => 0,
                'daily_data' => [],
                'average_views_per_day' => 0
            ];

            $days = [];

            foreach ($response->getRows() as $row) {
                $date = $row->getDimensionValues()[1]->getValue();
                $views = (int)$row->getMetricValues()[0]->getValue();
                $users = (int)$row->getMetricValues()[1]->getValue();

                $metrics['total_views'] += $views;
                $metrics['total_users'] += $users;
                $metrics['daily_data'][$date] = $views;
                $days[] = $views;
            }

            if (!empty($days)) {
                $metrics['average_views_per_day'] = round(array_sum($days) / count($days), 2);
            }

            Cache::put($cacheKey, $metrics, self::CACHE_TTL);
            return $metrics;

        } catch (Exception $e) {
            /*Log::error('Error obteniendo métricas de página', [
                'page' => $pagePath,
                'error' => $e->getMessage()
            ]);*/

            return [
                'error' => $e->getMessage(),
                'total_views' => 0,
                'total_users' => 0,
                'daily_data' => []
            ];
        }
    }

    /**
     * Obtener conversión entre dos eventos
     */
    public function getConversionBetweenEvents($initialEvent, $finalEvent, $startDate, $endDate)
    {
        $cacheKey = "ga_conversion_" . md5($initialEvent . $finalEvent . $startDate . $endDate);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $initialCount = $this->getEventCount($initialEvent, $startDate, $endDate);
            $finalCount = $this->getEventCount($finalEvent, $startDate, $endDate);

            $initialCount = (int)($initialCount ?? 0);
            $finalCount = (int)($finalCount ?? 0);
            $conversionRate = $initialCount > 0 ? round(($finalCount / $initialCount) * 100, 2) : 0;

            $result = [
                'initial_event' => $initialEvent,
                'initial_count' => $initialCount,
                'final_event' => $finalEvent,
                'final_count' => $finalCount,
                'conversion_rate' => $conversionRate
            ];

            Cache::put($cacheKey, $result, self::CACHE_TTL);
            return $result;

        } catch (Exception $e) {
            /*Log::error('Error calculando conversión', [
                'initial' => $initialEvent,
                'final' => $finalEvent,
                'error' => $e->getMessage()
            ]);*/

            return [
                'error' => $e->getMessage(),
                'conversion_rate' => 0
            ];
        }
    }

    /**
     * Método privado: obtener conteo de un evento específico
     */
    private function getEventCount($eventName, $startDate, $endDate)
    {
        $request = new RunReportRequest();
        $request->setProperty("properties/{$this->propertyId}");

        $request->setDateRanges([
            new DateRange([
                'start_date' => $startDate,
                'end_date' => $endDate,
            ])
        ]);

        $request->setMetrics([
            new Metric(['name' => 'eventCount']),
        ]);

        // Filtro por evento específico
        $filterExpression = new FilterExpression();
        $filterExpression->setFilter(new Filter([
            'field_name' => 'eventName',
            'string_filter' => new StringFilter([
                'match_type' => 'EXACT',
                'value' => $eventName,
            ])
        ]));
        $request->setWhereFilter($filterExpression);

        $response = $this->client->runReport($request);

        $count = 0;
        foreach ($response->getRows() as $row) {
            $count += (int)$row->getMetricValues()[0]->getValue();
        }

        return $count;
    }

    /**
     * Obtener métricas completas de una línea de negocio
     */
    public function getBusinessLineMetrics($eventNames, $startDate, $endDate)
    {
        try {
            $request = new RunReportRequest();
            $request->setProperty("properties/{$this->propertyId}");

            $request->setDateRanges([
                new DateRange([
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ])
            ]);

            $request->setDimensions([
                new Dimension(['name' => 'eventName']),
            ]);

            $request->setMetrics([
                new Metric(['name' => 'eventCount']),
                new Metric(['name' => 'totalUsers']),
            ]);

            $response = $this->client->runReport($request);

            $metrics = [
                'total_events' => 0,
                'total_users' => 0,
                'events' => [],
                'conversion_rate' => 0
            ];

            foreach ($response->getRows() as $row) {
                $eventName = $row->getDimensionValues()[0]->getValue();
                
                // Solo incluir eventos que nos interesan
                if (in_array($eventName, $eventNames)) {
                    $eventCount = (int)$row->getMetricValues()[0]->getValue();
                    $users = (int)$row->getMetricValues()[1]->getValue();

                    $metrics['total_events'] += $eventCount;
                    $metrics['total_users'] = max($metrics['total_users'], $users);
                    $metrics['events'][$eventName] = $eventCount;
                }
            }

            // Calcular tasa de conversión (primer evento vs último)
            if (!empty($metrics['events'])) {
                $eventValues = array_values($metrics['events']);
                $firstEvent = reset($eventValues);
                $lastEvent = end($eventValues);
                $metrics['conversion_rate'] = $firstEvent > 0 ? round(($lastEvent / $firstEvent) * 100, 2) : 0;
            }

            return $metrics;

        } catch (Exception $e) {
            /*Log::error('Error obteniendo métricas de línea de negocio', [
                'events' => $eventNames,
                'error' => $e->getMessage()
            ]);*/

            return [
                'error' => $e->getMessage(),
                'total_events' => 0,
                'total_users' => 0,
                'conversion_rate' => 0,
                'events' => []
            ];
        }
    }

    /**
     * Limpiar caché de todas las métricas
     */
    public static function clearCache()
    {
        Cache::tags(['analytics'])->flush();
    }
}