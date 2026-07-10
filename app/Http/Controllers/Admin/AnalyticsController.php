<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Analytics\Facades\Analytics;
use Spatie\Analytics\Period;

class AnalyticsController extends Controller
{
    public function index()
    {
        try {
            $period = Period::days(7);

            // 1. Visitas diarias (Calculamos el máximo para escalar la gráfica en Tailwind)
            $generalStats = Analytics::fetchVisitorsAndPageViewsByDate($period);
            $maxViews = $generalStats->max('screenPageViews') ?: 1;

            // 2. Tablas complementarias (Top 5)
            $mostVisited = Analytics::fetchMostVisitedPages($period, 5);
            $topReferrers = Analytics::fetchTopReferrers($period, 5);
            $topCountries = Analytics::fetchTopCountries($period, 5);

            return view('administrator.analytics.index', compact('generalStats', 'maxViews', 'mostVisited', 'topReferrers', 'topCountries'));
            
        } catch (\Exception $e) {
            return view('administrator.analytics.index')->with('error', 'Error en la API de Google: ' . $e->getMessage());
        }
    }
}
