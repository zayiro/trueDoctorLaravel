<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\BusinessLineDb;
use App\Services\Analytics\BusinessLineReport;
use App\Services\Analytics\GoogleAnalyticsReports;
use Illuminate\Support\Facades\Log;
use Spatie\Analytics\Facades\Analytics;
use Spatie\Analytics\Period;

class AnalyticsController extends Controller
{
    public function index()
    {
        $errors = [];

        // Los últimos 7 días, contando hoy
        $end = now()->toDateString();
        $start = now()->subDays(6)->toDateString();

        // 1) Métricas generales (Spatie Analytics)
        $generalStats = collect();
        $maxViews = 1;
        $mostVisited = $topReferrers = $topCountries = collect();

        try {
            $period = Period::days(7);

            $generalStats = Analytics::fetchVisitorsAndPageViewsByDate($period)
                ->groupBy(fn ($row) => data_get($row, 'date') instanceof \DateTimeInterface ? data_get($row, 'date')->format('Y-m-d') : (string) data_get($row, 'date'))
                ->map(fn ($rows, $date) => [
                    'date' => $date,
                    'views' => (int) collect($rows)->sum(fn ($r) => (int) data_get($r, 'screenPageViews', 0)),
                    'users' => (int) collect($rows)->sum(fn ($r) => (int) data_get($r, 'activeUsers', 0)),
                ])
                ->sortKeys()
                ->values();

            $maxViews = max(1, (int) $generalStats->max('views'));
            $mostVisited = Analytics::fetchMostVisitedPages($period, 5);
            $topReferrers = Analytics::fetchTopReferrers($period, 5);
            $topCountries = Analytics::fetchTopCountries($period, 5);
        } catch (\Throwable $e) {
            Log::error('Analytics (general): ' . $e->getMessage());
            $errors[] = 'No se pudieron cargar las métricas generales de Google Analytics: ' . $e->getMessage();
        }

        // 2) Líneas de negocio (config/business_lines.php): una consulta a GA por tipo de dato, no una por evento
        $lines = [];

        try {
            $report = new BusinessLineReport(app(GoogleAnalyticsReports::class), new BusinessLineDb());
            $lines = $report->build((array) config('business_lines', []), $start, $end);
        } catch (\Throwable $e) {
            Log::error('Analytics (líneas de negocio): ' . $e->getMessage());
            $errors[] = 'No se pudieron cargar las líneas de negocio: ' . $e->getMessage();
        }

        return view('administrator.analytics.index', [
            'generalStats' => $generalStats,
            'maxViews' => $maxViews,
            'mostVisited' => $mostVisited,
            'topReferrers' => $topReferrers,
            'topCountries' => $topCountries,
            'lines' => $lines,
            'rangeStart' => $start,
            'rangeEnd' => $end,
            'errors' => $errors,
        ]);
    }
}
