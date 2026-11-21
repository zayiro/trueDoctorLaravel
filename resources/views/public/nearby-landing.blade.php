@php
    $initial = [
        'cityKey' => $cityKey,
        'mode'    => $mode,
        'center'  => ['lat' => (float) $center['lat'], 'lng' => (float) $center['lng']],
        'radius'  => (int) $radius,
        'doctors' => $doctors,
    ];
    $citiesForJs = collect($cities)->map(fn ($c) => [
        'name' => $c['name'],
        'lat'  => (float) $c['lat'],
        'lng'  => (float) $c['lng'],
    ])->all();
@endphp

<x-guest-layout
    :meta_title="($mode === 'exact' ? 'Doctores cerca de mí' : 'Doctores cerca de mí en ' . $placeLabel) . ' | Agenda tu cita médica | OpenDoctor'"
    meta_description="Encuentra doctores y consultorios cerca de ti en el mapa. Mira el próximo turno disponible y agenda tu cita médica en línea con OpenDoctor."
    keywords="doctores cerca de mí, médicos cerca de mí, consultorios cerca de mí, especialistas cerca de mí, citas médicas, agendar cita médica"
>
    {{-- Esta página no pide la ubicación al abrir: solo cuando el visitante toca el botón. --}}
    <script>window.OD_SKIP_AUTO_GEO = true;</script>
    @vite('resources/js/nearby-landing.js')

    <div
        x-data="nearbyLanding({ endpoint: @js($endpoint), cities: @js($citiesForJs), initial: @js($initial) })"
        data-mode="{{ $mode }}"
        class="bg-white"
    >
        {{-- Encabezado y controles --}}
        <section class="bg-white pt-20 lg:pt-24 pb-6 border-b border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80 mb-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Agenda en línea, sin filas
                </span>

                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 tracking-tight">
                    <span x-text="heading">{{ $mode === 'exact' ? 'Doctores cerca de mí' : 'Doctores cerca de mí en ' . $placeLabel }}</span>
                </h1>
                <p class="mt-3 text-base sm:text-lg text-slate-600 max-w-3xl">
                    Mira en el mapa los consultorios con agenda disponible, compara el próximo turno y reserva tu cita médica en línea.
                </p>

                <div class="mt-5 flex flex-col sm:flex-row gap-3">
                    <div class="sm:w-72">
                        <label for="landing-city" class="sr-only">Ciudad</label>
                        <select
                            id="landing-city"
                            x-model="cityKey"
                            @change="selectCity($event.target.value)"
                            class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-3 pr-9 text-sm font-medium text-slate-800 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30"
                        >
                            <option value="" disabled @selected($mode === 'exact')>Mi ubicación actual</option>
                            @foreach ($cities as $key => $c)
                                <option value="{{ $key }}" @selected($key === $cityKey)>{{ $c['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button
                        type="button"
                        @click="useMyLocation()"
                        :disabled="locating"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-60 px-5 py-3 text-sm font-semibold !text-white shadow-sm transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span x-text="locating ? 'Ubicándote…' : 'Usar mi ubicación exacta'">Usar mi ubicación exacta</span>
                    </button>
                </div>

                <p x-show="geoMessage" x-cloak x-text="geoMessage" class="mt-3 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2"></p>
            </div>
        </section>

        {{-- Mapa + lista --}}
        <section class="bg-slate-50 py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid lg:grid-cols-5 gap-6">

                {{-- Mapa (arriba en celular, a la derecha en escritorio) --}}
                <div class="lg:col-span-3 lg:order-2" x-ref="mapWrap">
                    <div class="lg:sticky lg:top-24">
                        <div class="relative rounded-2xl overflow-hidden border border-slate-200 shadow-sm bg-slate-100">
                            <div x-ref="map" class="w-full h-[45dvh] min-h-[280px] lg:h-[70vh] z-0"></div>
                            <div x-show="loading" x-cloak class="absolute top-3 left-3 z-[400] bg-white px-3 py-1.5 rounded-lg shadow text-xs font-medium text-slate-700">
                                Buscando especialistas…
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Lista --}}
                <div class="lg:col-span-2 lg:order-1">

                    {{-- Versión del servidor (la ven los buscadores y se muestra mientras carga el JS) --}}
                    <div x-show="!hydrated">
                        <p class="text-sm font-semibold text-slate-700 mb-3">
                            {{ count($doctors) }} especialista(s) cerca de {{ $placeLabel }}
                        </p>
                        <div class="space-y-3">
                            @forelse ($doctors as $d)
                                <article class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                                    <h3 class="font-bold text-slate-900 text-base leading-snug">{{ $d['name'] }}</h3>
                                    <p class="text-sm text-slate-500 mt-0.5">{{ $d['address'] }}</p>
                                    <p class="text-xs text-slate-500 mt-1">{{ $mode === 'exact' ? 'A ' . $d['distance'] . ' km de ti' : 'A ' . $d['distance'] . ' km del centro de ' . $placeLabel }}</p>
                                    @if (!empty($d['nextAppointment']))
                                        <p class="mt-3 text-xs bg-emerald-50 border border-emerald-100 rounded-md px-2.5 py-1.5 text-gray-600">
                                            Próximo turno disponible: <strong class="text-emerald-700">{{ $d['nextAppointment'] }}</strong>
                                        </p>
                                    @endif
                                    <a href="{{ $d['profileUrl'] }}" class="mt-3 block w-full text-center rounded-lg bg-blue-600 hover:bg-blue-700 px-3 py-2 text-sm font-semibold !text-white">Agendar Cita</a>
                                </article>
                            @empty
                                <div class="bg-white rounded-2xl border border-slate-200 p-5 text-sm text-slate-600">
                                    Por ahora no hay consultorios con agenda cerca de {{ $placeLabel }}. Puedes agendar una videoconsulta con médicos certificados.
                                    <a href="{{ url('/search') }}?specialty=medicina-general" class="mt-3 block w-full text-center rounded-lg bg-blue-600 hover:bg-blue-700 px-3 py-2 text-sm font-semibold !text-white">Consultar con Medicina General</a>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Versión dinámica (Alpine) --}}
                    <div x-show="hydrated" x-cloak>
                        <p class="text-sm font-semibold text-slate-700 mb-3">
                            <span x-text="doctors.length">{{ count($doctors) }}</span> especialista(s) cerca de <span x-text="placeLabel">{{ $placeLabel }}</span>
                        </p>

                        <div x-show="errorMessage" x-cloak class="mb-3 text-sm text-red-800 bg-red-50 border border-red-200 rounded-lg px-3 py-2" x-text="errorMessage"></div>

                        <div class="space-y-3 lg:max-h-[70vh] lg:overflow-y-auto lg:pr-1">
                            <template x-for="d in doctors" :key="d.key">
                                <article class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h3 class="font-bold text-slate-900 text-base leading-snug" x-text="d.name"></h3>
                                            <p class="text-sm text-slate-500 mt-0.5" x-text="d.address"></p>
                                            <p class="text-xs text-slate-500 mt-1" x-text="distanceLabel(d)"></p>
                                        </div>
                                        <button type="button" @click="focusDoctor(d)" class="shrink-0 text-xs font-semibold text-blue-600 hover:text-blue-800 underline underline-offset-2">Ver en el mapa</button>
                                    </div>
                                    <template x-if="d.nextAppointment">
                                        <p class="mt-3 text-xs bg-emerald-50 border border-emerald-100 rounded-md px-2.5 py-1.5 text-gray-600">
                                            Próximo turno disponible: <strong class="text-emerald-700" x-text="d.nextAppointment"></strong>
                                        </p>
                                    </template>
                                    <a :href="d.profileUrl" class="mt-3 block w-full text-center rounded-lg bg-blue-600 hover:bg-blue-700 px-3 py-2 text-sm font-semibold !text-white">Agendar Cita</a>
                                </article>
                            </template>

                            {{-- Sin resultados --}}
                            <div x-show="!loading && !errorMessage && doctors.length === 0" x-cloak class="bg-white rounded-2xl border border-slate-200 p-5 text-sm text-slate-600">
                                <p>
                                    Por ahora no hay consultorios con agenda cerca de <span class="font-semibold" x-text="placeLabel"></span>
                                    en un radio de <span class="font-semibold" x-text="radius + ' km'"></span>.
                                </p>
                                <p class="mt-2">Estamos listos para atenderte: agenda una videoconsulta segura con médicos certificados.</p>
                                <button type="button" x-show="radius < 100" @click="widenSearch()" class="mt-3 w-full rounded-lg border border-slate-300 bg-white hover:bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700">Ampliar la búsqueda a 100 km</button>
                                <a href="{{ url('/search') }}?specialty=medicina-general" class="mt-3 block w-full text-center rounded-lg bg-blue-600 hover:bg-blue-700 px-3 py-2 text-sm font-semibold !text-white">Consultar con Medicina General</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- Texto para buscadores y para quien quiere saber más --}}
    <section class="bg-white py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-slate-600">
            <h2 class="text-2xl font-bold text-slate-900">Encuentra doctores y consultorios cerca de ti</h2>
            <p class="mt-3 leading-relaxed">
                OpenDoctor te muestra en un mapa los médicos y consultorios cercanos a tu ciudad o a tu ubicación, con el próximo turno disponible de cada uno.
                Elige el que mejor te quede, revisa su perfil y agenda tu cita médica en línea en pocos pasos.
            </p>

            <h3 class="mt-8 text-lg font-bold text-slate-900">¿Cómo funciona?</h3>
            <ol class="mt-3 space-y-2 list-decimal list-inside leading-relaxed">
                <li>Elige tu ciudad o usa tu ubicación exacta para ver los especialistas más cercanos.</li>
                <li>Compara el próximo turno disponible y toca “Agendar Cita”.</li>
                <li>Confirma tu cita y recibe los detalles en tu correo.</li>
            </ol>

            <h3 class="mt-8 text-lg font-bold text-slate-900">Busca en otras ciudades</h3>
            <p class="mt-3 flex flex-wrap gap-x-4 gap-y-2">
                @foreach ($cities as $key => $c)
                    <a href="{{ route('doctors.landing', ['ciudad' => $key]) }}" class="text-blue-600 hover:text-blue-800 underline underline-offset-2">Doctores cerca de mí en {{ $c['name'] }}</a>
                @endforeach
            </p>

            <p class="mt-8 leading-relaxed">
                ¿Prefieres no desplazarte? También puedes
                <a href="{{ url('/search') }}?specialty=medicina-general" class="text-blue-600 hover:text-blue-800 underline underline-offset-2">agendar una videoconsulta de Medicina General</a>
                desde donde estés.
            </p>
        </div>
    </section>
</x-guest-layout>
