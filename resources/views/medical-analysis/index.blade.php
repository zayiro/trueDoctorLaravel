<x-guest-layout 
    :meta-title-medical-analysis="$meta_title_medicalAnalysis" 
    :meta-description-medical-analysis="$meta_description_medicalAnalysis"
>
    @if(session('error'))
        <div class="max-w-4xl mx-auto my-4 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-sm text-red-400 text-center">
            {{ session('error') }}
        </div>
    @endif
    <!-- Hero Section -->
    <header class="max-w-7xl mx-auto px-6 pt-16 pb-20 grid md:grid-cols-2 gap-12 items-center">
        <div class="space-y-6 mt-4">
            <div class="inline-flex items-center gap-2 bg-emerald-50 border border-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider">
                <svg xmlns="http://w3.org" viewBox="0 0 24 24" fill="currentColor" class="size-6 text-emerald-500">
                    <path fill-rule="evenodd" d="M9 4.5a.75.75 0 0 1 .721.544l.84 2.52a1.75 1.75 0 0 0 1.117 1.117l2.52.84a.75.75 0 0 1 0 1.424l-2.52.84a1.75 1.75 0 0 0-1.117 1.117l-.84 2.52a.75.75 0 0 1-1.424 0l-.84-2.52a1.75 1.75 0 0 0-1.117-1.117l-2.52-.84a.75.75 0 0 1 0-1.424l2.52-.84a1.75 1.75 0 0 0 1.117-1.117l.84-2.52A.75.75 0 0 1 9 4.5ZM18.75 12a.75.75 0 0 1 .721.544l.405 1.215a.75.75 0 0 0 .479.479l1.215.405a.75.75 0 0 1 0 1.424l-1.215.405a.75.75 0 0 0-.479.479l-.405 1.215a.75.75 0 0 1-1.424 0l-.405-1.215a.75.75 0 0 0-.479-.479l-1.215-.405a.75.75 0 0 1 0-1.424l1.215-.405a.75.75 0 0 0 .479-.479l.405-1.215A.75.75 0 0 1 18.75 12Zm-12 5.25a.75.75 0 0 1 .721.544l.27 1.215a.375.375 0 0 0 .24.24l1.215.27a.75.75 0 0 1 0 1.424l-1.215.27a.375.375 0 0 0-.24.24l-.27 1.215a.75.75 0 0 1-1.424 0l-.27-1.215a.375.375 0 0 0-.24-.24l-1.215-.27a.75.75 0 0 1 0-1.424l1.215-.27a.375.375 0 0 0 .24-.24l.27-1.215a.75.75 0 0 1 .721-.544Z" clip-rule="evenodd" />
                </svg>
                IA Generativa Médica de Vanguardia
            </div>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-black tracking-tight text-slate-900 leading-tight">
                Interpreta tus exámenes médicos con Inteligencia Artificial en <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">segundos</span>
            </h1>
            <p class="text-lg text-slate-600 leading-relaxed max-w-xl">
                ¿Tienes análisis clínicos, tomografías o informes confusos? Nuestro <span class="font-bold">Asistente Médico Digital</span> avanzado traduce el lenguaje médico complejo a explicaciones claras, precisas y accionables para ti.
            </p>
            <p class="text-lg text-slate-600 leading-relaxed max-w-xl">
                Valor del informe <strong>{{ $price }} COP</strong>
            </p>
            <div class="flex flex-col sm:flex-row gap-4 pt-2">                
                <a href="{{ route('medical-analysis.upload') }}" 
                    x-data="{ loading: false }"
                    @pageshow.window="loading = false"
                    @click="
                        if (loading) return;
                        loading = true;
                        
                        if (typeof gtag === 'function') {
                            gtag('event', 'start_medical_analysis_upload', {
                                'action': 'begin_upload',
                                'source': 'hero_cta',
                                'feature_type': 'lab_analysis'
                            });
                        }
                    "
                    :class="loading ? 'opacity-70 cursor-not-allowed pointer-events-none' : ''"
                    class="bg-slate-900 hover:bg-slate-800 text-white px-8 py-4 rounded-xl font-semibold transition text-center shadow-xl shadow-slate-200 flex items-center justify-center gap-3">
                    
                    <span class="inline-flex items-center gap-x-2" x-show="!loading">
                        Analizar mis exámenes médicos 
                        <svg xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </span>

                    <span x-show="loading" class="flex items-center gap-2" x-cloak>
                        Iniciando el proceso de análisis... 
                        <i class="fa-solid fa-spinner animate-spin"></i>
                    </span>
                </a>
            </div>
            <!-- Trust badges -->
            <div class="pt-6 flex items-center gap-6 text-slate-400 text-sm border-t border-slate-100">
                <span><i class="fa-solid fa-shield-halved text-emerald-500 mr-2"></i> Cumplimiento HIPAA y GDPR</span>
                <span><i class="fa-solid fa-user-lock text-blue-500 mr-2"></i> Anonimización Estricta</span>
            </div>
        </div>
        <!-- Right side graphic placeholder -->
        <div class="relative hidden md:block">
            <div class="absolute inset-0 bg-gradient-to-tr from-blue-100 to-emerald-100 rounded-3xl transform rotate-3 scale-95 opacity-50 blur-lg"></div>
            <div class="relative bg-white border border-slate-100 p-8 rounded-3xl shadow-xl flex gap-6">
                
                <!-- Imagen izquierda -->
                <div class="flex-1">
                    <img src="{{ asset('images/examenes-medicos-con-ia.jpg') }}" 
                        alt="Análisis médico" 
                        class="w-full h-auto rounded-lg">
                </div>
                
                <!-- Pasos derecha -->
                <div class="flex-1 space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 font-bold">1</div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm">Carga tus PDF clínicos</h4>
                            <p class="text-xs text-slate-500">Historiales, laboratorios o recetas.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 border-t border-b border-slate-50 py-4">
                        <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 font-bold">2</div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm">Ocultación automática de datos</h4>
                            <p class="text-xs text-slate-500">Borramos tu nombre y cédula, al instante.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold">3</div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm">Segunda Opinión por IA</h4>
                            <p class="text-xs text-slate-500">Explicación humana y correlación de síntomas.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Why Section (The Sell) -->
    <section id="features" class="bg-white border-t border-b border-slate-100 py-20">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto space-y-4 mb-16">
                <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">¿Por qué usar Inteligencia Artificial para entender tus exámenes clínicos?</h2>
                <p class="text-slate-500">La IA no reemplaza a tu médico, empodera tu conocimiento para tomar mejores decisiones en tu próxima consulta.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Card 1 -->
                <div class="p-8 rounded-2xl bg-slate-50 border border-slate-100 hover:shadow-lg transition space-y-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                            <path fill-rule="evenodd" d="M2.25 13.5a8.25 8.25 0 0 1 8.25-8.25.75.75 0 0 1 .75.75v6.75H18a.75.75 0 0 1 .75.75 8.25 8.25 0 0 1-16.5 0Z" clip-rule="evenodd" />
                            <path fill-rule="evenodd" d="M12.75 3a.75.75 0 0 1 .75-.75 8.25 8.25 0 0 1 8.25 8.25.75.75 0 0 1-.75.75h-7.5a.75.75 0 0 1-.75-.75V3Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Análisis Transversal</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Un ser humano tarda horas en cruzar datos de 5 informes distintos. Nuestra IA analiza múltiples PDFs en segundos buscando patrones ocultos.
                    </p>
                </div>
                <!-- Card 2 -->
                <div class="p-8 rounded-2xl bg-slate-50 border border-slate-100 hover:shadow-lg transition space-y-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                            <path d="M11.625 12c0 .207-.168.375-.375.375h-.75a.375.375 0 0 1-.375-.375v-.75c0-.207.168-.375.375-.375h.75c.207 0 .375.168.375.375v.75ZM14.25 12c0 .207-.168.375-.375.375h-.75a.375.375 0 0 1-.375-.375v-.75c0-.207.168-.375.375-.375h.75c.207 0 .375.168.375.375v.75Z" />
                            <path fill-rule="evenodd" d="M5.25 3A2.25 2.25 0 0 0 3 5.25v13.5A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V5.25A2.25 2.25 0 0 0 18.75 3H5.25ZM12 6.75a.75.75 0 0 1 .75.75v.75h.75a.75.75 0 0 1 0 1.5h-.75v.75a.75.75 0 0 1-1.5 0v-.75h-.75a.75.75 0 0 1 0-1.5h.75V7.5A.75.75 0 0 1 12 6.75ZM6.75 12a.75.75 0 0 1 .75-.75h.75v-.75a.75.75 0 0 1 1.5 0v.75h.75a.75.75 0 0 1 0 1.5h-.75v.75a.75.75 0 0 1-1.5 0v-.75H7.5A.75.75 0 0 1 6.75 12Zm10.5-.75a.75.75 0 0 0-.75.75v.75h-.75a.75.75 0 0 0 0 1.5h.75v.75a.75.75 0 0 0 1.5 0v-.75h.75a.75.75 0 0 0 0-1.5h-.75V12a.75.75 0 0 0-.75-.75Zm-5.25 4.5a.75.75 0 0 1 .75-.75h.75v-.75a.75.75 0 0 1 1.5 0v.75h.75a.75.75 0 0 1 0 1.5h-.75v.75a.75.75 0 0 1-1.5 0v-.75h-.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Lenguaje Claro</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Traducimos tecnicismos incomprensibles a explicaciones sencillas. Entenderás exactamente qué significa cada indicador de tus laboratorios.
                    </p>
                </div>
                <!-- Card 3 -->
                <div class="p-8 rounded-2xl bg-slate-50 border border-slate-100 hover:shadow-lg transition space-y-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl">
                        <svg xmlns="http://w3.org" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                            <path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 0 0-1.032 0 11.209 11.209 0 0 1-7.877 3.08.75.75 0 0 0-.722.515A12.74 12.74 0 0 0 2.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 0 0 .374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 0 0-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08Zm3.094 8.08a.75.75 0 0 0-1.22-.868l-3.5 4.5-1.25-1.25a.75.75 0 1 0-1.06 1.06l1.75 1.75a.75.75 0 0 0 1.14-.094l4-5.5Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Privacidad Absoluta</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Tus archivos se procesan de forma 100% anónima gracias a nuestro motor de sanitizado. Tu identidad nunca se comparte con la Inteligencia Artificial.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Final Call to Action -->
    <section class="max-w-5xl mx-auto px-6 py-20 text-center">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-3xl p-12 shadow-xl shadow-blue-100 space-y-6">
            <h2 class="text-3xl md:text-4xl font-bold tracking-tight">Toma el control de tu salud hoy mismo</h2>
            <p class="text-blue-100 max-w-xl mx-auto text-base">
                No esperes semanas por una cita para entender un papel. Obtén una guía inteligente previa de manera segura, rápida y gratuita.
            </p>
            <div class="pt-4">                
                <a href="{{ route('medical-analysis.upload') }}"
                    x-data="{ loading: false }"
                    @click="
                        loading = true;
                        if (typeof gtag === 'function') {
                            gtag('event', 'start_medical_analysis_upload', {
                                'action': 'begin_upload',
                                'source': 'final_cta',
                                'feature_type': 'lab_analysis'
                            });
                        }
                    "
                    :class="loading ? 'opacity-70 cursor-not-allowed' : ''"
                    class="bg-white hover:bg-slate-50 text-blue-600 px-8 py-4 rounded-xl font-bold transition inline-flex items-center gap-2 shadow-md">
                    <span x-show="!loading">Comenzar Valoración IA <i class="fa-solid fa-chevron-right text-sm"></i></span>
                    <span x-show="loading" class="flex items-center gap-2">Cargando... <i class="fa-solid fa-spinner animate-spin"></i></span>
                </a>
            </div>
        </div>
    </section>
</x-guest-layout>   
