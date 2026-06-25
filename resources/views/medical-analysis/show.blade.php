<x-guest-layout>
    <!-- Contenedor Principal del Reporte -->
    <div class="max-w-5xl mx-auto space-y-8 animate-fade-in p-6 md:p-12 mt-12">

        @if(in_array($analysis->status, ['pending', 'processing']))

            <!-- ESTADO EN PROCESO: Panel de espera con auto-refresh -->
            <div class="bg-slate-950 rounded-2xl border border-blue-500/20 p-8 shadow-2xl space-y-6 text-center"
                 x-data="{}"
                 x-init="setTimeout(() => window.location.reload(), 6000)">
                <div class="w-16 h-16 rounded-full bg-blue-500/10 text-white flex items-center justify-center mx-auto">
                    <svg class="w-8 h-8 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                </div>

                <div class="space-y-2 max-w-lg mx-auto">
                    <h2 class="text-2xl font-black text-white tracking-tight">Analizando tus documentos</h2>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Nuestro motor de Inteligencia Artificial está leyendo y estructurando los datos de tus exámenes.
                        Esto normalmente toma menos de un minuto. Esta página se actualizará automáticamente.
                    </p>
                </div>

                <div class="flex justify-center pt-2">
                    <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-white bg-blue-500/10 px-3 py-1 rounded-full border border-blue-500/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                        {{ $analysis->status === 'pending' ? 'En cola de procesamiento' : 'Procesando con IA' }}
                    </div>
                </div>

                <p class="text-[11px] text-slate-600">Identificador de auditoría del sistema: #{{ $analysis->id }}</p>
            </div>

            <!-- Fallback sin JS/Alpine: meta-refresh cada 6s -->
            <noscript>
                <meta http-equiv="refresh" content="6">
            </noscript>

        @elseif($analysis->status === 'failed' || $analysis->status === 'error' || empty($analysis->ai_response))
            
            <!-- ESTADO FALLIDO: Panel Corporativo de Reintento -->
            <div class="bg-slate-950 rounded-2xl border border-red-500/20 p-8 shadow-2xl space-y-6 text-center">
                <div class="w-16 h-16 rounded-full bg-red-500/10 text-red-400 flex items-center justify-center mx-auto">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.008v.008H12v-.008Z" />
                    </svg>
                </div>

                <div class="space-y-2 max-w-lg mx-auto">
                    <h2 class="text-2xl font-black text-white tracking-tight">Procesamiento Interrumpido Temporalmente</h2>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Hemos resguardado tus motivos de consulta y el texto de tus exámenes de forma segura. El motor de Inteligencia Artificial externa reportó una saturación temporal al estructurar los datos del reporte clínico.
                    </p>
                </div>

                <div class="flex justify-center pt-2 max-w-md mx-auto">
                    <a href="{{ route('medical-analysis.upload') }}" class="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 border border-white/10 text-slate-300 font-bold py-3 px-6 rounded-xl text-sm transition flex items-center justify-center gap-2 shadow-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                        </svg>
                        Regresar e intentar de nuevo
                    </a>
                </div>

                <p class="text-[11px] text-slate-600">Identificador de auditoría del sistema: #{{ $analysis->id }}</p>
            </div>

        @else
            @if ($analysis->payment_status !== 'completed')
                <div class="bg-slate-950 rounded-2xl border border-white/10 my-6 p-6 md:p-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 shadow-xl">
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20">
                            <svg class="w-3.5 h-3.5 animate-pulse" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                            Resultados Clínicos Listos
                        </div>
                        <h2 class="text-2xl md:text-3xl font-black text-white tracking-tight mb-3">
                            {{ $analysis->ai_response['nombre_examen'] ?? 'Reporte Clínico Consolidado' }}
                        </h2>
                        <p class="text-base uppercase text-slate-400 mt-3">Interpretación preliminar</p>
                        <p class="text-white" style="margin: 0; font-size: 14px; line-height: 20px; font-style: italic;">
                            "{{ \Illuminate\Support\Str::limit($analysis->ai_response['conclusion_paciente'] ?? '', 180, '...') }}"
                        </p>
                        
                        <div x-data="wompiCheckout({{ $analysis->id }}, '{{ $analysis->access_token }}')" class="shrink-0 mt-5 pt-3">
                            <form x-ref="wompiForm" action="https://checkout.wompi.co/p/" method="GET" class="hidden">
                                <input type="hidden" name="public-key" :value="paymentData.public_key" />
                                <input type="hidden" name="currency" :value="paymentData.currency" />
                                <input type="hidden" name="amount-in-cents" :value="paymentData.amount_in_cents" />
                                <input type="hidden" name="reference" :value="paymentData.reference" />
                                <input type="hidden" name="signature:integrity" :value="paymentData.signature_integrity" />
                                <input type="hidden" name="redirect-url" :value="paymentData.redirect_url" />
                            </form>
                            <button @click="startPayment" 
                                    :disabled="isLoading"
                                    class="inline-flex items-center justify-center gap-1.5 px-5 py-3 bg-indigo-600 text-white hover:bg-indigo-700 text-indigo-900 font-bold text-lg rounded-xl shadow-md active:scale-95 transition-all duration-200">
                                
                                <!-- Icono de carga (Spinner) visible únicamente cuando isLoading es true -->
                                <svg x-show="isLoading" 
                                    class="animate-spin h-5 w-5 text-white" 
                                    fill="none" 
                                    viewBox="0 0 24 24" 
                                    x-cloak>
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>

                                <!-- Texto dinámico controlado por el estado de Alpine.js -->
                                <span x-text="isLoading ? 'Generando orden segura...' : 'Pagar con Wompi {{ Number::currency($price, in: 'COP', locale: 'es_CO') }}'"></span>
                            </button>
                        </div>                    
                    </div>
                </div>
            @else
                <div>
                    <!-- ESTADO EXITOSO: Cabecera del Reporte -->
                    <div class="bg-slate-950 rounded-2xl border border-white/10 p-6 md:p-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 shadow-xl mb-2">
                        <div class="space-y-2">
                            <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20">
                                <svg class="w-3.5 h-3.5 animate-pulse" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                                Valoración IA Completada con Éxito
                            </div>
                            <h2 class="text-2xl md:text-3xl font-black text-white tracking-tight">
                                {{ $analysis->ai_response['nombre_examen'] ?? 'Reporte Clínico Consolidado' }}
                            </h2>
                            <p class="text-xs text-slate-400">Procesado de forma segura y 100% anónima</p>
                        </div>

                        <!-- Badge Especialidad -->
                        <div class="bg-slate-900 border border-white/5 p-4 rounded-xl flex items-center gap-3 w-full md:w-auto">
                            <div class="w-10 h-10 rounded-lg bg-white text-blue-400 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.25 2.25 0 0 1 10.5 2.25h4.5a2.25 2.25 0 0 1 2.25 2.25M4.5 19.5a2.25 2.25 0 0 1-2.25-2.25V6.108c0-1.135.845-2.098 1.976-2.192a48.424 48.424 0 0 1 1.123-.08M12 18.75m-1.875 0a1.875 1.875 0 1 1 3.75 0 1.875 1.875 0 0 1-3.75 0Z"/>
                                </svg>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-500 uppercase tracking-wider font-bold">Canalizar con:</span>
                                <p class="text-sm font-bold text-slate-200 capitalize">
                                    <a href="{{ url('/search') }}?specialty={{ $analysis->ai_response['especialidad_slug'] }}&city=" 
                                        class="inline-flex items-center justify-center gap-1.5 px-5 py-3 bg-indigo-600 text-white hover:bg-indigo-700 text-indigo-900 font-bold text-lg rounded-xl shadow-md active:scale-95 transition-all duration-200"
                                        target="_blank" rel="noopener noreferrer"
                                    >
                                        {{ str_replace('-', ' ', $analysis->ai_response['especialidad_slug'] ?? 'Medicina General') }}
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Conclusión Simplificada para el Paciente -->
                    <div class="bg-slate-950 rounded-2xl border border-white/10 p-6 md:p-8 space-y-4 shadow-xl relative overflow-hidden mb-2">
                        <div class="absolute top-0 right-0 w-48 h-48 bg-gradient-to-bl from-blue-500/5 to-transparent pointer-events-none rounded-bl-full"></div>

                        <div class="flex items-center gap-2 text-white font-bold text-base">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/>
                            </svg>
                            ¿Qué significan estos resultados?
                        </div>
                        <div class="prose max-w-none relative z-10
                                    prose-headings:text-white prose-headings:font-bold
                                    prose-h2:text-lg prose-h3:text-base
                                    prose-p:text-slate-300 prose-p:text-sm prose-p:leading-relaxed
                                    prose-strong:text-white prose-strong:font-semibold
                                    prose-li:text-slate-300 prose-li:text-sm
                                    prose-ul:my-2 prose-ol:my-2
                                    prose-a:text-indigo-400 hover:prose-a:text-indigo-300">
                            @markdown($analysis->ai_response['conclusion_paciente'] ?? 'No se pudo generar la conclusión.')
                        </div>
                    </div>

                    <!-- Tabla de Indicadores Clave -->
                    <div class="bg-slate-950 rounded-2xl border border-white/10 shadow-xl overflow-hidden mb-2">
                        <div class="p-6 border-b border-white/5">
                            <h3 class="text-sm font-bold text-white tracking-wide uppercase flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>
                                </svg>
                                Biomarcadores y Valores Detectados
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-900 border-b border-white/5 text-slate-400 text-xs font-semibold uppercase tracking-wider">
                                        <th class="p-4 pl-6">Parámetro / Elemento</th>
                                        <th class="p-4">Valor en Informe</th>
                                        <th class="p-4 pr-6 text-right">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5 text-sm">
                                    @forelse(($analysis->ai_response['hallazgos_clave'] ?? []) as $item)
                                        <tr class="hover:bg-white/(0.02) transition-colors">
                                            <td class="p-4 pl-6 font-semibold text-slate-200">{{ $item['parametro'] ?? '—' }}</td>
                                            <td class="p-4 font-mono text-slate-300 text-xs">{{ $item['valor_detectado'] ?? '—' }}</td>
                                            <td class="p-4 pr-6 text-right">
                                                @php
                                                    $estado = strtolower($item['estado'] ?? 'normal');
                                                    $badgeClasses = match($estado) {
                                                        'normal'   => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                                        'elevado'  => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                                        'bajo'     => 'bg-gray-500/10 text-gray-400 border-gray-500/20',
                                                        'crítico', 'critico' => 'bg-red-500/10 text-red-400 border-red-500/20 animate-pulse',
                                                        default    => 'bg-slate-500/10 text-slate-400 border-slate-500/20'
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-lg border {{ $badgeClasses }}">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                                    {{ $item['estado'] ?? 'Normal' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="p-6 text-center text-slate-500 text-sm">
                                                No se detectarón biomarcadores específicos en este informe.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Recomendaciones e Impresión -->
                    <div class="bg-slate-950 rounded-2xl border border-white/10 p-6 md:p-8 space-y-4 shadow-xl mb-2">
                        <div class="flex items-center gap-2 text-indigo-400 font-bold text-base">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.83-5.83m0 0a2.652 2.652 0 1 1-3.75-3.75M14.25 8.25V3a.75.75 0 0 0-1.41-.365L9.17 6.94M14.25 8.25H9.75M6.108 15.75c-1.135-.094-1.976-1.057-1.976-2.192V6.108c0-1.135.846-2.098 1.976-2.192a48.424 48.424 0 0 1 11.123 0"/>
                            </svg>
                            Recomendaciones y sugerencias informativas
                        </div>
                        <div class="prose max-w-none
                                    prose-headings:text-white prose-headings:font-bold
                                    prose-h2:text-lg prose-h3:text-base
                                    prose-p:text-slate-300 prose-p:text-sm prose-p:leading-relaxed
                                    prose-strong:text-white prose-strong:font-semibold
                                    prose-li:text-slate-300 prose-li:text-sm
                                    prose-ul:my-2 prose-ol:my-2
                                    prose-a:text-indigo-400 hover:prose-a:text-indigo-300">
                            @markdown($analysis->ai_response['recomendaciones'] ?? 'No se generaron recomendaciones.')
                        </div>           
                    </div>

                    <!--  CÓDIGO CORREGIDO Y SEGURO -->
                    @if(!empty($analysis) && isset($analysis->ai_response['especialidad_slug']))
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 space-y-1 mb-2">
                            <div class="mb-4">                                    
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Recomendación Médica</h3>
                                <h4 class="text-lg font-black tracking-tight mt-2">¿Deseas revisar estos resultados con un profesional?</h4>
                                <p class="text-sm text-slate-700 font-medium leading-relaxed">
                                    La Inteligencia Artificial sugiere que un especialista en <strong class="text-indigo-600 capitalize">{{ str_replace('-', ' ', $analysis->ai_response['especialidad_slug']) }}</strong> es el más idóneo para dar seguimiento a tus métricas.
                                </p>
                            </div>
                            <div class="shrink-0 mt-3">
                                <!-- URL Absoluta con Query String corregida -->
                                <a href="{{ url('/search') }}?specialty={{ $analysis->ai_response['especialidad_slug'] }}&city=" 
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

                    <div class="flex justify-end gap-4 pt-2 mb-2">
                        <button onclick="window.print()" class="bg-slate-950 hover:bg-slate-900 border border-white/10 text-slate-300 px-5 py-3 rounded-xl font-semibold text-sm transition flex items-center gap-2 shadow-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.82l2.6-2.6m0 0l2.6 2.6m-2.6-2.6V18m6-9a3.5 3.5 0 11-7 0 3.5 3.5 0 017 0zM18 9v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V9a2.25 2.25 0 012.25-2.25h10.5A2.25 2.25 0 0118 9z"/>
                            </svg>
                            Imprimir o Guardar en PDF
                        </button>
                    </div>

                    <p class="text-[11px] text-slate-500 leading-normal text-center italic max-w-2xl mx-auto pt-4 mb-2">
                        Aviso importante: Esta valoración es una traducción informativa generada por inteligencia artificial generativa aplicada a la salud. Su propósito es exclusivamente educativo y de empoderamiento al paciente. No representa, sustituye ni constituye bajo ninguna circunstancia un diagnóstico, receta o dictamen médico oficial. Por favor, presente este reporte a su especialista clínico en su próxima consulta.
                    </p>                            
                </div>
            @endif                            
        @endif
    </div> <!-- Cierre del contenedor principal -->

    <script>
        document.addEventListener('alpine:init', () => {
        // Recibe el ID de la orden y el token guardado en la vista de Blade
        Alpine.data('wompiCheckout', (orderId, viewToken) => ({
            isLoading: false,
            paymentData: {}, // Propiedad nativa de Alpine donde guardaremos la respuesta

            async startPayment() {
                this.isLoading = true;

                try {
                    // 1. Petición AJAX forzando formato JSON
                    const response = await fetch('{{ route("medical-analysis.payment.prepare") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ order_id: orderId })
                    });

                    // 2. Si el backend responde con un error (4xx o 5xx)
                    if (!response.ok) {
                        const errorDetails = await response.json().catch(() => null);
                        console.error('Backend Error:', errorDetails);
                        throw new Error('Server status error: ' + response.status);
                    }

                    // 3. Asignación directa al estado de Alpine (Aquí eliminamos por completo responseData)
                    this.paymentData = await response.json();

                    // 4. VALIDACIÓN: Comparamos el token recibido del JSON con el de la vista
                    if (this.paymentData.token !== viewToken) {
                        alert('Error de seguridad: Los tokens de verificación no coinciden.');
                        this.isLoading = false;
                        return; // Cancela el flujo y no envía el formulario a Wompi
                    }

                    // 5. Si la validación es correcta, disparamos el envío del formulario
                    this.$nextTick(() => {
                        this.$refs.wompiForm.submit();
                    });

                } catch (error) {
                    console.error('Payment Flow Error:', error);
                    alert('Ocurrió un error al procesar tu solicitud. Por favor, revisa la consola.');
                    this.isLoading = false;
                }
            }
        }));
    });

    </script>
</x-guest-layout>