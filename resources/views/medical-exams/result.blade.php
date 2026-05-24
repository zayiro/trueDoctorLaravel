<x-guest-layout>
    <div class="max-w-5xl mx-auto py-12 px-4 mt-6">
        <style>
            @media print {
                body { background: white; color: black; padding: 0; }
                .print-card { border: none !important; box-shadow: none !important; }
            }
        </style>
        <div class="max-w-4xl mx-auto space-y-6">
            
            <!-- Acciones superiores (Ocultas al imprimir) -->
            <div class="flex justify-between items-center print:hidden bg-white p-4 border border-slate-200 rounded-2xl shadow-sm">
                <a href="{{ route('exams.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-800 flex items-center gap-1">
                    ← Analizar otro examen
                </a>
                <button onclick="window.print()" class="px-4 py-2.5 bg-slate-900 hover:bg-slate-950 text-xs font-bold rounded-xl shadow-md active:scale-95 transition-all flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    Guardar como PDF / Imprimir
                </button>
            </div>

            <!-- Tarjeta de Reporte Principal (Optimizada para impresión) -->
            <div class="print-card bg-white rounded-3xl border border-slate-200 shadow-xl shadow-slate-100 overflow-hidden">
                <!-- Cabecera del Reporte -->
                <div class="bg-slate-950 text-white p-6 sm:p-8 flex justify-between items-start border-b-4 border-indigo-600">
                    <div class="space-y-1.5">
                        <span class="text-xs font-black uppercase tracking-widest text-indigo-400">Reporte Analítico Automatizado</span>
                        <h1 class="text-2xl font-black tracking-tight leading-none">{{ $analysisResult['nombre_examen'] }}</h1>
                        <p class="text-xs text-slate-400 font-medium pt-1">ID Transacción: {{ $analysis->payment_id }} — Emitido el {{ $analysis->updated_at->format('d/m/Y g:i A') }}</p>
                    </div>
                </div>

                <div class="p-6 sm:p-8 space-y-6">
                    <!-- Información del Paciente y Motivo -->
                    <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-100 text-xs font-medium">
                        <div>
                            <span class="text-slate-400 block uppercase tracking-wide font-bold">Paciente</span>
                            <span class="text-slate-800 text-sm font-bold">{{ auth()->user()->name ?? 'Invitado' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block uppercase tracking-wide font-bold">Motivo Declarado</span>
                            <span class="text-slate-700 capitalize text-sm font-semibold">{{ $analysis->reason_type }}</span>
                        </div>
                    </div>

                    <!-- Tabla de Parámetros Clínicos Extraídos -->
                    <div class="space-y-3">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Valores Clínicos Detectados</h2>
                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-xs">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 text-[10px] font-bold text-slate-400 border-b border-slate-200 uppercase tracking-wider">
                                        <th class="p-3">Biomarcador / Parámetro</th>
                                        <th class="p-3">Resultado</th>
                                        <th class="p-3 text-right">Rango / Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-sm font-medium">
                                    @foreach($analysisResult['hallazgos_clave'] as $hallazgo)
                                        <tr class="hover:bg-slate-50/50">
                                            <td class="p-3 text-slate-900 font-semibold">{{ $hallazgo['parametro'] }}</td>
                                            <td class="p-3 text-slate-600 font-mono text-xs tabular-nums">{{ $hallazgo['valor_detectado'] }}</td>
                                            <td class="p-3 text-right">
                                                @if($hallazgo['estado'] === 'Normal')
                                                    <span class="inline-flex px-2 py-0.5 text-xs font-bold rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">Normal</span>
                                                @elseif($hallazgo['estado'] === 'Elevado' || $hallazgo['estado'] === 'Bajo')
                                                    <span class="inline-flex px-2 py-0.5 text-xs font-bold rounded-md bg-amber-50 text-amber-700 border border-amber-200">{{ $hallazgo['estado'] }}</span>
                                                @else
                                                    <span class="inline-flex px-2 py-0.5 text-xs font-bold rounded-md bg-rose-50 text-rose-700 border border-rose-200">{{ $hallazgo['estado'] }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Bloques Informativos de Conclusión -->
                    <div class="space-y-4">
                        <div class="bg-indigo-50/40 border border-indigo-100 rounded-xl p-5 space-y-1">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-700">Interpretación del Reporte</h3>
                            <p class="text-sm text-slate-700 font-medium leading-relaxed">{{ $analysisResult['conclusion_paciente'] }}</p>
                        </div>

                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 space-y-1">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Próximos Pasos Recomendados</h3>
                            <p class="text-sm text-slate-700 font-medium leading-relaxed">{{ $analysisResult['recomendaciones'] }}</p>
                        </div>

                        <!--  CÓDIGO CORREGIDO Y SEGURO -->
                        @if(!empty($analysisResult) && isset($analysisResult['especialidad_slug']))
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 space-y-1">
                                <div class="mb-4">                                    
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Recomendación Médica</h3>
                                    <h4 class="text-lg font-black tracking-tight mt-2">¿Deseas revisar estos resultados con un profesional?</h4>
                                    <p class="text-sm text-slate-700 font-medium leading-relaxed">
                                        La Inteligencia Artificial sugiere que un especialista en <strong class="text-indigo-300 capitalize">{{ str_replace('-', ' ', $analysisResult['especialidad_slug']) }}</strong> es el más idóneo para dar seguimiento a tus métricas.
                                    </p>
                                </div>
                                <div class="shrink-0 mt-3">
                                    <!-- URL Absoluta con Query String corregida -->
                                    <a href="{{ url('/search') }}?specialty={{ $analysisResult['especialidad_slug'] }}&city=" 
                                        class="inline-flex items-center justify-center gap-1.5 px-5 py-3 bg-indigo-600 text-white hover:bg-indigo-700 text-indigo-900 font-bold text-lg rounded-xl shadow-md active:scale-95 transition-all duration-200"
                                        target="_blank" rel="noopener noreferrer"
                                    >
                                        Ver especialistas recomendados
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        @endisset

                    </div>
                </div>

                <!-- Descargo de Responsabilidad de la IA (Crítico en Salud) -->
                <div class="bg-amber-50/40 px-6 py-4 border-t border-slate-100 text-center">
                    <p class="text-sm text-slate-700 font-medium leading-relaxed max-w-4xl mx-auto">
                        <strong>⚠️ Descargo de responsabilidad:</strong> Este informe es una transcripción e interpretación automatizada generada por Inteligencia Artificial y tiene carácter meramente educativo. No reemplaza un diagnóstico ni una consulta con un profesional médico certificado.
                    </p>
                </div>
            </div>
        </div>
        <div class="text-center text-xs text-slate-400 mt-6 print:hidden">
            © {{ date('Y') }} HealthAI. Todos los derechos reservados. Este informe es confidencial y está destinado únicamente para el paciente registrado.
        </div>
    </div>
</x-guest-layout>           
