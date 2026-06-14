<x-guest-layout>
    <!-- Inyección exclusiva para el HEAD (SEO Avanzado) -->
    <x-slot:seo>
        <title>{{ $metaTitle }}</title>
        <meta name="title" content="{{ $metaTitle }}">
        <meta name="description" content="{{ $metaDesc }}">
        <meta name="robots" content="{{ strtolower($urgency) === 'alta' ? 'noindex, follow' : 'index, follow' }}">
        <link rel="canonical" href="{{ request()->url() }}">
        
        @php
            // Marcado estructurado Schema.org para posicionamiento de nivel médico
            $schemaData = [
                "@context" => "https://schema.org",
                "@type" => "MedicalWebPage",
                "name" => $metaTitle,
                "description" => $metaDesc,
                "url" => request()->url(),
                "inLanguage" => "es",
                "mainContentOfPage" => [
                    "@type" => "WebPageElement",
                    "cssSelector" => ".symptom-content"
                ],
                "medicalAudience" => [
                    "@type" => "MedicalAudience",
                    "audienceType" => "Patients"
                ]
            ];

            // Marcado de navegación estructurada (Breadcrumbs)
            $breadcrumbData = [
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
        @endphp

        <script type="application/ld+json">
            {!! json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
        <script type="application/ld+json">
            {!! json_encode($breadcrumbData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    </x-slot:seo>
    
    <div class="max-w-7xl mx-auto px-4 py-8 mt-5 symptom-content">
        <div class="w-full mt-6">
            
            <!-- 🗺️ Migas de pan (Breadcrumbs) optimizadas -->
            <nav aria-label="Breadcrumb" class="mb-6">
                <ol class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                    <li>
                        <a href="/" class="hover:text-blue-600 transition font-medium">Inicio</a>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="text-slate-400" aria-hidden="true">/</span>
                        <a href="{{ route('symptom.index') }}" class="hover:text-blue-600 transition font-medium">Síntomas</a>
                    </li>
                    <li class="flex items-center gap-2 text-slate-800 font-semibold" aria-current="page">
                        <span class="text-slate-400" aria-hidden="true">/</span>
                        <span class="truncate max-w-[180px] sm:max-w-[300px]">{{ $symptom->search_query }}</span>
                    </li>
                </ol>
            </nav>

            <!-- Encabezado Clínico Semántico -->
            <header class="mb-8 border-b border-slate-200 pb-5">
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mb-2 leading-tight">
                    Orientación médica para: <span class="text-blue-600">"{{ $title }}"</span>
                </h1>
                <p class="text-slate-500 text-sm">Análisis y derivación clínica automatizada para pacientes.</p>
            </header>

            <!-- 🛡️ Banner de Triage Clínico (AI Advice) con URL de Buscador Real de Producción -->
            <section class="{{ strtolower($urgency) === 'alta' ? 'bg-rose-50 border-rose-500 text-rose-950' : (strtolower($urgency) === 'media' ? 'bg-amber-50 border-amber-500 text-amber-950' : 'bg-emerald-50 border-emerald-500 text-emerald-950') }} border-l-4 shadow-sm p-6 rounded-r-2xl mb-8 flex items-start gap-4" role="alert">
                <!-- SVG Nativo: Information-Circle de Heroicons -->
                <svg class="w-6 h-6 mt-0.5 flex-shrink-0 {{ strtolower($urgency) === 'alta' ? 'text-rose-600' : (strtolower($urgency) === 'media' ? 'text-amber-600' : 'text-emerald-600') }}" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 111.063.852l-.708 2.836a.75.75 0 001.063.852l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                
                <div class="flex-1">
                    <h2 class="text-lg font-black mb-2 flex flex-wrap items-center gap-2">
                        Prioridad de Atención Recomendada: 
                        <span class="{{ strtolower($urgency) === 'alta' ? 'bg-rose-600' : (strtolower($urgency) === 'media' ? 'bg-amber-600' : 'bg-emerald-600') }} inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-black text-white uppercase tracking-wider">
                            {{ $urgency }}
                        </span>
                    </h2>
                    <p class="text-base leading-relaxed font-medium mb-3">
                        {{ $recommendation }}
                    </p>
                    
                    {{-- 🛡️ BLINDAJE EN VISTA: Cero caídas si la variable no viene definida --}}
                    @isset($specialtyData)
                        @if($specialtyData)
                            <p class="text-sm opacity-95 font-semibold">
                                Contamos con una red activa de profesionales verificados.
                                <a href="{{ url('/search') }}?specialty={{ $specialtyData->slug }}&city=" class="underline font-bold ml-1 hover:text-blue-700 transition-colors">
                                    Ver todo el catálogo de {{ $specialtyData->name }} →
                                </a>
                            </p>
                        @endif
                    @endisset

                </div>
            </section>

            <!-- 🩺 Listado de Médicos Sugeridos Disponibles (Híbrido: Particulares + Clínicas Corporativas) -->
            <section class="mt-8" aria-label="Especialistas recomendados">
                <h2 class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-6 flex items-center gap-2">
                    <!-- SVG Nativo: Identification de Heroicons -->
                    <svg class="w-4 h-4 text-slate-400" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                    </svg>
                    Especialistas disponibles para asignación directa:
                </h2>

                <div class="space-y-4">
                    @forelse($doctors as $doctor)
                        <article class="bg-white rounded-2xl shadow-sm border border-slate-100 border-b-2 border-b-blue-600 p-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 transition hover:shadow-md" itemscope typeof="MedicalBusiness">
                            <div>
                                <h3 class="text-slate-900 font-extrabold text-lg mb-1" itemprop="name">
                                    Dr(a). {{ $doctor->user->name }}
                                </h3>
                                
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-50 text-slate-700 border border-slate-200">
                                        {{ $doctor->specialties->pluck('name')->implode(', ') ?: 'Especialista' }}
                                    </span>
                                    
                                    {{-- Iterar Sedes Híbridas del Doctor --}}
                                    @if($doctor->addresses->isNotEmpty())
                                        @foreach($doctor->addresses->take(2) as $address)
                                            <span class="text-xs text-slate-500 inline-flex items-center gap-1.5 bg-slate-50 border border-slate-100 px-2.5 py-1 rounded-md" itemprop="address" itemscope typeof="PostalAddress">
                                                @if($address->type === 'virtual')
                                                    <!-- SVG Nativo: Video-Camera de Heroicons -->
                                                    <svg class="w-3.5 h-3.5 text-purple-600 flex-shrink-0" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                                                    </svg>
                                                @else
                                                    <!-- SVG Nativo: Map-Pin de Heroicons -->
                                                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                                    </svg>
                                                @endif
                                                
                                                <span itemprop="addressLocality" class="text-xs font-medium">{{ $address->name }} ({{ $address->city->name }})</span>
                                                
                                                @if($address->clinic_id)
                                                    <span class="text-[10px] bg-purple-100 text-purple-800 font-bold px-1.5 py-0.2 rounded-full uppercase tracking-tight ml-0.5">Staff</span>
                                                @endif
                                            </span>
                                        @endforeach
                                    @endif
                                </div>
                            </div>

                            <div class="w-full sm:w-auto flex-shrink-0">
                                <a href="{{ url('/medical-partner/' . $doctor->slug) }}" class="block w-full sm:w-auto text-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-black px-6 py-3 rounded-full transition shadow-sm focus:ring-4 focus:ring-blue-200">
                                    Agendar Cita
                                </a>
                            </div>
                        </article>
                        @empty
                        <!-- 🛡️ Estado de respaldo optimizado: Redirección inteligente a Medicina General -->
                        <div class="text-center p-8 sm:p-12 border border-dashed border-slate-200 bg-white rounded-2xl flex flex-col items-center dark:bg-gray-800 dark:border-gray-700">
                            <!-- SVG Nativo: Magnifying-Glass de Heroicons -->
                            <svg class="w-10 h-10 text-slate-400 mb-3" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.604 10.604z" />
                            </svg>
                            
                            <h4 class="font-bold text-slate-900 text-lg mb-1 dark:text-white">
                                No hay especialistas directos asignados en este momento
                            </h4>
                            
                            <p class="text-slate-500 text-sm max-w-md mb-5 dark:text-slate-400">
                                Contamos con médicos generales e institucionales listos para evaluar tu caso de forma inicial y derivarte correctamente.
                            </p>
                            
                            {{-- 🔒 ENLACE ESTRATÉGICO: Filtra directamente por Medicina General y deja la ciudad abierta --}}
                            <a href="{{ url('/search') }}?specialty=medicina-general&city=" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black px-6 py-3 rounded-full transition shadow-sm focus:ring-4 focus:ring-blue-200">
                                Consultar con Medicina General
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                </svg>
                            </a>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-guest-layout>
