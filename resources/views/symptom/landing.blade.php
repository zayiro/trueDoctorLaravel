<x-guest-layout>
    <!-- 📋 Inyección de SEO Avanzado en HEAD -->
    <x-slot:seo>
        <title>{{ $metaTitle }} | Asesoría Médica OpenDoctor</title>
        <meta name="title" content="{{ $metaTitle }}">
        <meta name="description" content="{{ $metaDesc ?? substr(strip_tags($clinicalDescription), 0, 160) }}">
        <meta name="robots" content="{{ strtolower($urgency) === 'alta' ? 'index, follow' : 'index, follow' }}">
        <meta name="keywords" content="{{ $symptom->search_query }}, {{ implode(', ', $relatedSymptoms ?? []) }}, consulta médica">
        
        <link rel="canonical" href="{{ request()->url() }}">
        
        <!-- Open Graph Tags -->
        <meta property="og:type" content="article">
        <meta property="og:title" content="{{ $metaTitle }}">
        <meta property="og:description" content="{{ $metaDesc ?? substr(strip_tags($clinicalDescription), 0, 160) }}">
        <meta property="og:url" content="{{ request()->url() }}">
        <meta property="og:locale" content="es_CO">
        @isset($featuredImage)
            <meta property="og:image" content="{{ $featuredImage }}">
        @endisset

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $metaTitle }}">
        <meta name="twitter:description" content="{{ $metaDesc ?? substr(strip_tags($clinicalDescription), 0, 160) }}">
        
        @php
            // ===================================
            // SCHEMA.ORG: MedicalWebPage + Article
            // ===================================
            $articleSchema = [
                "@context" => "https://schema.org",
                "@type" => "MedicalWebPage",
                "name" => $metaTitle,
                "description" => $metaDesc,
                "url" => request()->url(),
                "inLanguage" => "es",
                "datePublished" => \Carbon\Carbon::parse($symptom->updated_at)->toIso8601String(),
                "dateModified" => \Carbon\Carbon::parse($symptom->updated_at)->toIso8601String(),
                "author" => [
                    "@type" => "Organization",
                    "name" => "OpenDoctor",
                    "url" => config('app.url')
                ],
                "publisher" => [
                    "@type" => "Organization",
                    "name" => "OpenDoctor",
                    "logo" => [
                        "@type" => "ImageObject",
                        "url" => asset('images/opendoctor-logo.png'),
                        "width" => 250,
                        "height" => 60
                    ]
                ],
                "medicalAudience" => [
                    "@type" => "MedicalAudience",
                    "audienceType" => "Patients"
                ],
                "mainContentOfPage" => [
                    "@type" => "WebPageElement",
                    "cssSelector" => ".symptom-clinical-content"
                ],
                "articleBody" => strip_tags($clinicalDescription ?? '')
            ];

            // ===================================
            // SCHEMA.ORG: BreadcrumbList
            // ===================================
            $breadcrumbSchema = [
                "@context" => "https://schema.org",
                "@type" => "BreadcrumbList",
                "itemListElement" => [
                    [
                        "@type" => "ListItem",
                        "position" => 1,
                        "name" => "Inicio",
                        "item" => url('/')
                    ],
                    [
                        "@type" => "ListItem",
                        "position" => 2,
                        "name" => "Síntomas",
                        "item" => route('symptom.index')
                    ],
                    [
                        "@type" => "ListItem",
                        "position" => 3,
                        "name" => $symptom->search_query,
                        "item" => request()->url()
                    ]
                ]
            ];

            // ===================================
            // SCHEMA.ORG: FAQPage (Si hay preguntas)
            // ===================================
            $faqSchema = [
                "@context" => "https://schema.org",
                "@type" => "FAQPage",
                "mainEntity" => [
                    [
                        "@type" => "Question",
                        "name" => "¿Cuándo debo consultar a un médico por " . $symptom->search_query . "?",
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => "Consulta urgentemente si tienes signos de alarma graves. Para casos leves, puedes intentar autocuidado primero, pero si persiste más de " . ($persistenceDays ?? 3) . " días, busca atención médica."
                        ]
                    ],
                    [
                        "@type" => "Question",
                        "name" => "¿Cuáles son las causas más comunes?",
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => strip_tags($commonCauses ?? 'Consulta con un especialista para determinar la causa exacta.')
                        ]
                    ]
                ]
            ];
        @endphp

        <!-- Inyectar Schemas JSON-LD -->
        <script type="application/ld+json">
            {!! json_encode($articleSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
        <script type="application/ld+json">
            {!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
        <script type="application/ld+json">
            {!! json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    </x-slot:seo>

    <div class="bg-gradient-to-b from-slate-50 to-white min-h-screen">
        <div class="max-w-6xl mx-auto px-4 py-8 sm:py-12">
            
            <!-- 🗺️ BREADCRUMBS -->
            <nav aria-label="Migas de pan" class="mb-8">
                <ol class="flex flex-wrap items-center gap-2 text-xs sm:text-sm text-slate-600">
                    <li><a href="/" class="hover:text-blue-600 font-medium transition">Inicio</a></li>
                    <li class="text-slate-400">/</li>
                    <li><a href="{{ route('symptom.index') }}" class="hover:text-blue-600 font-medium transition">Síntomas</a></li>
                    <li class="text-slate-400">/</li>
                    <li class="text-slate-900 font-semibold" aria-current="page">{{ $symptom->search_query }}</li>
                </ol>
            </nav>

            <!-- ===================================
                 SECCIÓN: HERO + URGENCIA
                 =================================== -->
            <header class="mb-12">
                <h1 class="text-3xl sm:text-4xl font-black text-slate-900 mb-3 leading-tight">
                    {{ $title ?? $symptom->search_query }}
                </h1>
                <p class="text-lg text-slate-600 font-medium mb-2">
                    Información médica verificada y recomendaciones de atención
                </p>
                <p class="text-sm text-slate-500">
                    Última actualización: {{ \Carbon\Carbon::parse($symptom->updated_at)->translatedFormat('d \d\e F \d\e Y') }}
                    <span class="mx-2">•</span>
                    Tiempo de lectura: ~5 min
                </p>
            </header>

            <!-- ===================================
                 ALERT: URGENCIA CLÍNICA
                 =================================== -->
            <section class="mb-8 border-l-4 rounded-r-2xl p-6 shadow-sm {{ 
                strtolower($urgency) === 'alta' 
                    ? 'bg-red-50 border-red-500 text-red-900' 
                    : (strtolower($urgency) === 'media' 
                        ? 'bg-yellow-50 border-yellow-500 text-yellow-900' 
                        : 'bg-green-50 border-green-500 text-green-900') }}" 
                role="alert" aria-live="polite">
                
                <div class="flex gap-4">
                    <!-- Icon -->
                    <div class="flex-shrink-0">
                        @if(strtolower($urgency) === 'alta')
                            <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <svg class="w-6 h-6 {{ strtolower($urgency) === 'media' ? 'text-yellow-600' : 'text-green-600' }}" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                    </div>
                    
                    <!-- Content -->
                    <div class="flex-1">
                        <h2 class="text-lg font-black mb-2">
                            Prioridad de Atención: 
                            <span class="inline-block ml-2 px-3 py-1 rounded-full text-xs font-black text-white {{ 
                                strtolower($urgency) === 'alta' 
                                    ? 'bg-red-600' 
                                    : (strtolower($urgency) === 'media' 
                                        ? 'bg-yellow-600' 
                                        : 'bg-green-600') }}">
                                {{ strtoupper($urgency) }}
                            </span>
                        </h2>
                        <p class="text-base leading-relaxed font-medium">
                            {{ $recommendation }}
                        </p>
                        
                        @isset($specialtyData)
                            <p class="text-sm mt-3 opacity-90 font-semibold">
                                📋 Especialidades relacionadas:
                                <a href="{{ url('/search') }}?specialty={{ $specialtyData->slug }}" class="underline hover:text-blue-700 font-bold">
                                    Ver médicos especialistas →
                                </a>
                            </p>
                        @endisset
                    </div>
                </div>
            </section>

            <!-- ===================================
                 CONTENIDO CLÍNICO: MAIN BODY (1000+ palabras)
                 =================================== -->
            <article class="symptom-clinical-content prose prose-sm max-w-none mb-12 prose-headings:font-black prose-headings:text-slate-900 prose-a:text-blue-600 prose-a:underline hover:prose-a:text-blue-700">
                
                <!-- 1️⃣ QUÉ ES -->
                <section class="mb-8">
                    <h2 class="text-2xl font-black text-slate-900 mb-4 flex items-center gap-2">
                        <span class="inline-block w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-sm font-black">1</span>
                        ¿Qué es {{ $symptom->search_query }}?
                    </h2>
                    <div class="bg-white rounded-xl border border-slate-200 p-6 space-y-4 text-slate-700 leading-relaxed">
                        {!! $clinicalDescription ?? '<p class="text-slate-500 italic">Descripción clínica disponible en tu base de datos.</p>' !!}
                    </div>
                </section>

                <!-- 2️⃣ CAUSAS COMUNES -->
                @isset($commonCauses)
                <section class="mb-8">
                    <h2 class="text-2xl font-black text-slate-900 mb-4 flex items-center gap-2">
                        <span class="inline-block w-8 h-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-sm font-black">2</span>
                        Causas Comunes
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {!! $commonCauses !!}
                    </div>
                </section>
                @endisset

                <!-- 3️⃣ SIGNOS DE ALARMA -->
                @isset($alarmSigns)
                <section class="mb-8 bg-red-50 border-l-4 border-red-500 p-6 rounded-r-xl">
                    <h2 class="text-2xl font-black text-red-900 mb-4 flex items-center gap-2">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 2.36a6.002 6.002 0 018.367 8.529.75.75 0 11-1.06 1.06h-.002a4.5 4.5 0 10.576 2.405.75.75 0 11-1.418-.248zM6.75 12.75a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd"/>
                        </svg>
                        Signos de Alarma - Busca Atención Urgente Si:
                    </h2>
                    <div class="space-y-2 text-red-900">
                        {!! $alarmSigns !!}
                    </div>
                </section>
                @endisset

                <!-- 4️⃣ FACTORES DE RIESGO -->
                @isset($riskFactors)
                <section class="mb-8">
                    <h2 class="text-2xl font-black text-slate-900 mb-4 flex items-center gap-2">
                        <span class="inline-block w-8 h-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-sm font-black">4</span>
                        Factores de Riesgo
                    </h2>
                    <ul class="space-y-2 text-slate-700">
                        {!! $riskFactors !!}
                    </ul>
                </section>
                @endisset

                <!-- 5️⃣ RECOMENDACIONES DE AUTOCUIDADO -->
                @isset($selfCareAdvice)
                <section class="mb-8 bg-emerald-50 border-l-4 border-emerald-500 p-6 rounded-r-xl">
                    <h2 class="text-2xl font-black text-emerald-900 mb-4 flex items-center gap-2">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M3.72 7.04a.75.75 0 001.06 0l10.94-10.94a.75.75 0 00-1.06-1.06L3.72 5.98a.75.75 0 000 1.06zm10.94 2.88l-2.12-2.12a.75.75 0 00-1.06 1.06l2.12 2.12-2.12 2.12a.75.75 0 101.06 1.06l2.12-2.12 2.12 2.12a.75.75 0 001.06-1.06l-2.12-2.12 2.12-2.12a.75.75 0 10-1.06-1.06l-2.12 2.12z" clip-rule="evenodd"/>
                        </svg>
                        Qué Hacer en Casa (Primeros Pasos)
                    </h2>
                    <div class="space-y-3 text-emerald-900">
                        {!! $selfCareAdvice !!}
                    </div>
                </section>
                @endisset

                <!-- 6️⃣ CUÁNDO CONSULTAR -->
                <section class="mb-8">
                    <h2 class="text-2xl font-black text-slate-900 mb-4 flex items-center gap-2">
                        <span class="inline-block w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-sm font-black">6</span>
                        ¿Cuándo Consultar a un Médico?
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
                            <h3 class="font-black text-blue-900 mb-2">⏰ Consulta Pronto</h3>
                            <p class="text-sm text-blue-800">Si los síntomas persisten más de {{ $persistenceDays ?? '3-5' }} días sin mejoría</p>
                        </div>
                        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-r-lg">
                            <h3 class="font-black text-yellow-900 mb-2">⚡ Consulta Hoy</h3>
                            <p class="text-sm text-yellow-800">Si hay síntomas moderados o cambios importantes en tu estado</p>
                        </div>
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                            <h3 class="font-black text-red-900 mb-2">🚨 Urgente</h3>
                            <p class="text-sm text-red-800">Si hay signos de alarma graves (ver sección superior)</p>
                        </div>
                    </div>
                </section>

            </article>

            <!-- ===================================
                 FAQs EXPANDIBLES CON ALPINE
                 =================================== -->
            <section class="mb-12">
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-6">
                    Preguntas Frecuentes
                </h2>
                
                <div class="space-y-3" x-data="{ openFaq: null }">
                    
                    <!-- FAQ 1 -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden bg-white hover:shadow-md transition">
                        <button 
                            @click="openFaq = openFaq === 1 ? null : 1"
                            :aria-expanded="openFaq === 1"
                            class="w-full px-6 py-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition font-black text-slate-900 text-left">
                            <span>¿Es {{ $symptom->search_query }} contagioso?</span>
                            <svg class="w-5 h-5 text-slate-500 transition" :class="{ 'rotate-180': openFaq === 1 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                            </svg>
                        </button>
                        <div x-show="openFaq === 1" x-collapse class="px-6 py-4 text-slate-700 bg-white">
                            <p>Esto depende de la causa específica. Consulta con tu médico para determinar si requiere aislamiento o precauciones especiales.</p>
                        </div>
                    </div>

                    <!-- FAQ 2 -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden bg-white hover:shadow-md transition">
                        <button 
                            @click="openFaq = openFaq === 2 ? null : 2"
                            :aria-expanded="openFaq === 2"
                            class="w-full px-6 py-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition font-black text-slate-900 text-left">
                            <span>¿Cuánto tiempo tarda en desaparecer?</span>
                            <svg class="w-5 h-5 text-slate-500 transition" :class="{ 'rotate-180': openFaq === 2 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                            </svg>
                        </button>
                        <div x-show="openFaq === 2" x-collapse class="px-6 py-4 text-slate-700 bg-white">
                            <p>La duración varía según la causa. En casos leves puede ser de {{ $persistenceDays ?? '3-7' }} días, pero los casos más complejos pueden requerir mayor tiempo. Un médico puede darte un pronóstico más preciso.</p>
                        </div>
                    </div>

                    <!-- FAQ 3 -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden bg-white hover:shadow-md transition">
                        <button 
                            @click="openFaq = openFaq === 3 ? null : 3"
                            :aria-expanded="openFaq === 3"
                            class="w-full px-6 py-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition font-black text-slate-900 text-left">
                            <span>¿Qué medicamentos puedo tomar?</span>
                            <svg class="w-5 h-5 text-slate-500 transition" :class="{ 'rotate-180': openFaq === 3 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                            </svg>
                        </button>
                        <div x-show="openFaq === 3" x-collapse class="px-6 py-4 text-slate-700 bg-white">
                            <p><strong>No automedicarse.</strong> Algunos síntomas pueden requerir medicamentos específicos. Consulta con un médico antes de tomar cualquier medicamento para asegurar que sea seguro y efectivo para tu caso particular.</p>
                        </div>
                    </div>

                    <!-- FAQ 4 -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden bg-white hover:shadow-md transition">
                        <button 
                            @click="openFaq = openFaq === 4 ? null : 4"
                            :aria-expanded="openFaq === 4"
                            class="w-full px-6 py-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition font-black text-slate-900 text-left">
                            <span>¿Es necesario ir a urgencias?</span>
                            <svg class="w-5 h-5 text-slate-500 transition" :class="{ 'rotate-180': openFaq === 4 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                            </svg>
                        </button>
                        <div x-show="openFaq === 4" x-collapse class="px-6 py-4 text-slate-700 bg-white">
                            <p>Solo si hay signos de alarma graves (ver sección anterior). Para casos leves o moderados, puedes agendar con un médico de forma presencial o virtual. En OpenDoctor tenemos disponibilidad rápida.</p>
                        </div>
                    </div>

                </div>
            </section>

            <!-- ===================================
                 CTA: AGENDAR CITA + MÉDICOS
                 =================================== -->
            <section class="mb-12">
                <div class="bg-indigo-600 rounded-2xl p-8 sm:p-12 text-white text-center shadow-lg">
                    <h2 class="text-2xl sm:text-3xl font-black mb-3">
                        ¿Necesitas Consultar Ahora?
                    </h2>
                    <p class="text-blue-100 mb-6 text-lg">
                        Tenemos médicos especialistas disponibles para atención virtual y presencial en Colombia
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ url('/search') }}?specialty={{ $specialtyData->slug ?? '' }}" class="inline-flex items-center justify-center px-8 py-3 bg-white text-blue-600 font-black rounded-full hover:bg-blue-50 transition shadow-lg">
                            Buscar Especialista
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                        <a href="{{ route('symptom.index') }}" class="inline-flex items-center justify-center px-8 py-3 bg-blue-500 text-white font-black rounded-full hover:bg-blue-400 transition">
                            Explorar Otros Síntomas
                        </a>
                    </div>
                </div>
            </section>

            <!-- ===================================
                 MÉDICOS SUGERIDOS
                 =================================== -->
            @if($doctors->isNotEmpty())
            <section aria-label="Especialistas recomendados" class="mb-12">
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-6">
                    Médicos Disponibles Ahora
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($doctors->take(4) as $doctor)
                        <article class="bg-white rounded-2xl shadow-sm border border-slate-200 hover:shadow-lg hover:border-blue-300 transition-all overflow-hidden">
                            
                            <!-- Header del Doctor -->
                            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                                <h3 class="text-white font-black text-lg">
                                    Dr(a). {{ $doctor->user->name }}
                                </h3>
                                <p class="text-blue-100 text-sm">
                                    {{ $doctor->specialties->pluck('name')->implode(', ') ?: 'Medicina General' }}
                                </p>
                            </div>

                            <!-- Body -->
                            <div class="p-6 space-y-4">
                                
                                <!-- Ubicaciones -->
                                @if($doctor->addresses->isNotEmpty())
                                <div class="space-y-2">
                                    <p class="text-xs font-black text-slate-500 uppercase tracking-wider">Sedes:</p>
                                    @foreach($doctor->addresses->take(2) as $address)
                                        <div class="flex items-start gap-2 text-sm text-slate-700">
                                            @if($address->type === 'virtual')
                                                <svg class="w-4 h-4 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                    <path d="M4.5 3a2.5 2.5 0 0 0-2.5 2.5v8A2.5 2.5 0 0 0 4.5 16h11a2.5 2.5 0 0 0 2.5-2.5v-8A2.5 2.5 0 0 0 15.5 3h-11zm1 2h9a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5v-6a.5.5 0 0 1 .5-.5z"/>
                                                </svg>
                                                <span class="font-medium">Consulta Virtual</span>
                                            @else
                                                <svg class="w-4 h-4 text-slate-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                                </svg>
                                                <span class="font-medium">{{ $address->name }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                @endif

                                <!-- Calificación (si existe) -->
                                <div class="pt-2 border-t border-slate-100">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-black text-slate-500 uppercase">Disponibilidad</span>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black bg-green-100 text-green-800">
                                            ✓ Disponible
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- CTA -->
                            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                                <a href="{{ url('/medical-partner/' . $doctor->slug) }}" class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-black py-2.5 rounded-lg transition">
                                    Ver Perfil y Agendar
                                </a>
                            </div>

                        </article>
                    @endforeach
                </div>

                @if($doctors->count() > 4)
                <div class="text-center mt-8">
                    <a href="{{ url('/search') }}?specialty={{ $specialtyData->slug ?? '' }}" class="inline-flex items-center gap-2 px-6 py-3 border-2 border-blue-600 text-blue-600 font-black rounded-full hover:bg-blue-50 transition">
                        Ver Todos los Especialistas
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                </div>
                @endif

            </section>
            @endif

            <!-- ===================================
                 DISCLAIMER MÉDICO
                 =================================== -->
            <div class="bg-slate-100 rounded-xl p-6 text-center text-xs text-slate-600 border border-slate-200">
                <p class="font-medium mb-1">⚠️ Descargo Legal</p>
                <p>
                    Esta información es de carácter educativo y no reemplaza la consulta médica profesional. 
                    Para diagnóstico y tratamiento específico, consulta siempre con un médico certificado. 
                    En caso de emergencia, llama al 911 o acude al hospital más cercano.
                </p>
            </div>

        </div>
    </div>
</x-guest-layout>