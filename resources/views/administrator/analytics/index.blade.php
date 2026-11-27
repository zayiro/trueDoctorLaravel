<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('administrator.dashboard')],
    ['name' => 'Google Analytics'],
]">
    <style>
        .an-wrap { max-width: 80rem; margin: 0 auto; padding: 1rem; display: flex; flex-direction: column; gap: 1.5rem; min-width: 0; }
        .an-wrap * { box-sizing: border-box; }
        .an-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; }
        .an-sub { font-size: .875rem; color: #64748b; margin: .25rem 0 0; }
        .an-err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: .75rem; padding: .75rem 1rem; font-size: .875rem; overflow-wrap: anywhere; }
        .an-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1rem; min-width: 0; }
        .an-h2 { font-size: 1rem; font-weight: 800; color: #0f172a; margin: 0 0 .75rem; }
        .an-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr)); gap: .75rem; }
        .an-kpi { border: 1px solid #e2e8f0; border-radius: .875rem; padding: .875rem 1rem; background: #f8fafc; min-width: 0; }
        .an-kpi b { display: block; font-size: 1.6rem; line-height: 1.1; color: #0f172a; }
        .an-kpi span { display: block; font-size: .75rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .03em; margin-top: .25rem; }
        .an-kpi small { display: block; font-size: .7rem; color: #94a3b8; margin-top: .125rem; }
        .an-bars { display: flex; align-items: flex-end; gap: .5rem; height: 9rem; }
        .an-bar { flex: 1 1 0; min-width: 0; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; }
        .an-bar i { display: block; width: 100%; background: #6366f1; border-radius: .375rem .375rem 0 0; min-height: 2px; }
        .an-bar em { font-style: normal; font-size: .65rem; color: #64748b; margin-top: .25rem; white-space: nowrap; }
        .an-bar strong { font-size: .65rem; color: #334155; }
        .an-three { display: grid; grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr)); gap: 1rem; }
        .an-li { display: flex; justify-content: space-between; gap: .75rem; padding: .4rem 0; border-bottom: 1px solid #f1f5f9; font-size: .85rem; color: #334155; }
        .an-li:last-child { border-bottom: 0; }
        .an-li span:first-child { overflow-wrap: anywhere; min-width: 0; }
        .an-li span:last-child { font-weight: 700; white-space: nowrap; }
        .an-empty { font-size: .85rem; color: #94a3b8; }
    </style>

    <div class="an-wrap">
        <div>
            <h1 class="an-title">📊 Panel de Analytics</h1>
            <p class="an-sub">Métricas clave de los últimos 7 días ({{ \Carbon\Carbon::parse($rangeStart)->format('d/m') }} – {{ \Carbon\Carbon::parse($rangeEnd)->format('d/m/Y') }}). Se actualiza cada 10 minutos.</p>
        </div>

        @foreach ($errors as $msg)
            <div class="an-err">⚠️ {{ $msg }}</div>
        @endforeach

        {{-- 1. Métricas generales --}}
        <div class="an-card">
            <h2 class="an-h2">Visitas por día</h2>
            @if ($generalStats->isEmpty())
                <p class="an-empty">Aún no hay datos de visitas para este período.</p>
            @else
                <div class="an-kpis" style="margin-bottom:1rem">
                    <div class="an-kpi"><b>{{ number_format($generalStats->sum('views'), 0, ',', '.') }}</b><span>Vistas de página</span></div>
                    <div class="an-kpi"><b>{{ number_format($generalStats->sum('users'), 0, ',', '.') }}</b><span>Usuarios activos</span><small>Suma de los 7 días</small></div>
                </div>
                <div class="an-bars">
                    @foreach ($generalStats as $day)
                        <div class="an-bar" title="{{ $day['date'] }}: {{ $day['views'] }} vistas">
                            <strong>{{ $day['views'] }}</strong>
                            <i style="height: {{ max(2, round($day['views'] / $maxViews * 100)) }}%"></i>
                            <em>{{ \Carbon\Carbon::parse($day['date'])->format('d/m') }}</em>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- 2. Líneas de negocio --}}
        @include('administrator.analytics.partials.business-lines', ['lines' => $lines])

        {{-- 3. Páginas, orígenes y países --}}
        <div class="an-three">
            <div class="an-card">
                <h2 class="an-h2">Páginas más visitadas</h2>
                @forelse ($mostVisited as $row)
                    <div class="an-li"><span>{{ data_get($row, 'pageTitle') ?: data_get($row, 'fullPageUrl', '—') }}</span><span>{{ number_format((int) data_get($row, 'screenPageViews', 0), 0, ',', '.') }}</span></div>
                @empty
                    <p class="an-empty">Sin datos todavía.</p>
                @endforelse
            </div>
            <div class="an-card">
                <h2 class="an-h2">Principales orígenes</h2>
                @forelse ($topReferrers as $row)
                    <div class="an-li"><span>{{ data_get($row, 'pageReferrer') ?: 'Directo' }}</span><span>{{ number_format((int) data_get($row, 'screenPageViews', 0), 0, ',', '.') }}</span></div>
                @empty
                    <p class="an-empty">Sin datos todavía.</p>
                @endforelse
            </div>
            <div class="an-card">
                <h2 class="an-h2">Países</h2>
                @forelse ($topCountries as $row)
                    <div class="an-li"><span>{{ data_get($row, 'country') ?: '—' }}</span><span>{{ number_format((int) data_get($row, 'screenPageViews', 0), 0, ',', '.') }}</span></div>
                @empty
                    <p class="an-empty">Sin datos todavía.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-admin-layout>
