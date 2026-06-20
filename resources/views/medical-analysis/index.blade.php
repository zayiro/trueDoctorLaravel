<x-guest-layout>
    @if(session('error'))
        <div class="max-w-4xl mx-auto my-4 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-sm text-red-400 text-center">
            {{ session('error') }}
        </div>
    @endif
    <!-- Hero Section -->
    <header class="max-w-7xl mx-auto px-6 pt-16 pb-20 grid md:grid-cols-2 gap-12 items-center">
        <div class="space-y-6 mt-4">
            <div class="inline-flex items-center gap-2 bg-emerald-50 border border-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider">
                <i class="fa-solid fa-sparkles text-emerald-500"></i> IA Generativa Médica de Vanguardia
            </div>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-black tracking-tight text-slate-900 leading-tight">
                Entiende tus informes médicos en <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">segundos</span>
            </h1>
            <p class="text-lg text-slate-600 leading-relaxed max-w-xl">
                ¿Tienes análisis clínicos, tomografías o informes confusos? Nuestro <span class="font-bold">Asistente Médico Digital</span> avanzado traduce el lenguaje médico complejo a explicaciones claras, precisas y accionables para ti.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 pt-2">
                <a href="{{ route('medical-analysis.upload') }}" class="bg-slate-900 hover:bg-slate-800 text-white px-8 py-4 rounded-xl font-semibold transition text-center shadow-xl shadow-slate-200 flex items-center justify-center gap-3">
                    Analizar mis documentos <i class="fa-solid fa-arrow-right text-sm"></i>
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
            <div class="relative bg-white border border-slate-100 p-8 rounded-3xl shadow-xl space-y-6">
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
                        <p class="text-xs text-slate-500">Borramos tu nombre y cédula,  al instante.</p>
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
    </header>

    <!-- Why Section (The Sell) -->
    <section id="features" class="bg-white border-t border-b border-slate-100 py-20">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto space-y-4 mb-16">
                <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">¿Por qué usar Inteligencia Artificial Sanitaria?</h2>
                <p class="text-slate-500">La IA no reemplaza a tu médico, empodera tu conocimiento para tomar mejores decisiones en tu próxima consulta.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Card 1 -->
                <div class="p-8 rounded-2xl bg-slate-50 border border-slate-100 hover:shadow-lg transition space-y-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-magnifying-glass-chart"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Análisis Transversal</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Un ser humano tarda horas en cruzar datos de 5 informes distintos. Nuestra IA analiza múltiples PDFs en segundos buscando patrones ocultos.
                    </p>
                </div>
                <!-- Card 2 -->
                <div class="p-8 rounded-2xl bg-slate-50 border border-slate-100 hover:shadow-lg transition space-y-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-brain"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Lenguaje Claro</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Traducimos tecnicismos incomprensibles a explicaciones sencillas. Entenderás exactamente qué significa cada indicador de tus laboratorios.
                    </p>
                </div>
                <!-- Card 3 -->
                <div class="p-8 rounded-2xl bg-slate-50 border border-slate-100 hover:shadow-lg transition space-y-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-user-shield"></i>
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
                <a href="{{ route('medical-analysis.upload') }}" class="bg-white hover:bg-slate-50 text-blue-600 px-8 py-4 rounded-xl font-bold transition inline-flex items-center gap-2 shadow-md">
                    Comenzar Valoración Gratuita <i class="fa-solid fa-chevron-right text-sm"></i>
                </a>
            </div>
        </div>
    </section>
</x-guest-layout>   
