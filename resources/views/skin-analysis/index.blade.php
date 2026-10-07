<x-guest-layout 
    :meta-title-medical-analysis="$meta_title_medicalAnalysis" 
    :meta-description-medical-analysis="$meta_description_medicalAnalysis"
>
    @if(session('error'))
        <div class="max-w-4xl mx-auto my-4 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-sm text-red-400 text-center">
            {{ session('error') }}
        </div>
    @endif

    @php
        // Para usar fotos reales: pon la ruta en 'image' (ej. 'images/skin/lunares.jpg' dentro de /public).
        // Mientras sea null se muestra un degradado de color.
        $concerns = [
            ['tag' => 'Lunares', 'title' => 'Lunares y manchas', 'subtitle' => 'Forma y bordes',
            'desc' => 'Detecta a tiempo cambios de color, forma o tamaño para consultar con un especialista.',
            'image' => 'images/skin-analysis/carousel/1.webp', 'c1' => '#fcd34d', 'c2' => '#fda4af', 'emoji' => '🔍'],
            ['tag' => 'Acné', 'title' => 'Acné y brotes', 'subtitle' => 'Inflamación',
            'desc' => 'Identifica el tipo de lesión y recibe cuidados generales mientras agendas tu cita.',
            'image' => 'images/skin-analysis/carousel/2.webp', 'c1' => '#fda4af', 'c2' => '#fed7aa', 'emoji' => '✨'],
            ['tag' => 'Erupciones', 'title' => 'Erupciones y picazón', 'subtitle' => 'Enrojecimiento',
            'desc' => 'Describe lo que se observa en tu piel y entiende qué señales requieren atención pronta.',
            'image' => 'images/skin-analysis/carousel/3.webp', 'c1' => '#fca5a5', 'c2' => '#fbcfe8', 'emoji' => '🩹'],
            ['tag' => 'Textura', 'title' => 'Descamación y hongos', 'subtitle' => 'Superficie de la piel',
            'desc' => 'Analiza placas, resequedad y descamación para orientarte sobre el siguiente paso.',
            'image' => 'images/skin-analysis/carousel/4.webp', 'c1' => '#7dd3fc', 'c2' => '#a7f3d0', 'emoji' => '🧴'],
            ['tag' => 'Pigmentación', 'title' => 'Manchas por sol', 'subtitle' => 'Uniformidad del tono',
            'desc' => 'Sigue manchas oscuras y marcas para saber cuándo conviene una valoración médica.',
            'image' => 'images/skin-analysis/carousel/5.webp', 'c1' => '#fde047', 'c2' => '#fed7aa', 'emoji' => '☀️'],
        ];

        $steps = [
            ['n' => '1', 'title' => 'Sube tu foto', 'text' => 'Nuestra IA verifica al instante que la imagen tenga buena luz y nitidez.'],
            ['n' => '2', 'title' => 'Cuéntanos tu caso', 'text' => 'Añade la ubicación y los síntomas para un informe más completo.'],
            ['n' => '3', 'title' => 'Recibe tu informe', 'text' => 'Te llega por correo con posibles hallazgos, señales de alarma y recomendaciones.'],
        ];
    @endphp

    {{-- Estilos propios del carrusel: no dependen de que Tailwind se recompile --}}
    <style>
        .od-serif { font-family: Georgia, 'Times New Roman', serif; }
        .od-wrap { border-radius: 2rem; border: 1px solid rgba(255,255,255,.8); background: rgba(255,255,255,.8); padding: 1.5rem; box-shadow: 0 20px 40px -12px rgba(168,85,247,.18); }
        .od-panel { display: flex; flex-direction: column; gap: 2rem; }
        .od-side h2 { font-size: 2rem; line-height: 1.15; font-weight: 600; color: #0f172a; margin: 0; }
        .od-arrows { display: flex; gap: .75rem; margin-top: 1.5rem; }
        .od-arrow { width: 2.75rem; height: 2.75rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: opacity .2s; }
        .od-arrow:hover { opacity: .8; }
        .od-arrow-prev { background: #fff; border: 1px solid #cbd5e1; color: #1e293b; }
        .od-arrow-next { background: #0f172a; border: 1px solid #0f172a; color: #fff; }
        .od-track { display: flex; gap: 1.25rem; flex: 1; min-width: 0; overflow-x: auto; scroll-snap-type: x mandatory; scroll-behavior: smooth; padding-bottom: .5rem; scrollbar-width: none; }
        .od-track::-webkit-scrollbar { display: none; }
        .od-item { flex: 0 0 280px; scroll-snap-align: start; }
        .od-card { position: relative; height: 240px; border-radius: 1.5rem; overflow: hidden; }
        .od-card img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .od-shade { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,.6), rgba(0,0,0,.05) 60%, transparent); }
        .od-emoji { position: absolute; right: 1.25rem; top: 1.25rem; font-size: 3.75rem; opacity: .4; }
        .od-tag { position: absolute; left: 1rem; top: 1rem; background: rgba(255,255,255,.92); color: #1e293b; font-size: .75rem; font-weight: 600; padding: .25rem .75rem; border-radius: 9999px; }
        .od-caption { position: absolute; left: 1rem; right: 1rem; bottom: 1rem; color: #fff; }
        .od-caption h3 { font-size: 1.125rem; font-weight: 700; line-height: 1.2; margin: 0; }
        .od-caption p { font-size: .75rem; opacity: .85; margin: 0; }
        .od-desc { margin: .75rem 0 0; padding: 0 .25rem; font-size: .875rem; line-height: 1.5; color: #64748b; }
        @media (min-width: 768px) {
            .od-wrap { padding: 2rem; }
            .od-side h2 { font-size: 2.25rem; }
        }
        @media (min-width: 1024px) {
            .od-panel { flex-direction: row; align-items: center; }
            .od-side { width: 15rem; flex-shrink: 0; }
        }
    </style>

    <div class="bg-gradient-to-b from-white via-purple-50 to-slate-50">

        {{-- HERO --}}
        <section class="mx-auto max-w-4xl px-4 pt-16 pb-10 text-center md:pt-24">            
            <h1 class="od-serif mt-5 text-4xl font-semibold leading-tight tracking-tight text-slate-900 md:text-6xl">
                Lo que ves en tu piel merece más que adivinar.
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-base text-slate-600 md:text-lg">
                Sube una foto y recibe un informe preliminar en minutos, con posibles hallazgos, señales de alarma y el especialista al que conviene acudir.
            </p>
            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('skin-analysis.upload') }}"
                class="inline-flex items-center justify-center rounded-full bg-slate-900 px-7 py-3.5 text-sm font-semibold text-white shadow-lg transition hover:bg-slate-700">
                    Analizar mi piel ahora
                </a>
                <span class="text-sm text-slate-500">· Resultado por correo</span>
            </div>
        </section>

        {{-- CARRUSEL DE CASOS --}}
        <section class="mx-auto max-w-6xl px-4 pb-16">
            <div x-data class="od-wrap">
                <div class="od-panel">

                    <div class="od-side">
                        <h2 class="od-serif">Mira el problema con claridad antes de elegir una solución.</h2>
                        <div class="od-arrows">
                            <button type="button" aria-label="Anterior" class="od-arrow od-arrow-prev"
                                    @click="$refs.track.scrollBy({ left: -320, behavior: 'smooth' })">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <button type="button" aria-label="Siguiente" class="od-arrow od-arrow-next"
                                    @click="$refs.track.scrollBy({ left: 320, behavior: 'smooth' })">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>

                    <div x-ref="track" class="od-track">
                        @foreach ($concerns as $c)
                            <article class="od-item">
                                <div class="od-card" style="background: linear-gradient(135deg, {{ $c['c1'] }}, {{ $c['c2'] }});">
                                    @if ($c['image'])
                                        <img src="{{ asset($c['image']) }}" alt="{{ $c['title'] }}" loading="lazy">
                                    @else
                                        <span class="od-emoji">{{ $c['emoji'] }}</span>
                                    @endif
                                    <div class="od-shade"></div>
                                    <span class="od-tag">{{ $c['tag'] }}</span>
                                    <div class="od-caption">
                                        <h3>{{ $c['title'] }}</h3>
                                        <p>{{ $c['subtitle'] }}</p>
                                    </div>
                                </div>
                                <p class="od-desc">{{ $c['desc'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- CÓMO FUNCIONA --}}
        <section class="mx-auto max-w-5xl px-4 pb-16 mt-4">
            <h2 class="od-serif text-center text-3xl font-semibold text-slate-900 md:text-4xl">Así de simple</h2>
            <div class="mt-10 grid gap-5 md:grid-cols-3 mb-3 gap-3">
                @foreach ($steps as $s)
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-purple-100 text-sm font-bold text-purple-700">{{ $s['n'] }}</span>
                        <h3 class="mt-4 text-lg font-bold text-slate-900">{{ $s['title'] }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $s['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- CTA FINAL --}}
        <section class="mx-auto max-w-4xl px-4 pb-20 mt-5">
            <div class="rounded-3xl bg-slate-900 px-6 py-12 text-center text-white md:px-12">
                <h2 class="od-serif text-3xl font-semibold md:text-4xl">Empieza con una foto.</h2>
                <p class="mx-auto mt-3 max-w-xl text-sm text-slate-300">
                    Tu imagen se guarda de forma privada y solo se usa para generar tu informe.
                </p>
                <a href="{{ route('skin-analysis.upload') }}"
                class="mt-8 inline-flex items-center justify-center rounded-full bg-white px-7 py-3.5 text-sm font-semibold text-slate-900 transition hover:bg-purple-100">
                    Subir mi fotografía
                </a>
            </div>
            <p class="mx-auto mt-6 max-w-2xl text-center text-xs leading-relaxed text-slate-500">
                Este análisis es orientativo, no constituye un diagnóstico y no reemplaza la valoración de un profesional de la salud. Si notas sangrado, crecimiento rápido o dolor intenso, consulta a un médico cuanto antes.
            </p>
        </section>
    </div>
</x-guest-layout>