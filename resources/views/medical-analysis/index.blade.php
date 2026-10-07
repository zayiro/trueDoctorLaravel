<x-guest-layout
    :meta-title-medical-analysis="$meta_title_medicalAnalysis"
    :meta-description-medical-analysis="$meta_description_medicalAnalysis"
>
@php
    $exams = [
        ['icon' => 'fa-vial',        'title' => 'Exámenes de laboratorio', 'text' => 'Sangre, orina y perfiles bioquímicos.',           'tone' => 'bg-blue-50 text-blue-600'],
        ['icon' => 'fa-x-ray',       'title' => 'Radiografía',             'text' => 'Placas e imágenes de Rayos X convencionales.',    'tone' => 'bg-emerald-50 text-emerald-600'],
        ['icon' => 'fa-wave-square', 'title' => 'Ecografía',               'text' => 'Ultrasonido diagnóstico y Doppler.',              'tone' => 'bg-indigo-50 text-indigo-600'],
        ['icon' => 'fa-layer-group', 'title' => 'Tomografía',              'text' => 'Estudios tomográficos seriados (TAC).',           'tone' => 'bg-blue-50 text-blue-600'],
        ['icon' => 'fa-brain',       'title' => 'Resonancia',              'text' => 'Resonancia magnética nuclear de alta definición.', 'tone' => 'bg-emerald-50 text-emerald-600'],
        ['icon' => 'fa-file-medical', 'title' => 'DICOM',                  'text' => 'Carga directa de archivos de imagen médica (.dcm).', 'tone' => 'bg-indigo-50 text-indigo-600'],
    ];

    $steps = [
        ['n' => '1', 'title' => 'Carga tus documentos', 'text' => 'Sube PDF clínicos, historiales, laboratorios o imágenes de tus estudios.',        'tone' => 'bg-blue-50 text-blue-600'],
        ['n' => '2', 'title' => 'Ocultamos tus datos',   'text' => 'Borramos tu nombre y cédula de forma automática antes de analizar.',                 'tone' => 'bg-emerald-50 text-emerald-600'],
        ['n' => '3', 'title' => 'Recibe tu segunda opinión', 'text' => 'Una explicación en lenguaje claro y la correlación con tus síntomas.',           'tone' => 'bg-indigo-50 text-indigo-600'],
    ];

    $reasons = [
        ['icon' => 'fa-diagram-project', 'title' => 'Análisis transversal',  'text' => 'Un ser humano tarda horas en cruzar datos de 5 informes distintos. Nuestra IA analiza varios documentos en segundos buscando patrones.', 'tone' => 'bg-blue-50 text-blue-600'],
        ['icon' => 'fa-comment-dots',    'title' => 'Lenguaje claro',        'text' => 'Traducimos los tecnicismos a explicaciones sencillas. Entenderás qué significa cada indicador de tus exámenes.',                          'tone' => 'bg-emerald-50 text-emerald-600'],
        ['icon' => 'fa-user-shield',     'title' => 'Privacidad ante todo',  'text' => 'Tus archivos pasan por un motor de sanitizado que oculta tus datos personales antes de llegar a la Inteligencia Artificial.',            'tone' => 'bg-indigo-50 text-indigo-600'],
    ];

    $faqs = [
        ['q' => '¿Esto reemplaza a mi médico?', 'a' => 'No. Es una segunda lectura de apoyo, en lenguaje claro, para que llegues a tu consulta con mejores preguntas. El diagnóstico y el tratamiento siempre los define un profesional de la salud.'],
        ['q' => '¿Qué tipos de archivo puedo subir?', 'a' => 'Informes en PDF (laboratorios, historiales, recetas), imágenes de tus estudios como radiografías, ecografías, tomografías y resonancias, y archivos DICOM (.dcm).'],
        ['q' => '¿Qué pasa con mis datos personales?', 'a' => 'Antes de enviar tu documento a la IA, ocultamos automáticamente datos como tu nombre y tu cédula, de modo que tu identidad no se comparte con el modelo de inteligencia artificial.'],
        ['q' => '¿Cuánto tarda el análisis?', 'a' => 'Normalmente unos segundos. Los estudios de imagen más pesados pueden tardar un poco más.'],
    ];
