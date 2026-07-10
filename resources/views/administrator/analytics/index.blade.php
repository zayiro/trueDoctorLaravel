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
    <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6" x-data="{ activeTab: 'pages' }">
        
        <!-- Encabezado -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-200 pb-5">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900">Panel de Analytics</h1>
                <p class="text-sm text-gray-500 mt-1">Métricas clave del sitio en los últimos 7 días.</p>
            </div>
        </div>

        @if(isset($error))
            <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl text-sm flex items-start space-x-2">
                <span>⚠️</span>
                <p>{{ $error }}</p>
            </div>
        @else
            <!-- Tarjetas de Métricas Principales (Grid Responsivo) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Usuarios Activos</span>
                        <h3 class="text-3xl sm:text-4xl font-black text-indigo-600 mt-1">{{ $generalStats->sum('activeUsers') }}</h3>
                    </div>
                    <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl text-xl hidden sm:block">👥</div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Vistas de Página</span>
                        <h3 class="text-3xl sm:text-4xl font-black text-emerald-600 mt-1">{{ $generalStats->sum('screenPageViews') }}</h3>
                    </div>
                    <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl text-xl hidden sm:block">📄</div>
                </div>
            </div>

            <!-- Gráfico Diario hecho con Tailwind y Alpine.js (Responsivo) -->
            <div class="bg-white p-4 sm:p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="text-sm font-bold text-gray-700 mb-6 uppercase tracking-wider">Tendencia de Vistas Diarias</h3>
                <div class="flex items-end justify-between h-48 pt-4 gap-2 sm:gap-4 overflow-x-auto">
                    @foreach($generalStats as $day)
                        @php 
                            $percentage = ($day['screenPageViews'] / $maxViews) * 100;
                            $formattedDate = \Carbon\Carbon::parse($day['date'])->isoFormat('ddd DD');
                        @endphp
                        <div class="flex flex-col items-center flex-1 min-w-[45px] h-full justify-end group">
                            <!-- Tooltip Alpine / Tailwind -->
                            <span class="opacity-0 group-hover:opacity-100 transition-opacity bg-gray-800 text-white text-xs px-2 py-1 rounded mb-2 absolute transform -translate-y-16 pointer-events-none z-10">
                                {{ $day['screenPageViews'] }} vistas
                            </span>
                            <!-- Barra -->
                            <div class="w-full bg-indigo-100 group-hover:bg-indigo-500 rounded-t-lg transition-all duration-300" 
                                style="height: {{ max($percentage, 5) }}%"></div>
                            <!-- Etiqueta de fecha -->
                            <span class="text-[10px] sm:text-xs text-gray-400 font-medium mt-2 text-center whitespace-nowrap">
                                {{ $formattedDate }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Secciones Detalladas: Pestañas en Móvil, Grid Completo en Escritorio -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Selector de Pestañas exclusivo para móviles -->
                <div class="flex sm:hidden bg-gray-100 p-1 rounded-xl lg:hidden">
                    <button @click="activeTab = 'pages'" :class="activeTab === 'pages' ? 'bg-white shadow-sm font-bold text-indigo-600' : 'text-gray-500'" class="flex-1 py-2 text-xs rounded-lg transition-all">Páginas</button>
                    <button @click="activeTab = 'traffic'" :class="activeTab === 'traffic' ? 'bg-white shadow-sm font-bold text-indigo-600' : 'text-gray-500'" class="flex-1 py-2 text-xs rounded-lg transition-all">Tráfico</button>
                    <button @click="activeTab = 'countries'" :class="activeTab === 'countries' ? 'bg-white shadow-sm font-bold text-indigo-600' : 'text-gray-500'" class="flex-1 py-2 text-xs rounded-lg transition-all">Países</button>
                </div>

                <!-- Columna 1: Páginas Más Vistas -->
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100" 
                    x-show="window.innerWidth >= 640 || activeTab === 'pages'">
                    <h3 class="text-sm font-bold text-gray-700 mb-4 uppercase tracking-wider">Top Páginas</h3>
                    <div class="space-y-3">
                        @foreach($mostVisited as $page)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="min-w-0 pr-2">
                                    <p class="text-sm font-semibold text-gray-800 truncate" title="{{ $page['pageTitle'] }}">
                                        {{ $page['pageTitle'] }}
                                    </p>
                                    <p class="text-xs text-gray-400 truncate">{{ $page['fullPageUrl'] }}</p>
                                </div>
                                <span class="text-sm font-bold text-gray-900 bg-white px-2.5 py-1 rounded-lg border border-gray-200 shadow-sm shrink-0">
                                    {{ $page['screenPageViews'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Columna 2: Fuentes de Tráfico -->
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100" 
                    x-show="window.innerWidth >= 640 || activeTab === 'traffic'">
                    <h3 class="text-sm font-bold text-gray-700 mb-4 uppercase tracking-wider">Fuentes de Tráfico</h3>
                    <div class="space-y-3">
                        @foreach($topReferrers as $referrer)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <span class="text-sm font-semibold text-gray-700 truncate pr-2">
                                    {{ $referrer['pageReferrer'] ?: 'Tráfico Directo' }}
                                </span>
                                <span class="text-sm font-bold text-gray-900 bg-white px-2.5 py-1 rounded-lg border border-gray-200 shadow-sm shrink-0">
                                    {{ $referrer['screenPageViews'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Columna 3: Países Principales -->
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100" 
                    x-show="window.innerWidth >= 640 || activeTab === 'countries'">
                    <h3 class="text-sm font-bold text-gray-700 mb-4 uppercase tracking-wider">Países de Origen</h3>
                    <div class="space-y-3">
                        @foreach($topCountries as $country)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <span class="text-sm font-semibold text-gray-700">{{ $country['country'] }}</span>
                                <span class="text-sm font-bold text-gray-900 bg-white px-2.5 py-1 rounded-lg border border-gray-200 shadow-sm shrink-0">
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