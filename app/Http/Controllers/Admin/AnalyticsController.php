<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GoogleAnalyticsService;
use Spatie\Analytics\Facades\Analytics;
use Spatie\Analytics\Period;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    protected GoogleAnalyticsService $gaService;

    public function __construct(GoogleAnalyticsService $gaService)
    {
        $this->gaService = $gaService;
    }

    public function index()
    {
        try {
            $period = Period::days(7);

            // === DATOS GENERALES (Spatie Analytics) ===
            $generalStats = Analytics::fetchVisitorsAndPageViewsByDate($period);
            $maxViews = $generalStats->max('screenPageViews') ?: 1;
            $mostVisited = Analytics::fetchMostVisitedPages($period, 5);
            $topReferrers = Analytics::fetchTopReferrers($period, 5);
            $topCountries = Analytics::fetchTopCountries($period, 5);

            // === CITAS MÉDICAS (Google Analytics API) ===
            $appointmentMetrics = $this->gaService->getBusinessLineMetrics(
                [
                    'search_specialist',
                    'view_item_list',
                    'view_item',
                    'begin_checkout',
                    'add_shipping_info',
                    'purchase',
                    'view_confirmation',
                    'payment_processed',
                    'payment_failed'
                ],
                $period->startDate->format('Y-m-d'),
                $period->endDate->format('Y-m-d')
            );

            // Calcular conversión específica de citas
            $appointmentConversion = $this->gaService->getConversionBetweenEvents(
                'search_specialist',
                'purchase',
                $period->startDate->format('Y-m-d'),
                $period->endDate->format('Y-m-d')
            );
            $appointmentMetrics['funnel_conversion'] = $appointmentConversion['conversion_rate'] ?? 0;

            // === ANÁLISIS IA (Google Analytics API) ===
            $labAnalysisMetrics = $this->gaService->getBusinessLineMetrics(
                [
                    'view_medical_analysis_landing',
                    'start_medical_analysis',
                    'view_medical_analysis_index',
                    'start_medical_analysis_upload',
                    'view_medical_analysis_upload',
                    'add_lab_report',
                    'begin_lab_analysis',
                    'complete_lab_analysis',
                    'lab_analysis_payment_processed',
                    'lab_analysis_payment_failed'
                ],
                $period->startDate->format('Y-m-d'),
                $period->endDate->format('Y-m-d')
            );

            // Calcular conversión específica de análisis
            $labConversion = $this->gaService->getConversionBetweenEvents(
                'view_medical_analysis_landing',
                'complete_lab_analysis',
                $period->startDate->format('Y-m-d'),
                $period->endDate->format('Y-m-d')
            );
            $labAnalysisMetrics['funnel_conversion'] = $labConversion['conversion_rate'] ?? 0;

            return view('administrator.analytics.index', compact(
                'generalStats',
                'maxViews',
                'mostVisited',
                'topReferrers',
                'topCountries',
                'appointmentMetrics',
                'labAnalysisMetrics',
                'period'
            ));

        } catch (\Exception $e) {
            /*Log::error('Error en Analytics Dashboard', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);*/

            return view('administrator.analytics.index')
                ->with('error', 'Error cargando analytics: ' . $e->getMessage());
        }
    }
}