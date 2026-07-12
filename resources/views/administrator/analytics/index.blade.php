@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('administrator.dashboard'),
    ],
    [
        'name' => 'Google Analytics',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6" x-data="{ activeTab: 'general', activeBusiness: 'appointments' }">
        
        <!-- Encabezado -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-200 pb-5">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900">📊 Panel de Analytics</h1>
                <p class="text-sm text-gray-500 mt-1">Métricas clave en los últimos 7 días | Últimas 24h</p>
            </div>
        </div>

        @if(isset($error))
            <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl text-sm flex items-start space-x-2">
                <span>⚠️</span>
                <p>{{ $error }}</p>
            </div>
        @else
            <!-- SECCIÓN 1: MÉTRICAS GENERALES -->
            <div class="space-y-4">
                <h2 class="text-lg font-bold text-gray-900">Métricas Generales</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Usuarios Activos</span>
                                <h3 class="text-3xl sm:text-4xl font-black text-indigo-600 mt-1">{{ $generalStats->sum('activeUsers') }}</h3>
                            </div>
                            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl text-2xl hidden sm:block">👥</div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Vistas de Página</span>
                                <h3 class="text-3xl sm:text-4xl font-black text-emerald-600 mt-1">{{ $generalStats->sum('screenPageViews') }}</h3>
                            </div>
                            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl text-2xl hidden sm:block">📄</div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico Diario -->
                <div class="bg-white p-4 sm:p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="text-sm font-bold text-gray-700 mb-6 uppercase tracking-wider">Tendencia de Vistas Diarias</h3>
                    <div class="flex items-end justify-between h-48 pt-4 gap-2 sm:gap-4 overflow-x-auto">
                        @foreach($generalStats as $day)
                            @php 
                                $percentage = ($day['screenPageViews'] / $maxViews) * 100;
                                $formattedDate = \Carbon\Carbon::parse($day['date'])->isoFormat('ddd DD');
                            @endphp
                            <div class="flex flex-col items-center flex-1 min-w-[45px] h-full justify-end group">
                                <span class="opacity-0 group-hover:opacity-100 transition-opacity bg-gray-800 text-white text-xs px-2 py-1 rounded mb-2 absolute transform -translate-y-16 pointer-events-none z-10">
                                    {{ $day['screenPageViews'] }} vistas
                                </span>
                                <div class="w-full bg-indigo-100 group-hover:bg-indigo-500 rounded-t-lg transition-all duration-300" 
                                    style="height: {{ max($percentage, 5) }}%"></div>
                                <span class="text-[10px] sm:text-xs text-gray-400 font-medium mt-2 text-center whitespace-nowrap">
                                    {{ $formattedDate }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: DASHBOARDS POR LÍNEA DE NEGOCIO -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-gray-200 pb-4">
                    <h2 class="text-lg font-bold text-gray-900">Líneas de Negocio</h2>
                </div>

                <!-- Selector de Línea de Negocio -->
                <div class="flex gap-2 bg-gray-100 p-2 rounded-xl w-fit">
                    <button @click="activeBusiness = 'appointments'" 
                        :class="activeBusiness === 'appointments' ? 'bg-white shadow-sm font-bold text-blue-600' : 'text-gray-600'"
                        class="px-4 py-2 text-sm rounded-lg transition-all">
                        📅 Citas Médicas
                    </button>
                    <button @click="activeBusiness = 'lab_analysis'" 
                        :class="activeBusiness === 'lab_analysis' ? 'bg-white shadow-sm font-bold text-green-600' : 'text-gray-600'"
                        class="px-4 py-2 text-sm rounded-lg transition-all">
                        🧬 Análisis IA
                    </button>
                </div>

                <!-- DASHBOARD: CITAS MÉDICAS -->
                <div x-show="activeBusiness === 'appointments'" x-transition class="space-y-4">
                    @if(!isset($appointmentMetrics['error']))
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <!-- KPI 1 -->
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-2xl border border-blue-200">
                                <div class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Total de Eventos</div>
                                <h3 class="text-3xl font-black text-blue-900 mt-2">{{ $appointmentMetrics['total_events'] }}</h3>
                                <p class="text-xs text-blue-600 mt-2">En los últimos 7 días</p>
                            </div>

                            <!-- KPI 2 -->
                            <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 p-6 rounded-2xl border border-indigo-200">
                                <div class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">Usuarios Únicos</div>
                                <h3 class="text-3xl font-black text-indigo-900 mt-2">{{ $appointmentMetrics['total_users'] }}</h3>
                                <p class="text-xs text-indigo-600 mt-2">Interacciones activas</p>
                            </div>

                            <!-- KPI 3: TASA DE CONVERSIÓN -->
                            <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 p-6 rounded-2xl border border-emerald-200">
                                <div class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Tasa de Conversión</div>
                                <h3 class="text-3xl font-black text-emerald-900 mt-2">{{ $appointmentMetrics['conversion_rate'] }}%</h3>
                                <p class="text-xs text-emerald-600 mt-2">Vista → Compra</p>
                            </div>
                        </div>

                        <!-- Desglose de Eventos -->
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                            <h3 class="text-sm font-bold text-gray-700 mb-4 uppercase tracking-wider">Desglose de Eventos</h3>
                            <div class="space-y-3">
                                @forelse($appointmentMetrics['events'] as $eventName => $count)
                                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded-xl hover:bg-blue-100 transition">
                                        <span class="text-sm font-semibold text-gray-700">{{ $eventName }}</span>
                                        <span class="text-sm font-bold text-blue-900 bg-white px-3 py-1 rounded-lg border border-blue-200">
                                            {{ $count }}
                                        </span>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500">Sin datos de eventos</p>
                                @endforelse
                            </div>
                        </div>
                    @else
                        <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-xl text-yellow-700 text-sm">
                            {{ $appointmentMetrics['error'] }}
                        </div>
                    @endif
                </div>

                <!-- DASHBOARD: ANÁLISIS DE LABORATORIO -->
                <div x-show="activeBusiness === 'lab_analysis'" x-transition class="space-y-4">
                    @if(!isset($labAnalysisMetrics['error']))
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <!-- KPI 1 -->
                            <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-2xl border border-green-200">
                                <div class="text-xs font-semibold text-green-600 uppercase tracking-wider">Total de Eventos</div>
                                <h3 class="text-3xl font-black text-green-900 mt-2">{{ $labAnalysisMetrics['total_events'] }}</h3>
                                <p class="text-xs text-green-600 mt-2">En los últimos 7 días</p>
                            </div>

                            <!-- KPI 2 -->
                            <div class="bg-gradient-to-br from-teal-50 to-teal-100 p-6 rounded-2xl border border-teal-200">
                                <div class="text-xs font-semibold text-teal-600 uppercase tracking-wider">Usuarios Únicos</div>
                                <h3 class="text-3xl font-black text-teal-900 mt-2">{{ $labAnalysisMetrics['total_users'] }}</h3>
                                <p class="text-xs text-teal-600 mt-2">Interacciones activas</p>
                            </div>

                            <!-- KPI 3: TASA DE CONVERSIÓN -->
                            <div class="bg-gradient-to-br from-cyan-50 to-cyan-100 p-6 rounded-2xl border border-cyan-200">
                                <div class="text-xs font-semibold text-cyan-600 uppercase tracking-wider">Tasa de Conversión</div>
                                <h3 class="text-3xl font-black text-cyan-900 mt-2">{{ $labAnalysisMetrics['conversion_rate'] }}%</h3>
                                <p class="text-xs text-cyan-600 mt-2">Upload → Completado</p>
                            </div>
                        </div>

                        <!-- Desglose de Eventos -->
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                            <h3 class="text-sm font-bold text-gray-700 mb-4 uppercase tracking-wider">Desglose de Eventos</h3>
                            <div class="space-y-3">
                                @forelse($labAnalysisMetrics['events'] as $eventName => $count)
                                    <div class="flex items-center justify-between p-3 bg-green-50 rounded-xl hover:bg-green-100 transition">
                                        <span class="text-sm font-semibold text-gray-700">{{ $eventName }}</span>
                                        <span class="text-sm font-bold text-green-900 bg-white px-3 py-1 rounded-lg border border-green-200">
                                            {{ $count }}
                                        </span>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500">Sin datos de eventos</p>
                                @endforelse
                            </div>
                        </div>
                    @else
                        <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-xl text-yellow-700 text-sm">
                            {{ $labAnalysisMetrics['error'] }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- SECCIÓN 3: TOP INFORMACIÓN GENERAL -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Páginas Más Vistas -->
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="text-sm font-bold text-gray-700 mb-4 uppercase tracking-wider">Top Páginas</h3>
                    <div class="space-y-3">
                        @foreach($mostVisited as $page)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition">
                                <div class="min-w-0 pr-2">
                                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $page['pageTitle'] }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $page['fullPageUrl'] }}</p>
                                </div>
                                <span class="text-sm font-bold text-gray-900 bg-white px-2.5 py-1 rounded-lg border border-gray-200 shrink-0">
                                    {{ $page['screenPageViews'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Fuentes de Tráfico -->
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="text-sm font-bold text-gray-700 mb-4 uppercase tracking-wider">Fuentes de Tráfico</h3>
                    <div class="space-y-3">
                        @foreach($topReferrers as $referrer)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition">
                                <span class="text-sm font-semibold text-gray-700 truncate pr-2">
                                    {{ $referrer['pageReferrer'] ?: 'Tráfico Directo' }}
                                </span>
                                <span class="text-sm font-bold text-gray-900 bg-white px-2.5 py-1 rounded-lg border border-gray-200 shrink-0">
                                    {{ $referrer['screenPageViews'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Países Principal -->
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="text-sm font-bold text-gray-700 mb-4 uppercase tracking-wider">Países de Origen</h3>
                    <div class="space-y-3">
                        @foreach($topCountries as $country)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition">
                                <span class="text-sm font-semibold text-gray-700">{{ $country['country'] }}</span>
                                <span class="text-sm font-bold text-gray-900 bg-white px-2.5 py-1 rounded-lg border border-gray-200 shrink-0">
                                    {{ $country['screenPageViews'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        @endif
    </div>
</x-admin-layout>