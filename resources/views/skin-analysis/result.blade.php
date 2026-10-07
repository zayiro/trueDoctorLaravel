<x-guest-layout
    :meta-title-medical-analysis="$meta_title_medicalAnalysis" 
    :meta-description-medical-analysis="$meta_description_medicalAnalysis"
>
    @php
        $lang = $analysis->analysis_language;
        $t = fn (string $key) => \App\Support\SkinAnalysisText::get($lang, $key);
        $report = $analysis->report ?? [];

        $levelStyles = [
            'baja'  => 'background:#d1fae5;color:#065f46;',
            'media' => 'background:#fef3c7;color:#92400e;',
            'alta'  => 'background:#fee2e2;color:#991b1b;',
        ];
        $normalize = fn ($v) => in_array($v, ['baja', 'media', 'alta'], true) ? $v : 'media';
    @endphp

    <div class="bg-gradient-to-b from-white via-purple-50 to-slate-50">
        <div class="mx-auto max-w-3xl px-4 py-12 md:py-16">

            {{-- ============ PROCESANDO ============ --}}
            @if ($is_processing)
                <script>
                    function skinStatusPoll(url) {
                        return {
                            tries: 0,
                            slow: false,
                            start() {
                                const timer = setInterval(async () => {
                                    this.tries++;
                                    try {
                                        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                                        const data = await response.json();
                                        if (data.status !== 'processing') {
                                            clearInterval(timer);
                                            window.location.reload();
                                            return;
                                        }
                                    } catch (e) {}
                                    if (this.tries >= 60) {
                                        clearInterval(timer);
                                        this.slow = true;
                                    }
                                }, 4000);
                            }
                        }
                    }
                </script>

                <div x-data="skinStatusPoll('{{ route('skin-analysis.status', ['token' => $analysis->access_token]) }}')" x-init="start()"
                    class="rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-md md:p-12">
                    <svg x-show="!slow" class="mx-auto h-12 w-12 animate-spin text-purple-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <h1 class="mt-6 text-2xl font-bold text-slate-900">{{ $t('processing_title') }}</h1>
                    <p x-show="!slow" class="mt-2 text-sm text-slate-600">{{ $t('processing_text') }}</p>
                    <p x-show="slow" x-cloak class="mt-2 text-sm text-slate-600">{{ $t('processing_slow') }}</p>
                </div>

            {{-- ============ ERROR ============ --}}
            @elseif ($analysis->status === 'error')
                <div class="rounded-3xl border-l-4 border-red-500 bg-red-50 p-8 shadow-sm">
                    <h1 class="text-xl font-bold text-red-800">{{ $t('error_title') }}</h1>
                    <p class="mt-2 text-sm text-red-700">{{ $t('error_text') }}</p>
                </div>

            {{-- ============ INFORME ============ --}}
            @else
                <h1 class="text-center text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">{{ $t('result_title') }}</h1>

                <div class="mt-8 space-y-5">

                    @if (($report['imagen_valida'] ?? true) === false)
                        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                            <h2 class="text-lg font-bold text-slate-900">{{ $t('invalid_title') }}</h2>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $report['descripcion'] ?? '' }}</p>
                        </div>
                    @else
                        {{-- Urgencia --}}
                        @php $urgency = $normalize(data_get($report, 'urgencia.nivel')); @endphp
                        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                            <div class="flex items-center justify-between gap-4">
                                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ $t('urgency') }}</h2>
                                <span class="rounded-full px-3 py-1 text-xs font-bold" style="{{ $levelStyles[$urgency] }}">{{ $t('level_' . $urgency) }}</span>
                            </div>
                            <p class="mt-3 text-sm leading-relaxed text-slate-700">{{ data_get($report, 'urgencia.accion_requerida') }}</p>
                        </div>

                        {{-- Qué se observa --}}
                        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ $t('observed') }}</h2>
                            <p class="mt-3 text-sm leading-relaxed text-slate-700">{{ $report['descripcion'] ?? '' }}</p>
                        </div>

                        {{-- Posibles condiciones --}}
                        @if (!empty($report['posibles_condiciones']))
                            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ $t('conditions') }}</h2>
                                <div class="mt-4 space-y-4">
                                    @foreach ($report['posibles_condiciones'] as $condition)
                                        @php $prob = $normalize($condition['probabilidad'] ?? null); @endphp
                                        <div class="border-t border-slate-100 pt-4 first:border-t-0 first:pt-0">
                                            <div class="flex items-center justify-between gap-3">
                                                <h3 class="font-semibold text-slate-900">{{ $condition['nombre'] ?? '' }}</h3>
                                                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-bold" style="{{ $levelStyles[$prob] }}">
                                                    {{ $t('probability') }}: {{ $t('level_' . $prob) }}
                                                </span>
                                            </div>
                                            <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $condition['motivo'] ?? '' }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Señales de alarma --}}
                        @if (!empty($report['senales_de_alarma']))
                            <div class="rounded-3xl border-l-4 border-red-500 bg-red-50 p-6 md:p-8">
                                <h2 class="text-sm font-bold uppercase tracking-wide text-red-800">{{ $t('alarm') }}</h2>
                                <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-red-700">
                                    @foreach ($report['senales_de_alarma'] as $sign)
                                        <li>{{ $sign }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Aspectos tranquilizadores --}}
                        @if (!empty($report['aspectos_tranquilizadores']))
                            <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6 md:p-8">
                                <h2 class="text-sm font-bold uppercase tracking-wide text-emerald-800">{{ $t('reassuring') }}</h2>
                                <p class="mt-3 text-sm leading-relaxed text-emerald-900">{{ $report['aspectos_tranquilizadores'] }}</p>
                            </div>
                        @endif

                        {{-- Cuidados generales --}}
                        @if (!empty($report['cuidados_generales']))
                            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ $t('care') }}</h2>
                                <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-700">
                                    @foreach ($report['cuidados_generales'] as $tip)
                                        <li>{{ $tip }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Especialista --}}
                        @if (!empty($report['especialista']))
                            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ $t('specialist') }}</h2>
                                <p class="mt-3 text-sm font-semibold text-slate-800">{{ $report['especialista'] }}</p>
                            </div>
                        @endif
                    @endif

                    <p class="px-2 text-center text-xs leading-relaxed text-slate-500">{{ $report['disclaimer'] ?? '' }}</p>

                    <div class="text-center">
                        <a href="{{ route('skin-analysis.upload') }}"
                        class="inline-flex items-center justify-center rounded-xl bg-slate-100 px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-200">
                            {{ $t('new_analysis') }}
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-guest-layout>