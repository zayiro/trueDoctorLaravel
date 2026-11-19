<x-guest-layout
    :meta-title-medical-analysis="$meta_title_medicalAnalysis"
    :meta-description-medical-analysis="$meta_description_medicalAnalysis"
>
@php
    $report = $analysis->report ?? [];

    $levels = ['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta'];

    $levelStyles = [
        'baja'  => 'background:#d1fae5;color:#065f46;',
        'media' => 'background:#fef3c7;color:#92400e;',
        'alta'  => 'background:#fee2e2;color:#991b1b;',
    ];
    $normalize = fn ($v) => in_array($v, ['baja', 'media', 'alta'], true) ? $v : 'media';
@endphp

<div class="bg-gradient-to-b from-white via-purple-50 to-slate-50">
    <div class="mx-auto max-w-5xl px-4 py-12 md:py-16">

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
                <h1 class="mt-6 text-2xl font-bold text-slate-900">Estamos analizando tu fotografía</h1>
                <p x-show="!slow" class="mt-2 text-sm text-slate-600">Esto toma menos de un minuto. No cierres esta página; también te enviaremos un aviso por correo.</p>
                <p x-show="slow" x-cloak class="mt-2 text-sm text-slate-600">Está tardando más de lo normal. Te avisaremos por correo cuando tu informe esté listo.</p>
            </div>

        {{-- ============ ERROR ============ --}}
        @elseif ($analysis->status === 'error')
            <div class="rounded-3xl border-l-4 border-red-500 bg-red-50 p-8 shadow-sm">
                <h1 class="text-xl font-bold text-red-800">No pudimos completar tu análisis</h1>
                <p class="mt-2 text-sm text-red-700">Ocurrió un problema al procesar tu fotografía. Contáctanos por WhatsApp o correo indicando tu correo de registro y lo resolveremos.</p>
            </div>

        {{-- ============ INFORME ============ --}}
        @else
            <h1 class="text-center text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">Tu informe de piel</h1>

            <div class="mt-8 space-y-5">

                @if (($report['imagen_valida'] ?? true) === false)
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                        <h2 class="text-lg font-bold text-slate-900">No pudimos evaluar la imagen</h2>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $report['descripcion'] ?? '' }}</p>
                    </div>
                @else
                    {{-- Urgencia --}}
                    @php $urgency = $normalize(data_get($report, 'urgencia.nivel')); @endphp
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                        <div class="flex items-center justify-between gap-4">
                            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">Urgencia</h2>
                            <span class="rounded-full px-3 py-1 text-xs font-bold" style="{{ $levelStyles[$urgency] }}">{{ $levels[$urgency] }}</span>
                        </div>
                        <p class="mt-3 text-sm leading-relaxed text-slate-700">{{ data_get($report, 'urgencia.accion_requerida') }}</p>
                    </div>

                    {{-- Qué se observa --}}
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">Qué se observa</h2>
                        <p class="mt-3 text-sm leading-relaxed text-slate-700">{{ $report['descripcion'] ?? '' }}</p>
                    </div>

                    {{-- Posibles condiciones --}}
                    @if (!empty($report['posibles_condiciones']))
                        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">Posibles condiciones compatibles</h2>
                            <div class="mt-4 space-y-4">
                                @foreach ($report['posibles_condiciones'] as $condition)
                                    @php $prob = $normalize($condition['probabilidad'] ?? null); @endphp
                                    <div class="border-t border-slate-100 pt-4 first:border-t-0 first:pt-0">
                                        <div class="flex items-center justify-between gap-3">
                                            <h3 class="font-semibold text-slate-900">{{ $condition['nombre'] ?? '' }}</h3>
                                            <span class="shrink-0 rounded-full px-3 py-1 text-xs font-bold" style="{{ $levelStyles[$prob] }}">
                                                Probabilidad: {{ $levels[$prob] }}
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
                            <h2 class="text-sm font-bold uppercase tracking-wide text-red-800">Señales de alarma</h2>
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
                            <h2 class="text-sm font-bold uppercase tracking-wide text-emerald-800">Aspectos tranquilizadores</h2>
                            <p class="mt-3 text-sm leading-relaxed text-emerald-900">{{ $report['aspectos_tranquilizadores'] }}</p>
                        </div>
                    @endif

                    {{-- Cuidados generales --}}
                    @if (!empty($report['cuidados_generales']))
                        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">Cuidados generales</h2>
                            <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-700">
                                @foreach ($report['cuidados_generales'] as $tip)
                                    <li>{{ $tip }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Recomendación médica: misma tarjeta que en exámenes (Dermatología o, si no hay médicos, Medicina General) --}}
                    @php
                        $suggested = $rec['suggested'] ?? null;
                        $target = $rec['target'] ?? null;
                    @endphp
                    @if ($target)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-5 space-y-1 print:hidden">
                            <div class="mb-4">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Recomendación Médica</h3>
                                <h4 class="mt-2 text-lg font-black tracking-tight text-slate-900">¿Deseas revisar estos resultados con un profesional?</h4>
                                <p class="text-sm font-medium leading-relaxed text-slate-700">
                                    @if ($suggested && $rec['available'])
                                        Tus resultados sugieren una valoración en <strong class="text-indigo-600">{{ $suggested->name }}</strong> para dar seguimiento a lo observado en tu piel.
                                    @elseif ($suggested)
                                        Tus resultados sugieren una valoración en <strong class="text-indigo-600">{{ $suggested->name }}</strong>.
                                        Por ahora puedes comenzar con un médico de <strong class="text-indigo-600">{{ $target->name }}</strong>,
                                        quien podrá evaluarte y orientarte sobre el siguiente paso.
                                    @else
                                        Te recomendamos revisar estos resultados con un médico de <strong class="text-indigo-600">{{ $target->name }}</strong>.
                                    @endif
                                </p>
                            </div>
                            <div class="mt-3 shrink-0">
                                <a href="{{ route('search', ['specialty' => $target->slug]) }}"
                                   class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-5 py-3 text-lg font-bold text-white shadow-md transition-all duration-200 hover:bg-indigo-700 active:scale-95"
                                   target="_blank" rel="noopener noreferrer">
                                    Agendar con {{ $target->name }}
                                    <svg class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @elseif (!empty($report['especialista']))
                        {{-- Sin ningún médico disponible: se conserva el aviso de texto --}}
                        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">Especialista sugerido</h2>
                            <p class="mt-3 text-sm font-semibold text-slate-800">{{ $report['especialista'] }}</p>
                        </div>
                    @endif
                @endif

                <div class="flex justify-end gap-4 pt-2 mb-2 print:hidden">
                    <button onclick="window.print()" class="bg-slate-950 hover:bg-slate-900 border border-white/10 text-slate-300 px-5 py-3 rounded-xl font-semibold text-sm transition flex items-center gap-2 shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.82l2.6-2.6m0 0l2.6 2.6m-2.6-2.6V18m6-9a3.5 3.5 0 11-7 0 3.5 3.5 0 017 0zM18 9v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V9a2.25 2.25 0 012.25-2.25h10.5A2.25 2.25 0 0118 9z"/>
                        </svg>
                        Imprimir o Guardar en PDF
                    </button>
                </div>

                <p class="px-2 text-center text-xs leading-relaxed text-slate-500 print:hidden">
                    <strong>Aviso importante:</strong> Esta valoración es una traducción informativa generada por inteligencia artificial generativa aplicada a la salud. Su propósito es exclusivamente educativo y de empoderamiento al paciente. No representa, sustituye ni constituye bajo ninguna circunstancia un diagnóstico, receta o dictamen médico oficial. Por favor, presente este reporte a su especialista clínico en su próxima consulta.
                </p>

                <div class="text-center print:hidden">
                    <a href="{{ route('skin-analysis.upload') }}"
                       class="inline-flex items-center justify-center rounded-xl bg-slate-100 px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-200">
                        Hacer otro análisis
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>

