@php
    // Solo administradores; nunca debe romper el dashboard.
    $aiAlerts = $alerts ?? [];
    $aiChecked = null;
    if (! isset($alerts)) {
        try {
            $aiAlerts = \Illuminate\Support\Facades\Cache::remember('ai-model-alerts', 600, fn () => app(\App\Services\AiModels\AiModelMonitor::class)->alerts());
            $aiChecked = app(\App\Services\AiModels\AiModelMonitor::class)->liveCheck()['checked_at'];
        } catch (\Throwable $e) {
            $aiAlerts = [];
        }
    }
@endphp

@if (! empty($aiAlerts))
    <div class="m-3 mb-5 space-y-3">
        @foreach ($aiAlerts as $a)
            @php
                $red = in_array($a['status'], ['expired', 'missing'], true);
                $when = $a['retires_at'] ? \Carbon\Carbon::parse($a['retires_at'])->format('d/m/Y') : null;
            @endphp
            <div class="rounded-2xl border p-4 {{ $red ? 'bg-red-50 border-red-300 text-red-900' : 'bg-amber-50 border-amber-300 text-amber-900' }}" role="alert">
                <p class="font-bold">
                    {{ $red ? '⛔' : '⚠️' }}
                    @if ($a['status'] === 'expired')
                        {{ $a['label'] }} ya fue retirado ({{ $when }}): las funciones que lo usan pueden estar fallando.
                    @elseif ($a['status'] === 'missing')
                        {{ $a['label'] }} ya no responde en la API: las funciones que lo usan pueden estar fallando.
                    @else
                        {{ $a['label'] }} se retira el {{ $when }} (en {{ $a['days'] }} {{ $a['days'] === 1 ? 'día' : 'días' }}).
                    @endif
                </p>
                <p class="text-sm mt-1">
                    Se usa en: {{ implode(' · ', $a['places']) }}.
                    @if ($a['replacement'])
                        Reemplazo recomendado: <strong>{{ $a['replacement'] }}</strong>.
                    @endif
                    Cámbialo en <a class="underline font-semibold" href="{{ url('/admin/settings') }}">Configuraciones</a>,
                    <a class="underline font-semibold" href="{{ url('/admin/ai-chat') }}">Chat de Salud IA</a> o en el <code>.env</code>.
                </p>
            </div>
        @endforeach
        <p class="text-xs text-slate-500 px-1">
            Fechas del registro revisado el {{ config('ai_models.last_reviewed') }}
            @if ($aiChecked) · última comprobación real con las APIs: {{ \Carbon\Carbon::parse($aiChecked)->timezone('America/Bogota')->format('d/m/Y') }} @endif
        </p>
    </div>
@endif
