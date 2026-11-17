@once
    @vite('resources/js/nearby-doctors.js')
@endonce

<div x-data="nearbyDoctorsMap({ radius: {{ $radius }}, endpoint: @js($endpoint) })" class="mt-6">
        {{-- Disparador visual estilo Mapa / Geolocalización (Optimizado Mobile-First) --}}
    <button
        type="button"
        @click="openModal"
        {{ $attributes->merge([
            'class' => 'group relative w-full overflow-hidden flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 p-4 sm:px-5 sm:py-4 bg-white hover:bg-blue-50/40 border border-slate-200/90 hover:border-blue-300 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 text-left focus:outline-none focus:ring-2 focus:ring-blue-500/40'
        ]) }}
    >
        {{-- Fondo decorativo SVG que simula calles y zonas de un mapa --}}
        <div class="pointer-events-none absolute inset-y-0 right-0 w-3/4 sm:w-1/2 opacity-40 group-hover:opacity-75 group-hover:scale-105 transition-all duration-500">
            <svg class="h-full w-full text-blue-200" viewBox="0 0 240 100" fill="none" preserveAspectRatio="none">
                <path d="M-10 22 L250 48 M45 -10 L85 110 M145 -10 L115 110 M-10 78 L250 62 M195 -10 L215 110" stroke="currentColor" stroke-width="3" />
                <path d="M10 48 L130 34 M100 72 L205 95" stroke="currentColor" stroke-width="1.5" stroke-dasharray="4 4" />
                <rect x="65" y="34" width="28" height="20" rx="5" class="fill-emerald-100/90" />
                <rect x="158" y="16" width="32" height="22" rx="5" class="fill-blue-100/90" />
                <circle cx="135" cy="52" r="3.5" class="fill-red-400/70" />
                <circle cx="185" cy="68" r="3.5" class="fill-red-400/70" />
            </svg>
            <div class="absolute inset-0 bg-gradient-to-r from-white via-white/80 to-transparent"></div>
        </div>

        {{-- Contenedor Principal: Icono + Textos --}}
        <div class="relative z-10 flex items-start sm:items-center gap-3.5 flex-1 min-w-0">
            {{-- Icono Radar --}}
            <div class="relative flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 shrink-0 rounded-xl bg-blue-50 border border-blue-100 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300 mt-0.5 sm:mt-0 mr-2">
                <span class="animate-ping absolute inline-flex h-7 w-7 sm:h-8 sm:w-8 rounded-full bg-blue-400 opacity-25"></span>
                <svg class="relative w-5 h-5 sm:w-6 sm:h-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>

            {{-- Bloque de Textos --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between sm:justify-start gap-2">
                    <span class="text-[15px] sm:text-base font-bold text-slate-800 group-hover:text-blue-600 transition-colors leading-snug">
                        {{ $slot->isEmpty() ? 'Especialistas médicos cerca a mí' : $slot }}
                    </span>

                    {{-- Badge de Radio (En desktop va aquí junto al título; en móvil baja para dar aire) --}}
                    <span class="hidden sm:inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80 shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Radio {{ $radius }} km
                    </span>
                </div>

                {{-- Subtítulo con buen ancho en móvil --}}
                <p class="text-xs sm:text-sm text-slate-500 mt-1 leading-relaxed">
                    <template x-if="state === 'ready' && doctors.length > 0">
                        <span class="text-blue-600 font-medium" x-text="`${doctors.length} especialista(s) encontrados cerca de tu ubicación`"></span>
                    </template>
                    <template x-if="state !== 'ready' || doctors.length === 0">
                        <span>Explora consultorios disponibles en tu zona en el mapa</span>
                    </template>
                </p>

                {{-- Barra inferior exclusiva de Móvil: Badge + Acción clara --}}
                <div class="flex sm:hidden items-center justify-between gap-2 mt-3 pt-2.5 border-t border-slate-100">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Radio {{ $radius }} km
                    </span>

                    <span class="inline-flex items-center gap-1 text-xs font-bold text-blue-600">
                        <span x-show="state !== 'loading'">Ver en mapa</span>
                        <span x-show="state === 'loading'" x-cloak>Ubicando...</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </span>
                </div>
            </div>
        </div>

        {{-- Pill de Acción (Solo visible en Escritorio sm:flex) --}}
        <div class="hidden sm:flex relative z-10 shrink-0 items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-50 group-hover:bg-blue-600 text-slate-700 group-hover:text-slate-600 text-sm font-semibold border border-slate-200/90 group-hover:border-blue-600 shadow-2xs transition-all duration-200">
            <span x-show="state !== 'loading'">Explorar mapa</span>
            <span x-show="state === 'loading'" x-cloak>Ubicando...</span>

            <svg x-show="state !== 'loading'" class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <svg x-show="state === 'loading'" x-cloak class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>
    </button>


    {{-- Modal estilo Google Maps --}}
    <dialog
        x-ref="dialog"
        @close="closeAndCleanup"
        aria-labelledby="nearby-doctors-title"
        class="fixed inset-0 m-auto w-[calc(100%-2rem)] max-w-4xl max-h-[calc(100dvh-2rem)] p-0 rounded-2xl shadow-2xl border border-gray-200 backdrop:bg-gray-900/60 backdrop:backdrop-blur-sm overflow-hidden open:flex open:flex-col"
    >
        {{-- Cabecera --}}
        <header class="shrink-0 flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 bg-white">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    </svg>
                </div>
                <div>
                    <h2 id="nearby-doctors-title" class="text-base font-bold text-gray-900">Especialistas cerca de ti</h2>
                    <p class="text-md text-gray-500" x-text="statusMessage"></p>
                </div>
            </div>

            <button
                type="button"
                @click="$refs.dialog.close()"
                class="p-2 rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition-colors"
                aria-label="Cerrar"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </header>

        {{-- Contenedor Principal --}}
        <div class="relative bg-gray-100 flex-1 min-h-0 overflow-y-auto overscroll-contain">
            {{-- Estado: Cargando --}}
            <div x-show="state === 'loading'" x-cloak class="min-h-[320px] flex flex-col items-center justify-center p-6 bg-white">
                <svg class="animate-spin w-10 h-10 text-blue-600 mb-3" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-gray-600 text-sm font-medium" x-text="loadingMessage"></p>
            </div>

            {{-- Estado: Error --}}
            <div x-show="state === 'error'" x-cloak class="min-h-[320px] flex flex-col items-center justify-center bg-red-50 p-6 text-center">
                <svg class="w-12 h-12 text-red-500 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <p class="text-red-700 font-medium mb-4" x-text="errorMessage"></p>
                <button type="button" @click="$refs.dialog.close()" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">
                    Cerrar
                </button>
            </div>
            {{-- Estado: Sin Resultados --}}
            <div x-show="state === 'empty'" x-cloak class="min-h-[320px] flex flex-col items-center justify-center bg-white p-4 sm:p-6 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-gray-400">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                </div>

                <div class="w-full flex flex-col items-center text-center py-6 sm:py-12 px-4 sm:px-6 gap-4 bg-white rounded-3xl border border-slate-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">                    
                    <p class="text-md text-gray-500">
                        No encontramos especialistas disponibles en un radio de <span class="font-semibold text-gray-700" x-text="`${radius} km`"></span>.
                    </p>
                    <p class="text-md leading-normal">
                        Estamos listos para atenderte. No te desplaces, agenda una videoconsulta segura 24/7 con médicos certificados en nuestra plataforma.
                    </p>

                    <div class="flex flex-wrap justify-center gap-3">
                        <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-2.5 dark:bg-emerald-950/30 dark:border-emerald-900">
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/>
                            </svg>
                            <span class="text-md font-bold text-emerald-700 dark:text-emerald-400">Telemedicina inmediata disponible</span>
                        </div>
                        <div class="flex items-center gap-2 bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-2.5 dark:bg-indigo-950/30 dark:border-indigo-900">
                            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-md font-bold text-indigo-700 dark:text-indigo-400">Médicos generales listos ahora</span>
                        </div>
                    </div>                    
                    
                    <a href="{{ url('/search') }}?specialty=medicina-general" 
                    class="group w-full sm:w-auto max-w-max sm:max-w-none bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 shadow-md shadow-blue-600/15 flex items-center justify-center gap-2.5 text-base transform active:scale-[0.98]">
                        Consultar con Medicina General
                        <svg class="w-4 h-4 text-blue-200 group-hover:text-white group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>

            {{-- Estado: Mapa Estilo Google Maps --}}
            <div x-show="state === 'ready'" x-cloak class="relative w-full h-[60dvh] min-h-[280px] sm:h-[520px]">
                <div x-ref="mapContainer" class="w-full h-full z-0"></div>

                {{-- Tarjeta flotante superior izquierda --}}
                <div class="absolute top-4 left-4 z-[400] bg-white px-4 py-2.5 rounded-xl shadow-lg border border-gray-100 flex items-center gap-3 text-xs font-medium text-gray-700">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-600 ring-2 ring-blue-200"></span>
                        Tú
                    </span>
                    <span class="text-gray-300">|</span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-600"></span>
                        <span x-text="`${doctors.length} Especialista(s) (${radius} km)`"></span>
                    </span>
                </div>
            </div>
        </div>
    </dialog>
</div>
