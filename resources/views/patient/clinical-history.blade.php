@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Historia Clínica',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="py-10 px-4 sm:px-6 lg:px-8">
        
        <!-- Encabezado de la sección -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                    Mi Historia Clínica Oficial
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Registro cronológico de tus atenciones médicas firmadas en opendoctor.online.
                </p>
            </div>

            <!-- Botón de descarga PDF general -->
            @if($histories->count() > 0 && Route::has('patient.pdf.clinical-history'))
                <a href="{{ route('patient.pdf.clinical-history ', $patient) }}" 
                   class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded-xl transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Descargar Historia PDF
                </a>
            @endif
        </div>

        <!-- Banner informativo de carácter legal (Solo lectura) -->
        <div class="bg-blue-50/80 border border-blue-200 rounded-xl p-4 mb-8 flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            <div class="text-md text-blue-900">
                <p class="font-semibold">Documento de consulta (Solo lectura)</p>
                <p class="text-blue-700 mt-0.5">
                    Por normativa de salud en Colombia, las evoluciones clínicas son redactadas y firmadas exclusivamente por el profesional tratante y no pueden ser editadas. Si deseas actualizar tus alergias o medicamentos actuales, hazlo desde tu Perfil de Salud.
                </p>
            </div>
        </div>

        <!-- Listado de Evoluciones Clínicas -->
        @if($histories->isEmpty())
            <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center shadow-sm">
                <div class="w-12 h-12 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-slate-900">Aún no tienes registros clínicos firmados</h3>
                <p class="text-base text-slate-500 max-w-md mx-auto mt-1">
                    Cuando finalices una consulta médica presencial o por videollamada, el especialista cerrará tu evolución y aparecerá aquí automáticamente.
                </p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($histories as $index => $history)
                    @php
                        // Mapeo de etiquetas visuales para el tipo de atención
                        $entryLabels = [
                            'consultation' => ['label' => 'Consulta Médica', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
                            'follow_up'    => ['label' => 'Control / Seguimiento', 'class' => 'bg-teal-50 text-teal-700 border-teal-200'],
                            'emergency'    => ['label' => 'Atención Prioritaria', 'class' => 'bg-rose-50 text-rose-700 border-rose-200'],
                            'note'         => ['label' => 'Nota Clínica', 'class' => 'bg-slate-100 text-slate-700 border-slate-200'],
                        ];
                        $currentType = $entryLabels[$history->entry_type] ?? $entryLabels['note'];
                    @endphp

                    <!-- Tarjeta colapsable con Alpine.js (La primera aparece abierta por defecto) -->
                    <div x-data="{ open: {{ $index === 0 ? 'true' : 'false' }} }" 
                         class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden transition hover:border-slate-300">
                        
                        <!-- Cabecera de la tarjeta -->
                        <div @click="open = !open" 
                             class="p-5 sm:p-6 cursor-pointer flex flex-col sm:flex-row sm:items-center justify-between gap-4 select-none">
                            
                            <div class="flex items-start gap-4">
                                <!-- Icono de calendario / fecha -->
                                <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-600 flex flex-col items-center justify-center shrink-0 border border-slate-200">
                                    <span class="text-[10px] font-bold uppercase leading-none">{{ $history->created_at->translatedFormat('M') }}</span>
                                    <span class="text-base font-extrabold text-slate-900 leading-tight">{{ $history->created_at->format('d') }}</span>
                                </div>

                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full border {{ $currentType['class'] }}">
                                            {{ $currentType['label'] }}
                                        </span>

                                        @if($history->status === 'amended')
                                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                                                Con Nota Aclaratoria
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                                </svg>
                                                Firmada
                                            </span>
                                        @endif

                                        <span class="text-xs text-slate-400">
                                            Folio #{{ str_pad($history->id, 6, '0', STR_PAD_LEFT) }}
                                        </span>
                                    </div>

                                    <h3 class="text-base font-bold text-slate-900 mt-1.5">
                                        Dr(a). {{ $history->doctor->user->name ?? 'Profesional Médico' }}
                                    </h3>
                                    <p class="text-xs text-slate-500">
                                        {{ $history->doctor->specialty->name ?? 'Medicina General' }} • {{ $history->created_at->format('d/m/Y h:i A') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Diagnóstico rápido y botón de expandir -->
                            <div class="flex items-center justify-between sm:justify-end gap-4 border-t sm:border-t-0 pt-3 sm:pt-0 border-slate-100">
                                @if($history->cie10_code)
                                    <div class="text-left sm:text-right">
                                        <span class="block text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Código CIE-10</span>
                                        <span class="text-sm font-bold text-slate-800">{{ $history->cie10_code }}</span>
                                    </div>
                                @endif

                                <button type="button" class="p-2 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition">
                                    <svg class="w-5 h-5 transform transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <!-- Cuerpo desplegable con la estructura clínica SOAP -->
                        <div x-show="open" x-transition class="border-t border-slate-100 bg-slate-50/60 p-5 sm:p-6 space-y-5">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- S: Subjetivo -->
                                <div class="bg-white p-4 rounded-xl border border-slate-200/80">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="w-6 h-6 rounded-md bg-blue-100 text-blue-700 text-xs font-extrabold flex items-center justify-center">S</span>
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Motivo y Síntomas (Subjetivo)</h4>
                                    </div>
                                    <p class="text-sm text-slate-700 whitespace-pre-line leading-relaxed">
                                        {{ $history->soap_subjective ?: 'Sin registros en esta sección.' }}
                                    </p>
                                </div>

                                <!-- O: Objetivo -->
                                <div class="bg-white p-4 rounded-xl border border-slate-200/80">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="w-6 h-6 rounded-md bg-indigo-100 text-indigo-700 text-xs font-extrabold flex items-center justify-center">O</span>
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Examen Físico y Hallazgos (Objetivo)</h4>
                                    </div>
                                    <p class="text-sm text-slate-700 whitespace-pre-line leading-relaxed">
                                        {{ $history->soap_objective ?: 'Sin registros en esta sección.' }}
                                    </p>
                                </div>

                                <!-- A: Análisis -->
                                <div class="bg-white p-4 rounded-xl border border-slate-200/80">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="w-6 h-6 rounded-md bg-amber-100 text-amber-700 text-xs font-extrabold flex items-center justify-center">A</span>
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Análisis y Diagnóstico</h4>
                                    </div>
                                    <p class="text-sm text-slate-700 whitespace-pre-line leading-relaxed">
                                        {{ $history->soap_assessment ?: 'Sin registros en esta sección.' }}
                                    </p>
                                </div>

                                <!-- P: Plan -->
                                <div class="bg-white p-4 rounded-xl border border-slate-200/80">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="w-6 h-6 rounded-md bg-emerald-100 text-emerald-700 text-xs font-extrabold flex items-center justify-center">P</span>
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Plan de Tratamiento y Recomendaciones</h4>
                                    </div>
                                    <p class="text-sm text-slate-700 whitespace-pre-line leading-relaxed">
                                        {{ $history->soap_plan ?: 'Sin registros en esta sección.' }}
                                    </p>
                                </div>
                            </div>

                                                        <!-- Notas Extemporáneas / Aclaratorias del Médico (si existen) -->
                            @if($history->notes->isNotEmpty())
                                <div class="pt-4 border-t border-slate-200/80 space-y-3">
                                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-amber-800 flex items-center gap-1.5">
                                        <span>📌</span> Notas Aclaratorias / Extemporáneas del Especialista ({{ $history->notes->count() }})
                                    </h4>

                                    <div class="space-y-2.5">
                                        @foreach($history->notes as $idx => $note)
                                            <div class="p-4 rounded-xl bg-amber-50/80 border border-amber-200/90">
                                                <div class="flex flex-wrap items-center justify-between gap-2 text-xs font-bold text-amber-900 mb-1.5">
                                                    <span>
                                                        Nota #{{ $idx + 1 }} • Dr(a). {{ $note->doctor->user->name ?? 'Profesional Médico' }}
                                                    </span>
                                                    <span class="text-amber-700 font-semibold">
                                                        {{ $note->created_at->format('d/m/Y h:i A') }}
                                                    </span>
                                                </div>
                                                <p class="text-sm text-slate-800 whitespace-pre-line leading-relaxed">
                                                    {{ $note->content }}
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Pie de tarjeta con trazabilidad legal -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-3 border-t border-slate-200/70 text-xs text-slate-500">
                                <div class="flex items-center gap-2">
                                    <span>Registro médico cerrado e inmutable</span>
                                    @if($history->ai_assisted)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-slate-200/70 text-slate-600 font-medium" title="Documentación estructurada con apoyo de herramienta clínica">
                                            Asistido por IA clínica
                                        </span>
                                    @endif
                                </div>
                                <div>
                                    Última actualización oficial: {{ $history->updated_at->format('d/m/Y H:i') }}
                                </div>
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Paginación -->
            <div class="mt-6">
                {{ $histories->links() }}
            </div>
        @endif

    </div>
</x-admin-layout>
