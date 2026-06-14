<x-guest-layout>
    <!-- Inyección exclusiva para el HEAD -->
    <x-slot:seo>
        <title>{{ $metaTitle }}</title>
        <meta name="title" content="{{ $metaTitle }}">
        <meta name="description" content="{{ $metaDesc }}">
        <meta name="robots" content="{{ $symptom->urgency_level === 'Alta' ? 'noindex, follow' : 'index, follow' }}">
        <link rel="canonical" href="{{ request()->url() }}">
        
        @php
            // Schema.org de nivel médico para fragmentos enriquecidos (Rich Snippets)
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

            // Schema estructurado para las migas de pan (Breadcrumbs)
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
                        "item" => url('/sintomas')
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
        <div class="w-full">
            
            <!-- Migas de pan (Breadcrumbs) optimizadas con marcado accesible aria -->
            <nav aria-label="Breadcrumb" class="mb-6">
                <ol class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                    <li>
                        <a href="/" class="hover:text-blue-600 transition font-medium">Inicio</a>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="text-slate-400" aria-hidden="true">/</span>
                        {{-- 🛡️ BLINDAJE: Usamos url('/sintomas') para evitar la excepción por falta de slug --}}
                        <a href="{{ url('/sintomas') }}" class="hover:text-blue-600 transition font-medium">Síntomas</a>
                    </li>
                    <li class="flex items-center gap-2 text-slate-800 font-semibold" aria-current="page">
                        <span class="text-slate-400" aria-hidden="true">/</span>
                        <span class="truncate max-w-[180px] sm:max-w-[300px]">{{ $symptom->search_query }}</span>
                    </li>
                </ol>
            </nav>


            <!-- Encabezado Clínico de la Consulta Semántica -->
            <header class="mb-8 border-b border-slate-200 pb-5">
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mb-2 leading-tight">
                    Orientación médica para: <span class="text-blue-600">"{{ $symptom->search_query }}"</span>
                </h1>
                <p class="text-slate-500 text-sm">Análisis y derivación clínica automatizada en lenguaje natural para pacientes.</p>
            </header>
            <!-- 🛡️ Banner de Triage Clínico con Semántica Optimizada -->
            <section class="{{ $symptom->urgency_level === 'Alta' ? 'bg-rose-50 border-rose-500 text-rose-950' : ($symptom->urgency_level === 'Media' ? 'bg-amber-50 border-amber-500 text-amber-950' : 'bg-emerald-50 border-emerald-500 text-emerald-950') }} border-l-4 shadow-sm p-6 rounded-r-2xl mb-8 flex items-start gap-4" role="alert">
                <span class="text-3xl leading-none" aria-hidden="true">💡</span>
                <div class="flex-1">
                    <h2 class="text-lg font-black mb-2 flex flex-wrap items-center gap-2">
                        Prioridad de Atención Recomendada: 
                        <span class="{{ $symptom->urgency_level === 'Alta' ? 'bg-rose-600' : ($symptom->urgency_level === 'Media' ? 'bg-amber-600' : 'bg-emerald-600') }} inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-black text-white uppercase tracking-wider">
                            {{ $symptom->urgency_level }}
                        </span>
                    </h2>
                    <p class="text-base leading-relaxed font-medium mb-3">
                        {{ $symptom->ai_advice }}
                    </p>
                    @if($symptom->specialty_id && isset($symptom->specialty))
                        <p class="text-sm opacity-95 font-semibold">
                            Contamos con una red activa de profesionales verificados.
                            <a href="{{ route('search') }}?specialty={{ $symptom->specialty->slug }}" class="underline font-bold ml-1 hover:text-blue-700 transition-colors">
                                Ver todo el catálogo de {{ $symptom->specialty->name }} →
                            </a>
                        </p>
                    @endif
                </div>
            </section>
            <!-- 🩺 Listado de Médicos Sugeridos Disponibles (Híbrido: Particulares + Clínicas Corporativas) -->
            <section class="mt-8" aria-label="Especialistas recomendados">
                <h2 class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-6 flex items-center gap-2">
                    <span aria-hidden="true">👨‍⚕️</span> Especialistas disponibles para asignación directa:
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
                                    
                                    {{-- Iterar Sedes Híbridas (Físicas/Virtuales del Consultorio o Clínicas Aliadas) --}}
                                    @if($doctor->addresses->isNotEmpty())
                                        @foreach($doctor->addresses->take(2) as $address)
                                            <span class="text-xs text-slate-500 flex items-center gap-1 bg-slate-50 border border-slate-100 px-2 py-0.5 rounded-md" itemprop="address" itemscope typeof="PostalAddress">
                                                <span aria-hidden="true">📍</span> 
                                                <span itemprop="addressLocality">{{ $address->name }} ({{ $address->city->name }})</span>
                                                @if($address->clinic_id)
                                                    <span class="text-[10px] bg-purple-100 text-purple-800 font-bold px-1.5 py-0.2 rounded-full uppercase tracking-tight">Staff</span>
                                                @endif
                                            </span>
                                        @endforeach
                                    @endif
                                </div>
                            </div>

                            {{-- Enrutamiento unificado de producción: /medical-partner/{slug} --}}
                            <div class="w-full sm:w-auto flex-shrink-0">
                                <a href="{{ url('/medical-partner/' . $doctor->slug) }}" class="block w-full sm:w-auto text-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-black px-6 py-3 rounded-full transition shadow-sm focus:ring-4 focus:ring-blue-200">
                                    Agendar Cita
                                </a>
                            </div>
                        </article>
                    @empty
                        <!-- Estado de respaldo si no hay médicos en esa área todavía -->
                        <div class="text-center p-8 sm:p-12 border border-dashed border-slate-200 bg-white rounded-2xl flex flex-col items-center">
                            <div class="text-4xl mb-3" aria-hidden="true">🔍</div>
                            <h4 class="font-bold text-slate-900 text-lg mb-1">No hay médicos directos asignados en este momento</h4>
                            <p class="text-slate-500 text-sm max-w-md mb-5">Contamos con médicos generales e institucionales listos para evaluar tu caso de forma inicial.</p>
                            <a href="{{ route('search') }}" class="inline-flex items-center bg-white border border-blue-600 text-blue-600 hover:bg-blue-50 text-xs font-bold px-5 py-2.5 rounded-full transition shadow-sm">
                                Explorar directorio completo
                            </a>
                        </div>
                    @endforelse
                </div>

                <!-- Paginación compatible con Tailwind CSS -->
                @if($doctors->isNotEmpty() && method_exists($doctors, 'links'))
                    <div class="flex justify-center mt-8">
                        {{ $doctors->links() }}
                    </div>
                @endif
            </section>

        </div>
    </div>
</x-guest-layout>