<style>
    @media print {
        /* Ocultar todo lo que no es el reporte */
        nav,
        header,
        footer,
        .navbar,
        button,
        [x-data],
        .print\:hidden {
            display: none !important;
        }

        /* Fondo blanco para impresión */
        body {
            background-color: #ffffff !important;
            color: #0f172a !important;
        }

        /* Contenedor principal sin padding extra */
        .max-w-5xl {
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Tarjetas con fondo blanco y borde visible */
        .bg-slate-950,
        .bg-slate-900 {
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: none !important;
        }

        /* Texto oscuro para contraste en papel */
        .text-slate-300,
        .text-slate-400,
        .text-slate-500 {
            color: #334155 !important;
        }

        .text-white {
            color: #0f172a !important;
        }

        /* Evitar que las tarjetas se corten entre páginas */
        .rounded-2xl {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        /* Márgenes de página */
        @page {
            margin: 1.5cm 1.5cm;
            size: A4 portrait;
        }

        /* Tabla de biomarcadores */
        table {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        /* Badges de estado */
        .bg-emerald-500\/10 { background-color: #d1fae5 !important; }
        .bg-amber-500\/10   { background-color: #fef3c7 !important; }
        .bg-red-500\/10     { background-color: #fee2e2 !important; }
        .bg-gray-500\/10    { background-color: #f1f5f9 !important; }

        .text-emerald-400 { color: #065f46 !important; }
        .text-amber-400   { color: #92400e !important; }
        .text-red-400     { color: #991b1b !important; }
        .text-gray-400    { color: #475569 !important; }

        /* Ocultar botones de acción */
        .flex.justify-end {
            display: none !important;
        }
    }
</style>
</x-guest-layout>