@endphp

    @if(session('error'))
        <div class="max-w-4xl mx-auto mt-4 px-6">
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-center text-sm text-red-700">
                {{ session('error') }}
            </div>
        </div>
    @endif

    {{-- ============ HERO ============ --}}
    <header class="max-w-7xl mx-auto px-6 pt-12 pb-16 md:pt-20 md:pb-24 grid lg:grid-cols-2 gap-14 items-center">
        <div class="space-y-6 mt-5">
            <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-emerald-700">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                IA generativa médica
            </div>

            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-tight text-slate-900">
                Interpreta tus exámenes médicos con inteligencia artificial en
                <span class="text-blue-600">segundos</span>
            </h1>

            <p class="max-w-xl text-lg leading-relaxed text-slate-600">
                Obtén un <span class="font-semibold text-blue-600">análisis técnico asistido por IA</span> para complementar la opinión de tu médico.
                Procesamos tus resultados al instante y te ayudamos a entenderlos antes de tu próxima consulta.
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
                   class="inline-flex items-center justify-center gap-3 rounded-xl bg-slate-900 px-8 py-4 text-center font-semibold text-white shadow-lg transition hover:bg-slate-800">
                    <span class="inline-flex items-center gap-2" x-show="!loading">
                        Analizar mis exámenes
                        <i class="fa-solid fa-arrow-right"></i>
                    </span>
                    <span class="inline-flex items-center gap-2" x-show="loading" x-cloak>
                        Iniciando el análisis...
                        <i class="fa-solid fa-spinner animate-spin"></i>
                    </span>
                </a>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-6 border-t border-slate-200 pt-6 text-sm text-slate-500">
                <span><i class="fa-solid fa-shield-halved mr-2 text-emerald-500"></i>Cumplimiento HIPAA y GDPR</span>
                <span><i class="fa-solid fa-user-lock mr-2 text-blue-500"></i>Anonimización estricta</span>
            </div>
        </div>

        {{-- Imagen con tarjetas flotantes --}}
        <div class="relative mb-8 lg:mb-0">
            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-3 sm:p-4 shadow-sm">
                <img src="{{ asset('images/examenes-medicos-con-ia.jpg') }}"
                     alt="Análisis de exámenes médicos con inteligencia artificial"
                     class="h-64 w-full rounded-2xl object-cover sm:h-80 lg:h-96">
            </div>

            <div class="absolute -top-4 right-4 sm:right-8 flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-lg">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-blue-600"><i class="fa-solid fa-bolt"></i></span>
                <div>
                    <p class="text-sm font-bold text-slate-900">Resultado en segundos</p>
                    <p class="text-xs text-slate-500">Sin esperar semanas</p>
                </div>
            </div>

            <div class="absolute -bottom-6 left-4 sm:left-8 flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-lg">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-600"><i class="fa-solid fa-eye-slash"></i></span>
                <div>
                    <p class="text-sm font-bold text-slate-900">Datos personales ocultos</p>
                    <p class="text-xs text-slate-500">Nombre y cédula, al instante</p>
                </div>
            </div>
        </div>
    </header>

    {{-- ============ QUÉ PUEDES ANALIZAR ============ --}}
    <section id="tipos" class="border-y border-slate-200 bg-slate-50 py-16 md:py-20">
        <div class="max-w-7xl mx-auto px-6">
            <div class="mx-auto mb-12 max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Qué puedes analizar</h2>
                <p class="mt-3 text-slate-500">Desde un hemograma hasta una resonancia: sube tu estudio y recibe una explicación clara.</p>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($exams as $exam)
                    <div class="flex items-start gap-4 rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-blue-500 hover:shadow-md">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-xl {{ $exam['tone'] }}">
                            <i class="fa-solid {{ $exam['icon'] }}"></i>
                        </span>
                        <div>
                            <h3 class="font-bold text-slate-900">{{ $exam['title'] }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $exam['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ CÓMO FUNCIONA ============ --}}
    <section class="max-w-7xl mx-auto px-6 py-16 md:py-20">
        <div class="mx-auto mb-12 max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Así de simple</h2>
            <p class="mt-3 text-slate-500">Tres pasos y tienes tu segunda opinión.</p>
        </div>

        <div class="grid gap-5 md:grid-cols-3">
            @foreach ($steps as $step)
                <div class="rounded-2xl border border-slate-200 bg-white p-6">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full text-lg font-bold {{ $step['tone'] }}">{{ $step['n'] }}</span>
                    <h3 class="mt-5 text-lg font-bold text-slate-900">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $step['text'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============ POR QUÉ USAR IA ============ --}}
    <section id="features" class="border-y border-slate-200 bg-slate-50 py-16 md:py-20">
        <div class="max-w-7xl mx-auto px-6">
            <div class="mx-auto mb-12 max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">¿Por qué usar IA para entender tus exámenes?</h2>
                <p class="mt-3 text-slate-500">La IA no reemplaza a tu médico: te da conocimiento para tomar mejores decisiones en tu próxima consulta.</p>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                @foreach ($reasons as $reason)
                    <div class="rounded-2xl border border-slate-200 bg-white p-8 transition hover:shadow-lg">
                        <span class="flex h-12 w-12 items-center justify-center rounded-xl text-xl {{ $reason['tone'] }}">
                            <i class="fa-solid {{ $reason['icon'] }}"></i>
                        </span>
                        <h3 class="mt-5 text-xl font-bold text-slate-900">{{ $reason['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $reason['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ PREGUNTAS FRECUENTES ============ --}}
    <section class="mx-auto max-w-3xl px-6 py-16 md:py-20" x-data="{ open: 1 }">
        <div class="mb-10 text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Preguntas frecuentes</h2>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-6">
            @foreach ($faqs as $faq)
                @php $n = $loop->iteration; @endphp
                <div class="{{ $loop->last ? '' : 'border-b border-slate-200' }}">
                    <button type="button"
                            @click="open = open === {{ $n }} ? null : {{ $n }}"
                            :aria-expanded="open === {{ $n }}"
                            class="flex w-full items-center justify-between gap-4 py-5 text-left">
                        <span class="font-semibold text-slate-900">{{ $faq['q'] }}</span>
                        <i class="fa-solid fa-chevron-down text-slate-400 transition"
                           :class="open === {{ $n }} ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open === {{ $n }}"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-cloak
                         class="pb-5 text-sm leading-relaxed text-slate-600">
                        {{ $faq['a'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============ CTA FINAL ============ --}}
    <section class="mx-auto max-w-5xl px-6 pb-20">
        <div class="rounded-3xl bg-slate-900 p-10 text-center text-white shadow-xl md:p-14">
            <h2 class="text-3xl font-bold tracking-tight md:text-4xl">Toma el control de tu salud hoy mismo</h2>
            <p class="mx-auto mt-4 max-w-xl text-base text-slate-300">
                No esperes semanas por una cita para entender un papel. Obtén una guía inteligente previa, de forma segura y rápida.
            </p>
            <div class="pt-8">
                <a href="{{ route('medical-analysis.upload') }}"
                   x-data="{ loading: false }"
                   @pageshow.window="loading = false"
                   @click="
                        if (loading) return;
                        loading = true;
                        if (typeof gtag === 'function') {
                            gtag('event', 'start_medical_analysis_upload', {
                                'action': 'begin_upload',
                                'source': 'final_cta',
                                'feature_type': 'lab_analysis'
                            });
                        }
                   "
                   :class="loading ? 'opacity-70 cursor-not-allowed pointer-events-none' : ''"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-8 py-4 font-bold text-slate-900 shadow-md transition hover:bg-slate-100">
                    <span class="inline-flex items-center gap-2" x-show="!loading">
                        Comenzar valoración con IA
                        <i class="fa-solid fa-chevron-right text-sm"></i>
                    </span>
                    <span class="inline-flex items-center gap-2" x-show="loading" x-cloak>
                        Cargando...
                        <i class="fa-solid fa-spinner animate-spin"></i>
                    </span>
                </a>
            </div>
            <p class="mx-auto mt-8 max-w-xl text-xs leading-relaxed text-slate-400">
                Este servicio es un apoyo informativo y no reemplaza el criterio ni el diagnóstico de un profesional de la salud.
            </p>
        </div>
    </section>
</x-guest-layout>