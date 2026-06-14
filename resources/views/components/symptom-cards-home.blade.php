@php
    // 🛡️ OPTIMIZACIÓN EN PRODUCCIÓN: Consultamos 2 síntomas aleatorios que tengan especialidad asignada
    $randomSymptoms = \Illuminate\Support\Facades\DB::table('indexed_symptoms')
        ->join('specialties', 'indexed_symptoms.specialty_id', '=', 'specialties.id')
        ->select(
            'indexed_symptoms.search_query',
            'indexed_symptoms.slug as symptom_slug',
            'indexed_symptoms.urgency_level',
            'indexed_symptoms.ai_advice',
            'specialties.name as specialty_name',
            'specialties.slug as specialty_slug'
        )
        ->inRandomOrder()
        ->limit(2)
        ->get();
@endphp

@if($randomSymptoms->isNotEmpty())
    <section class="max-w-7xl mx-auto px-4" aria-labelledby="symptoms-home-title">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-8">
            <div>
                <h2 id="symptoms-home-title" class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight dark:text-white">
                    ¿No sabes a qué especialista médico consultar?
                </h2>
                <p class="text-slate-500 text-sm sm:text-base mt-1 dark:text-slate-400">
                    Orientación clínica basada en síntomas comunes y derivación médica inmediata.
                </p>
            </div>
            <a href="{{ route('symptom.index') }}" class="text-sm font-bold text-blue-600 hover:text-blue-700 transition inline-flex items-center gap-1.5 mt-4 md:mt-0 group dark:text-blue-400 dark:hover:text-blue-300">
                Ver diccionario completo de síntomas
                <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </a>
        </div>

        <!-- Grilla con las 2 Tarjetas Semánticas -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($randomSymptoms as $symptom)
                <article class="bg-white border border-slate-100 rounded-2xl p-6 shadow-sm flex flex-col justify-between transition hover:shadow-md dark:bg-gray-800 dark:border-gray-700">
                    <div>
                        <!-- Cabecera de la tarjeta -->
                        <div class="flex items-center justify-between gap-2 mb-4">
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-purple-600 bg-purple-50 px-2.5 py-1 rounded-md dark:bg-purple-900/30 dark:text-purple-400">
                                <!-- SVG Nativo: Sparkles de Heroicons -->
                                <svg class="w-3.5 h-3.5" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 21l-.813-5.096L3 15l5.096-.813L9 9l.813 5.096L15 15l-5.187.904zM18 10.5l-.5 3-.5-3-3-.5 3-.5.5-3 .5 3 3 .5-3 .5zM14.25 4.5l-.25 1.5-.25-1.5-1.5-.25 1.5-.25.25-1.5.25 1.5 1.5.25-1.5.25z" />
                                </svg>
                                Síntoma Común
                            </span>

                            @if(strtolower($symptom->urgency_level) === 'alta')
                                <span class="text-[10px] uppercase font-black bg-rose-50 text-rose-700 border border-rose-100 px-2 py-0.5 rounded-full dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800 tracking-wide">Urgencia Alta</span>
                            @elseif(strtolower($symptom->urgency_level) === 'media')
                                <span class="text-[10px] uppercase font-black bg-amber-50 text-amber-700 border border-amber-100 px-2 py-0.5 rounded-full dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800 tracking-wide">Moderada</span>
                            @else
                                <span class="text-[10px] uppercase font-black bg-emerald-50 text-emerald-700 border border-emerald-100 px-2 py-0.5 rounded-full dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800 tracking-wide">Baja</span>
                            @endif
                        </div>

                        <!-- Título y descripción semántica -->
                        <h3 class="text-slate-900 font-extrabold text-lg mb-2 leading-tight dark:text-white">
                            <a href="{{ route('symptom.landing', ['slug' => $symptom->symptom_slug]) }}" class="hover:text-blue-600 transition">
                                "{{ $symptom->search_query }}"
                            </a>
                        </h3>
                        <p class="text-slate-600 text-sm leading-relaxed mb-4 line-clamp-2 dark:text-slate-300">
                            {{ $symptom->ai_advice }}
                        </p>

                        <!-- Indicación del especialista idóneo -->
                        <div class="bg-slate-50 border border-slate-100 p-3 rounded-xl mb-5 flex items-center gap-2.5 dark:bg-gray-700/50 dark:border-gray-700">
                            <!-- SVG Nativo: User-Group de Heroicons -->
                            <svg class="w-4 h-4 text-blue-600 flex-shrink-0 dark:text-blue-400" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94-3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                            </svg>
                            <p class="text-xs text-slate-700 dark:text-slate-300 font-medium">
                                Atendido por especialistas en: <strong class="text-slate-900 font-bold dark:text-white">{{ $symptom->specialty_name }}</strong>
                            </p>
                        </div>
                    </div>

                    <!-- Enlace comercial directo del buscador -->
                    <div class="pt-3 border-t border-slate-50 dark:border-gray-700">
                        <a href="{{ url('/search') }}?specialty={{ $symptom->specialty_slug }}&city=" class="w-full inline-flex justify-center items-center p-4 text-base font-black text-center text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition shadow-sm focus:ring-4 focus:ring-blue-100">
                            Agendar Especialista de {{ $symptom->specialty_name }}
                            <!-- SVG Nativo: Calendar de Heroicons -->
                            <svg class="w-3.5 h-3.5 ml-1.5" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
                            </svg>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
