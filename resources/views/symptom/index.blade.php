<x-guest-layout>
    <!-- Inyección exclusiva para el HEAD -->
    <x-slot:seo>
        <title>{{ $metaTitle }}</title>
        <meta name="title" content="{{ $metaTitle }}">
        <meta name="description" content="{{ $metaDesc }}">
        <meta name="robots" content="index, follow">
        <link rel="canonical" href="{{ request()->url() }}">
        
        @php
            // Schema estructurado para la página de catálogo de orientación médica
            $catalogSchema = [
                "@context" => "https://schema.org",
                "@type" => "MedicalWebPage",
                "name" => $metaTitle,
                "description" => $metaDesc,
                "url" => request()->url(),
                "inLanguage" => "es",
                "medicalAudience" => [
                    "@type" => "MedicalAudience",
                    "audienceType" => "Patients"
                ]
            ];
        @endphp

        <script type="application/ld+json">
            {!! json_encode($catalogSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    </x-slot:seo>
    
    <div class="max-w-7xl mx-auto px-4 py-8 mt-5">
        <div class="w-full mt-6">
            
            <!-- Encabezado Principal de la Guía Médica -->
            <header class="text-center max-w-3xl mx-auto mb-12 mt-6">
                <span class="text-xs font-bold text-blue-600 uppercase tracking-widest bg-blue-50 px-3 py-1.5 rounded-full dark:bg-blue-900/30 dark:text-blue-400">
                    Asistente de Orientación Clínica
                </span>
                <h1 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight mt-4 mb-4 leading-tight dark:text-white">
                    Guía de Orientación Médica por Síntomas
                </h1>
                <p class="text-slate-500 text-base sm:text-lg dark:text-slate-400">
                    Selecciona tu síntoma para evaluar el nivel de urgencia sugerido por nuestro sistema y agenda una cita inmediata con especialistas verificados.
                </p>
            </header>
            <!-- 🗂️ Catálogo y Grilla de Síntomas Indexados -->
            <section aria-label="Listado de síntomas disponibles">
                @if($symptoms->isEmpty())
                    <div class="text-center p-12 border border-dashed border-slate-200 bg-white rounded-2xl dark:bg-gray-800 dark:border-gray-700">
                        <div class="text-4xl mb-3" aria-hidden="true">🔍</div>
                        <h2 class="font-bold text-slate-900 text-lg mb-1 dark:text-white">No hay síntomas indexados todavía</h2>
                        <p class="text-slate-500 text-sm max-w-md mx-auto dark:text-slate-400">Nuestro asistente clínico se encuentra procesando nuevas consultas en lenguaje natural.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($symptoms as $item)
                            <article class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between dark:bg-gray-800 dark:border-gray-700">
                                <div>
                                    <!-- Cabecera de la Tarjeta con Nivel de Urgencia -->
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider dark:text-slate-500">
                                            Especialidad: {{ $item->specialty_name ?? 'Medicina General' }}
                                        </span>
                                        
                                        @if(strtolower($item->urgency_level) === 'alta')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-100 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800 uppercase tracking-wide">
                                                Urgencia Alta
                                            </span>
                                        @elseif(strtolower($item->urgency_level) === 'media')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-50 text-amber-700 border border-amber-100 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800 uppercase tracking-wide">
                                                Moderada
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800 uppercase tracking-wide">
                                                Baja
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Nombre del Síntoma / Enfermedad -->
                                    <h2 class="text-slate-900 font-extrabold text-base mb-4 leading-snug hover:text-blue-600 transition dark:text-white">
                                        <a href="{{ route('symptom.landing', ['slug' => $item->slug]) }}">
                                            "{{ $item->search_query }}"
                                        </a>
                                    </h2>
                                </div>

                                <!-- Botón de Acción Directo -->
                                <div class="pt-2 border-t border-slate-50 dark:border-gray-700">
                                    <a href="{{ route('symptom.landing', ['slug' => $item->slug]) }}" class="w-full inline-flex justify-center items-center py-2 px-4 text-xs font-bold text-center text-blue-600 bg-blue-50 rounded-xl hover:bg-blue-100 transition-colors focus:ring-4 focus:ring-blue-100 dark:bg-gray-700 dark:text-blue-400 dark:hover:bg-gray-600">
                                        Evaluar caso y ver doctores
                                        <svg class="w-3.5 h-3.5 ml-1.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <!-- Enlaces de Paginación Estables para Tailwind CSS -->
                    <div class="mt-10 flex justify-center">
                        {{ $symptoms->links() }}
                    </div>
                @endif
            </section>

        </div>
    </div>
</x-guest-layout>
