@once
    @vite('resources/js/nearby-doctors.js')
@endonce

<div x-data="nearbyDoctorsMap({ radius: {{ $radius }}, endpoint: @js($endpoint) })">
    {{-- Botón disparador --}}
    <button
        type="button"
        @click="openModal"
        {{ $attributes->merge([
            'class' => 'inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2'
        ]) }}
    >
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <span>{{ $slot->isEmpty() ? 'Especialistas médicos cerca a mí' : $slot }}</span>
    </button>

    {{-- Modal estilo Google Maps --}}
    <dialog
        x-ref="dialog"
        @close="closeAndCleanup"
        class="w-full max-w-4xl p-0 rounded-2xl shadow-2xl border border-gray-200 backdrop:bg-gray-900/60 backdrop:backdrop-blur-sm overflow-hidden"
    >
        {{-- Cabecera --}}
        <header class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-white">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900">Especialistas cerca de ti</h2>
                    <p class="text-xs text-gray-500" x-text="statusMessage"></p>
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
        <div class="relative bg-gray-100 h-[520px]">
            {{-- Estado: Cargando --}}
            <div x-show="state === 'loading'" x-cloak class="h-full flex flex-col items-center justify-center p-6 bg-white">
                <svg class="animate-spin w-10 h-10 text-blue-600 mb-3" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-gray-600 text-sm font-medium" x-text="loadingMessage"></p>
            </div>

            {{-- Estado: Error --}}
            <div x-show="state === 'error'" x-cloak class="h-full flex flex-col items-center justify-center bg-red-50 p-6 text-center">
                <svg class="w-12 h-12 text-red-500 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <p class="text-red-700 font-medium mb-4" x-text="errorMessage"></p>
                <button type="button" @click="$refs.dialog.close()" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">
                    Cerrar
                </button>
            </div>

            {{-- Estado: Sin Resultados --}}
            <div x-show="state === 'empty'" x-cloak class="h-full flex flex-col items-center justify-center bg-white p-6 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-gray-400">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                </div>
                

                <div class="flex flex-col items-center text-center py-12 px-6 gap-4 bg-white rounded-3xl border border-slate-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">                    
                    <p class="text-md text-gray-500">
                        No encontramos especialistas disponibles en un radio de <span class="font-semibold text-gray-700" x-text="`${radius} km`"></span>.
                    </p>

                    <p class="text-md font-bold text-blue-900">¡Agenda tu Cita Médica Virtual!</p>
                    <p class="text-md leading-normal">
                        No necesitas desplazarte. Puedes consultar de forma segura 24/7 con médicos certificados mediante nuestra plataforma integrada de telemedicina.
                    </p>

                    <div class="flex flex-wrap justify-center gap-3">
                        <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-2.5 dark:bg-emerald-950/30 dark:border-emerald-900">
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/>
                            </svg>
                            <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400">Telemedicina inmediata disponible</span>
                        </div>
                        <div class="flex items-center gap-2 bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-2.5 dark:bg-indigo-950/30 dark:border-indigo-900">
                            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-xs font-bold text-indigo-700 dark:text-indigo-400">Médicos generales listos ahora</span>
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
            <div x-show="state === 'ready'" x-cloak class="relative w-full h-full">
                <div x-ref="mapContainer" class="w-full h-full z-0"></div>

                {{-- Tarjeta flotante superior izquierda (Estilo Google Maps) --}}
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

