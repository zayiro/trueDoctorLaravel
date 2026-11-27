{{-- Líneas de negocio: se dibuja una pestaña por cada línea de config/business_lines.php --}}
<style>
    .bl-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1rem; min-width: 0; }
    .bl-tabs { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
    .bl-tab { border: 1px solid #e2e8f0; background: #f8fafc; color: #334155; border-radius: 999px; padding: .5rem .9rem; font-size: .85rem; font-weight: 700; cursor: pointer; }
    .bl-tab.is-on { color: #fff; border-color: transparent; }
    .bl-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr)); gap: .75rem; margin-bottom: 1rem; }
    .bl-kpi { border-radius: .875rem; padding: .875rem 1rem; min-width: 0; }
    .bl-kpi b { display: block; font-size: 1.6rem; line-height: 1.1; color: #0f172a; }
    .bl-kpi span { display: block; font-size: .75rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .03em; margin-top: .25rem; }
    .bl-kpi small { display: block; font-size: .7rem; color: #64748b; margin-top: .125rem; }
    .bl-h3 { font-size: .8rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: .04em; margin: 1.1rem 0 .5rem; }
    .bl-step { margin-bottom: .6rem; }
    .bl-step-top { display: flex; justify-content: space-between; gap: .75rem; font-size: .85rem; color: #334155; }
    .bl-step-top span:first-child { min-width: 0; overflow-wrap: anywhere; }
    .bl-step-top b { white-space: nowrap; color: #0f172a; }
    .bl-track { height: .5rem; background: #f1f5f9; border-radius: 999px; overflow: hidden; margin-top: .25rem; }
    .bl-fill { height: 100%; border-radius: 999px; }
    .bl-step small { display: block; font-size: .7rem; color: #94a3b8; margin-top: .15rem; }
    .bl-side { display: grid; grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr)); gap: .5rem; }
    .bl-side div { border: 1px solid #e2e8f0; border-radius: .75rem; padding: .6rem .8rem; font-size: .8rem; color: #475569; }
    .bl-side b { display: block; font-size: 1.1rem; color: #0f172a; }
    .bl-note { font-size: .75rem; color: #64748b; background: #f8fafc; border-radius: .6rem; padding: .5rem .75rem; margin-top: .5rem; overflow-wrap: anywhere; }
    .bl-warn { font-size: .8rem; background: #fffbeb; border: 1px solid #fde68a; color: #92400e; border-radius: .6rem; padding: .5rem .75rem; margin-bottom: .75rem; overflow-wrap: anywhere; }
    .bl-empty { font-size: .85rem; color: #64748b; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: .75rem; padding: .75rem 1rem; margin-bottom: .75rem; }
</style>

<div class="bl-card" x-data="{ line: '{{ array_key_first($lines) }}' }">
    <h2 class="an-h2" style="margin-bottom:.75rem">Líneas de negocio</h2>

    @if (empty($lines))
        <p class="bl-empty">No hay líneas de negocio para mostrar.</p>
    @else
        <div class="bl-tabs">
            @foreach ($lines as $key => $l)
                <button type="button" class="bl-tab" x-on:click="line = '{{ $key }}'"
                        x-bind:class="line === '{{ $key }}' ? 'is-on' : ''"
                        x-bind:style="line === '{{ $key }}' ? 'background: {{ $l['color'] }}' : ''">{{ $l['emoji'] }} {{ $l['label'] }}</button>
            @endforeach
        </div>

        @foreach ($lines as $key => $l)
            <div x-show="line === '{{ $key }}'" @if (!$loop->first) x-cloak style="display:none" @endif>
                @if ($l['error'])
                    <div class="bl-warn">⚠️ No se pudieron leer los datos de Google Analytics de esta línea: {{ $l['error'] }}</div>
                @elseif (!$l['has_data'])
                    <div class="bl-empty">Todavía no hay actividad de «{{ $l['label'] }}» en los últimos 7 días.</div>
                @endif
                @if ($l['business_error'])
                    <div class="bl-warn">⚠️ {{ $l['business_error'] }}</div>
                @endif

                @if (!empty($l['kpis']))
                    <div class="bl-kpis">
                        @foreach ($l['kpis'] as $k)
                            <div class="bl-kpi" style="background: {{ $l['soft'] }}">
                                <b>{{ is_numeric($k['value']) ? number_format($k['value'], 0, ',', '.') : $k['value'] }}</b>
                                <span>{{ $k['label'] }}</span>
                                <small>{{ $k['hint'] }}</small>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if (!empty($l['business']))
                    <h3 class="bl-h3">Datos reales (base de datos)</h3>
                    <div class="bl-kpis">
                        @foreach ($l['business'] as $b)
                            <div class="bl-kpi" style="background: #f8fafc; border: 1px solid #e2e8f0">
                                <b>{{ $b['format'] === 'money' ? '$' . number_format($b['value'], 0, ',', '.') : number_format($b['value'], 0, ',', '.') }}</b>
                                <span>{{ $b['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if (!empty($l['funnel']) && !$l['error'])
                    <h3 class="bl-h3">Embudo</h3>
                    @foreach ($l['funnel'] as $s)
                        <div class="bl-step">
                            <div class="bl-step-top"><span>{{ $loop->iteration }}. {{ $s['label'] }}</span><b>{{ number_format($s['count'], 0, ',', '.') }}</b></div>
                            <div class="bl-track"><div class="bl-fill" style="width: {{ min(100, $s['pct_first']) }}%; background: {{ $l['color'] }}"></div></div>
                            <small>
                                {{ number_format($s['users'], 0, ',', '.') }} usuarios
                                @if ($s['pct_prev'] !== null) · {{ number_format($s['pct_prev'], 1, ',', '.') }}% del paso anterior @endif
                                · <code>{{ $s['code'] }}</code>
                            </small>
                        </div>
                    @endforeach
                @endif

                @if (!empty($l['side']) && !$l['error'])
                    <h3 class="bl-h3">Otros eventos</h3>
                    <div class="bl-side">
                        @foreach ($l['side'] as $s)
                            <div><b>{{ number_format($s['count'], 0, ',', '.') }}</b>{{ $s['label'] }}</div>
                        @endforeach
                    </div>
                @endif

                @foreach ($l['notes'] as $n)
                    <p class="bl-note">ℹ️ {{ $n }}</p>
                @endforeach
            </div>
        @endforeach
    @endif
</div>
