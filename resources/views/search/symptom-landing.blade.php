<x-guest-layout>
    <!-- Inyección exclusiva para el HEAD -->
    <x-slot:seo>
        <meta name="title" content="{{ $seoTitle }}">
        <meta name="description" content="{{ $seoDescription }}">
        <meta name="robots" content="{{ $metaRobots }}">
        @php
            // 1. Construimos el arreglo base con datos limpios
            $schemaData = [
                "@context" => "https://schema.org",
                "@type" => "MedicalWebPage",
                "name" => $seoTitle,
                "description" => $seoDescription,
                "url" => request()->url(),
                "aspect" => "Análisis de síntomas y derivación médica"                
            ];

            $schemaData["mainContentOfPage"] = [
                "@type" => "WebPageElement",
                "cssSelector" => ".symptom-content"
            ];

            $schemaData["medicalAudience"] = [
                "@type" => "MedicalAudience",
                "audienceType" => "Patients"
            ];
        @endphp

        {{-- 3. Renderizado seguro: Evita comillas rotas o saltos de línea destructivos --}}
        <script type="application/ld+json">
            {!! json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
        </script>
    </x-slot:seo>
    
    <div class="max-w-7xl mx-auto px-4 py-8 mt-5 symptom-content">
        <div class="flex flex-col items-center justify-center">
            <div class="mt-8 pt-5">
                
                <!-- Migas de pan (Breadcrumbs) óptimas para el SEO de Google -->
                <nav aria-label="breadcrumb" class="mb-6">
                    <ol class="flex items-center space-x-2 text-xs text-slate-500">
                        <li>
                            <a href="/" class="hover:text-blue-600 transition">Inicio</a>
                        </li>
                        <li class="flex items-center space-x-2">
                            <span>/</span>
                            <a href="{{ route('search.symptom.view') }}" class="hover:text-blue-600 transition">Síntomas</a>
                        </li>
                        <li class="flex items-center space-x-2 text-slate-800 font-medium overflow-hidden" aria-current="page">
                            <span>/</span>
                            <span class="truncate max-w-[200px] sm:max-w-[350px]">{{ $symptom->search_query }}</span>
                        </li>
                    </ol>
                </nav>

                <!-- Encabezado Clínico de la Consulta -->
                <div class="mb-6 border-b border-slate-200 pb-5">
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mb-2">
                        Orientación para: <span class="text-blue-600">"{{ $symptom->search_query }}"</span>
                    </h1>
                    <p class="text-slate-500 text-sm">Análisis y derivación clínica automatizada en lenguaje natural.</p>
                </div>

                <!-- Banner de Triage Fijo -->
                <div class="{{ $symptom->urgency_level === 'Alta' ? 'bg-white border-rose-500 text-rose-950' : ($symptom->urgency_level === 'Media' ? 'bg-amber-50 border-amber-500 text-amber-950' : 'bg-emerald-50 border-emerald-500 text-emerald-950') }} border-l-4 shadow-sm p-5 rounded-r-2xl mb-8 flex items-start gap-4" role="alert">
                    <span class="text-3xl leading-none">💡</span>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold mb-2 flex items-center gap-2">
                            Prioridad de Atención: 
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-black bg-white-900 text-white uppercase">
                                {{ $symptom->urgency_level }}
                            </span>
                        </h3>
                        <p class="text-base leading-relaxed mb-3">
                            {{ $symptom->ai_advice }}
                        </p>
                        @if ($symptom->specialty)
                        <p class="text-sm opacity-90 font-medium">
                            Contamos con una red activa de profesionales verificados.
                            <a href="{{ route('search') }}?specialty={{ $symptom->specialty->slug }}" class="underline font-bold ml-1 hover:opacity-80 transition">
                                Ver todo el catálogo de {{ $symptom->specialty->name }} →
                            </a>
                        </p>
                        @endif
                    </div>
                </div>

                <!-- Listado de Médicos Sugeridos Disponibles -->
                <div class="mt-6">
                    <h4 class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-4 flex items-center gap-2">
                        <span>👨‍⚕️</span> Especialistas recomendados en {{ $symptom->specialty ? $symptom->specialty->name : 'General' }} disponibles:
                    </h4>

                    <div class="space-y-4">
                        @forelse($doctors as $doctor)
                            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 border-b-2 border-b-blue-600 p-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 transition hover:shadow-md">
                                <div>
                                    <h5 class="text-slate-900 font-bold text-lg mb-1">Dr(a). {{ $doctor->user->name }} {{ $doctor->user->last_name }}</h5>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-50 text-slate-700 border border-slate-200">
                                            {{ $symptom->specialty ? $symptom->specialty->name : 'General' }}
                                        </span>
                                        @if($doctor->addresses->isNotEmpty())
                                            <span class="text-xs text-slate-500 flex items-center gap-1">
                                                <span>📍</span> {{ $doctor->addresses->first()->city->name }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <a href="/doctor/{{ $doctor->slug }}" class="w-full sm:w-auto text-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-6 py-2.5 rounded-full transition shadow-sm">
                                    Agendar Cita
                                </a>
                            </div>
                        @empty
                            <!-- Estado de respaldo si no hay médicos en esa área todavía -->
                            <div class="text-center p-8 sm:p-12 border border-dashed border-slate-200 bg-white rounded-2xl flex flex-col items-center">
                                <div class="text-4xl mb-3">🔍</div>
                                <h5 class="font-bold text-slate-900 text-lg mb-1">No hay médicos directos asignados en este momento</h5>
                                <p class="text-slate-500 text-sm max-w-md mb-4">Contamos con médicos generales listos para evaluar tu caso de forma inicial.</p>
                                <a href="{{ route('search') }}" class="inline-flex items-center bg-white border border-blue-600 text-blue-600 hover:bg-blue-50 text-xs font-bold px-5 py-2.5 rounded-full transition shadow-sm">
                                    Explorar directorio completo
                                </a>
                            </div>
                        @endforelse
                    </div>

                    <!-- Paginación compatible con Tailwind -->
                    <div class="flex justify-center mt-6">
                        {{ $doctors->links() }}
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-guest-layout>
