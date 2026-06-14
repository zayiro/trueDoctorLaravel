<x-guest-layout>    
    <!-- Inyección exclusiva para el HEAD (SEO Estructurado Avanzado Híbrido) -->
    <x-slot:seo>
        <meta name="title" content="{{ $seoTitle }}">
        <meta name="description" content="{{ $seoDescription }}">
        <meta name="robots" content="{{ $metaRobots }}">
        @php
            $isClinicProfile = $profileType === 'clinic';
            
            // 🛡️ SCHEMA DINÁMICO MULTI-TENANT: Adapta los microdatos automáticamente según el rol del perfil
            $schemaData = [
                "@context" => "https://schema.org",
                "@type" => $isClinicProfile ? "MedicalBusiness" : "Physician",
                "name" => $isClinicProfile ? $partner->name : ($partner->gender === 'female' ? 'Doctora ' . $partner->user->name : 'Doctor ' . $partner->user->name),
                "image" => $partner->user->profile_photo_url ?? asset('images/default-clinic.png'),
                "medicalSpecialty" => $partner->specialties->first()?->name ?? 'Medicina General',
                "telephone" => $partner->phone ?? 'N/A',
                "url" => url()->current(),
                "description" => $partner->bio ? str(strip_tags($partner->bio))->limit(160, '...')->toString() : 'Servicios profesionales de salud dedicados a mantener y recuperar el bienestar humano.'
            ];

            if (isset($partner->reviews_count) && $partner->reviews_count > 0) {
                $schemaData["aggregateRating"] = [
                    "@type" => "AggregateRating",
                    "ratingValue" => (string) $partner->rating,
                    "reviewCount" => (string) $partner->reviews_count
                ];
            }
        @endphp

        <script type="application/ld+json">
            {!! json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>        
   </x-slot:seo>

   <style>
    /* 📅 REDUCCIÓN TIPOGRÁFICA Y ESCALA PREMIUM DE FLATPICKR */
    .flatpickr-calendar {
    font-family: inherit !important;
    font-size: 13px !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
    border-radius: 1.5rem !important;
    border: 1px solid #f1f5f9 !important;
    padding: 4px !important;
    
    /* 🛠️ AÑADE ESTO: Sincroniza el ancho del padre con tus días reducidos */
    width: 280px !important; /* 266px de los días + 8px de paddings internos */
    box-sizing: border-box !important;
}

    /* Reducción proporcional de la cabecera de meses y flechas */
    .flatpickr-months .flatpickr-month {
        height: 30px !important;
    }
    .flatpickr-current-month {
        font-size: 13px !important;
        font-weight: 800 !important;
    }

    /* Ajuste simétrico para los contenedores internos de los días (Evita que se corten los bordes) */
    .flatpickr-innerContainer {
        padding: 2px !important;
    }
    
    /* Reducción sutil de las celdas de los días individuales manteniendo la grilla flexible intacta */
    .flatpickr-day {
        font-size: 12px !important;
        height: 36px !important;
        line-height: 36px !important;
        max-width: 36px !important;
        margin: 1px !important;
    }

    .flatpickr-days, 
    .dayContainer {
        width: 266px !important;
        min-width: 266px !important;
        max-width: 266px !important;
    }

    /* 🎨 Tus estilos estéticos: Días habilitados en Verde suave OpenDoctor */
    .flatpickr-day.has-agenda-slot {
        background-color: #f0fdf4 !important; 
        color: #166534 !important; 
        font-weight: 800 !important;
        border-color: #bbf7d0 !important;
        border-radius: 0.75rem !important;
    }
    .flatpickr-day.has-agenda-slot:hover {
        background-color: #dcfce7 !important;
    }
    
    /* Día seleccionado en Azul Corporativo */
    .flatpickr-day.selected, .flatpickr-day.selected:hover {
        background-color: #4f46e5 !important;
        color: white !important;
        border-color: #4f46e5 !important;
        border-radius: 0.75rem !important;
    }

    /* 🔒 CAPA DE SEGURIDAD ESTÉTICA: Atenúa y bloquea los días del mes anterior y siguiente */
    .flatpickr-day.nextMonthDay, 
    .flatpickr-day.prevMonthDay,
    .flatpickr-day.nextMonthDay:hover,
    .flatpickr-day.prevMonthDay:hover {
        background-color: transparent !important;
        color: #cbd5e1 !important; /* Gris suave (text-slate-300) */
        font-weight: 400 !important;
        cursor: not-allowed !important; /* Cursor de prohibición */
        pointer-events: none !important; /* Anula por completo los clics en el navegador */
        border-color: transparent !important;
    }

    .flatpickr-weekdays {
    width: 266px !important;
    max-width: 266px !important;
    height: 28px !important;
}
.flatpickr-weekday {
    max-width: 38px !important; /* Sincronizado con el ancho total de tu .flatpickr-day (36px + margins) */
}
</style>
 
    {{-- 🔒 INICIALIZACIÓN HÍBRIDA MAESTRA: Envía de forma explícita el context ID de la clínica a JavaScript --}}
    <div class="mt-5 bg-gray-100 min-h-screen py-12" x-data="bookingSystem({{ $partner->id }}, '{{ $profileType }}', {{ $partner->settings->max_advance_days ?? 30 }}, {{ $fromClinicId ?? 'null' }})">    
        
        <!-- Overlay de Carga Atómico Global (Pantalla de Espera) -->
        <div id="loading-overlay" style="display:none;" class="fixed inset-0 bg-white/95 z-[9999] flex flex-col items-center justify-center backdrop-blur-sm">            
            <div class="flex flex-col items-center">
                <svg class="animate-spin h-16 w-16 text-indigo-600 mb-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-xl font-black text-slate-800 tracking-tight">Confirmando reservación médica...</p>
                <p id="getHourText" class="text-sm text-slate-400 font-medium hidden">Buscando los mejores espacios horarios libres para ti</p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- COLUMNA IZQUIERDA: Tarjeta Informativa del Socio Médico -->
                <div class="lg:col-span-1" x-data="{ open: window.innerWidth >= 1024 }">
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5 sticky top-10 space-y-5 dark:bg-gray-800 dark:border-gray-700">
                        
                        <div class="text-center relative">
                            <button @click="open = !open" type="button" class="absolute top-0 right-0 p-2 text-slate-400 hover:text-slate-600 lg:hidden focus:outline-none transition-transform duration-200" :class="open ? 'rotate-180' : ''" title="Alternar información del perfil">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </button>

                            <img src="{{ $partner->user->profile_photo_url ?? asset('images/default-clinic.png') }}" class="w-32 h-32 rounded-full mx-auto border-4 border-indigo-50 shadow-sm object-cover">
                            
                            <div class="font-black text-xs text-indigo-600 uppercase tracking-widest mt-4 dark:text-indigo-400">
                                @if($profileType === 'clinic') Centro Médico @else {{ $partner->gender === 'female' ? 'Doctora' : 'Doctor' }} @endif
                            </div>
                            <h1 class="text-2xl font-black text-slate-800 mt-0.5 tracking-tight dark:text-white">
                                @if($profileType === 'clinic') {{ $partner->name }} @else {{ ucfirst($partner->user->name) }} @endif
                            </h1>
                            <p class="text-slate-500 font-semibold text-sm mt-0.5 dark:text-gray-400">
                                {{ $partner->specialties->first()->name ?? 'Atención Médica' }}
                            </p>
                            
                            <div class="flex justify-center mt-2.5">
                                @include('partials.stars', ['rating' => $partner->rating])
                            </div>
                        </div>

                        <!-- Bloque Informativo Colapsable (Móviles / Desktop Abierto) -->
                        <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-5 lg:!block">
                            <div class="p-4 text-xs text-slate-600 bg-indigo-50/60 rounded-2xl text-center border border-indigo-100/30 leading-relaxed font-medium dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                                @if($profileType === 'clinic')
                                    Agende una cita presencial o virtual en minutos con nuestro personal de salud calificado en la sede que prefiera.
                                @else
                                    Agende una cita presencial o virtual en minutos con {{ $partner->gender === 'female' ? 'la doctora ' . $partner->user->name : 'el doctor ' . $partner->user->name }} en la sede que mejor se adapte a sus necesidades.
                                @endif
                            </div>
                            <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100/70 space-y-4 dark:bg-gray-700/50 dark:border-gray-600">    
                                <!-- Perfil Médico / Reseña Corta -->
                                <div class="space-y-1">
                                    <h4 class="font-bold text-[10px] uppercase text-slate-400 tracking-wider dark:text-gray-400">Sobre el especialista</h4>
                                    <div class="text-sm text-slate-600 leading-relaxed dark:text-gray-300">                                    
                                        @if($partner->bio)
                                            <span>{{ str(strip_tags($partner->bio))->limit(120, '...') }}</span>
                                        @else
                                            <span class="text-xs text-slate-400 italic dark:text-gray-500">Perfil profesional en proceso de actualización.</span>                                        
                                        @endif
                                    </div>
                                </div>

                                <!-- Especialidades Habilitadas -->
                                <div class="space-y-1 border-t border-slate-200/50 pt-3 dark:border-gray-700">
                                    <h4 class="font-bold text-[10px] uppercase text-slate-400 tracking-wider dark:text-gray-400">Especialidades Habilitadas</h4>
                                    <div class="text-sm text-slate-800 font-semibold dark:text-white">    
                                        @php
                                            $partnerSpecialties = $partner->specialties->isNotEmpty() ? $partner->specialties->pluck('name')->toArray() : [];
                                        @endphp
                                        {{ !empty($partnerSpecialties) ? implode(', ', $partnerSpecialties) : 'Medicina General' }}                                
                                    </div>
                                </div>
                                
                                <!-- Código Legal y Trayectoria -->
                                <div class="grid grid-cols-2 gap-4 border-t border-slate-200/50 pt-3 dark:border-gray-700">
                                    @if($profileType === 'doctor')
                                        <div class="space-y-0.5">
                                            <h4 class="font-bold text-[10px] uppercase text-slate-400 tracking-wider dark:text-gray-400">Licencia Médica</h4>
                                            <div class="text-sm font-semibold text-slate-800 dark:text-white">{{ $partner->medical_license ?? 'N/A' }}</div>
                                        </div>
                                    @else
                                        <div class="space-y-0.5">
                                            <h4 class="font-bold text-[10px] uppercase text-slate-400 tracking-wider dark:text-gray-400">Código REPS</h4>
                                            <div class="text-sm font-semibold text-slate-800 dark:text-white">{{ $partner->reps_code ?? 'N/A' }}</div>
                                        </div>
                                    @endif
                                    
                                    <div class="space-y-0.5">
                                        <h4 class="font-bold text-[10px] uppercase text-slate-400 tracking-wider dark:text-gray-400">Trayectoria</h4>
                                        <div class="text-sm font-semibold text-slate-800 dark:text-white">{{ $partner->experience_years ? $partner->experience_years . ' años' : 'N/A' }}</div>
                                    </div>
                                </div>

                                <!-- Idiomas de Atención (Exclusivo Doctores) -->
                                @if($profileType === 'doctor')
                                    <div class="space-y-1 border-t border-slate-200/50 pt-3 dark:border-gray-700">
                                        <h4 class="font-bold text-[10px] uppercase text-slate-400 tracking-wider dark:text-gray-400">Idiomas de atención</h4>
                                        <div class="text-sm font-medium text-slate-700 dark:text-gray-300">    
                                            @php
                                                $langNames = ['es' => 'Español', 'en' => 'Inglés', 'pt' => 'Portugués', 'fr' => 'Francés', 'de' => 'Alemán'];
                                                $rawLang = $partner->languages;
                                                $decodedLang = is_array($rawLang) ? $rawLang : (json_decode($rawLang, true) ?? []);
                                                $partnerLanguages = array_map(fn($code) => $langNames[$code] ?? strtoupper($code), $decodedLang);
                                            @endphp
                                            {{ !empty($partnerLanguages) ? implode(', ', $partnerLanguages) : 'Español' }}                                
                                        </div>
                                    </div>
                                @endif

                                <!-- Experiencias y Tratamientos -->
                                <div class="space-y-2 border-t border-slate-200/50 pt-3 dark:border-gray-700">
                                    <h4 class="font-bold text-[10px] uppercase text-slate-400 tracking-wider dark:text-gray-400">Es experto en</h4>
                                    <div class="flex flex-wrap gap-1.5 pt-0.5">
                                        @if($profileType === 'doctor' && isset($partner->expertises))
                                            @forelse($partner->expertises as $expertise)
                                                <span class="text-xs bg-white text-slate-700 px-2.5 py-1 rounded-lg border border-slate-200/70 font-medium shadow-2xs transition-colors hover:border-slate-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600" title="Síntomas: {{ $expertise->symptoms_keywords }}">
                                                    🔍 {{ $expertise->disease_name }}
                                                </span>
                                            @empty
                                                <span class="text-xs text-slate-400 italic dark:text-gray-500">Consulta general y preventiva.</span>
                                            @endforelse
                                        @else
                                            <span class="text-xs text-slate-400 italic dark:text-gray-500">Consulta institucional y especializada.</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- COLUMNA DERECHA: FLUJO TRANSACCIONAL DE TRES PASOS -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- ======================================================== -->
                    <!-- 🏢 1. SELECTOR DE SEDES DE ATENCIÓN (FÍSICA O VIRTUAL)    -->
                    <!-- ======================================================== -->
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 md:p-8 space-y-4 dark:bg-gray-800 dark:border-gray-700">
                        <div class="border-b border-slate-100 dark:border-gray-700 pb-3">
                            <h2 class="text-lg font-black text-slate-800 dark:text-white tracking-tight">1. Elige la Sede de Atención</h2>
                            <p class="text-sm text-slate-400 dark:text-gray-400">Selecciona el consultorio presencial o la opción de telemedicina.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @forelse($partner->addresses as $addr)
                                <label class="cursor-pointer block select-none">
                                    {{-- 🔒 VINCULACIÓN AL ALPINE: Selecciona la dirección y dispara fetchServicesLocal --}}
                                    <input type="radio" name="address_id" value="{{ $addr->id }}" 
                                           x-on:change="selectAddress({{ $addr->id }}, '{{ $addr->type }}')" 
                                           {{-- Pre-selección segura de la URL --}}
                                           {{ (isset($preSelectedAddress) && $preSelectedAddress->id == $addr->id) ? 'checked' : '' }}
                                           class="sr-only peer">
                                    
                                    <div class="p-5 bg-white border border-slate-200 rounded-2xl hover:bg-slate-50 peer-checked:bg-indigo-50/50 peer-checked:border-indigo-600 transition-all h-full flex flex-col justify-between shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:hover:bg-gray-600 dark:peer-checked:bg-indigo-950/30">
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="font-extrabold text-sm text-slate-800 dark:text-white truncate">
                                                    {{ $addr->name }}
                                                </span>
                                                
                                                {{-- Badge de Modalidad Estético con SVG Nativo --}}
                                                @if($addr->type === 'virtual')
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-purple-50 text-purple-700 border border-purple-100 dark:bg-purple-950/30 dark:text-purple-400 dark:border-purple-900">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://w3.org"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25z"></path></svg>
                                                        Virtual
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-blue-50 text-blue-700 border border-blue-100 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-900">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://w3.org"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 115 0z"></path></svg>
                                                        Presencial
                                                    </span>
                                                @endif
                                            </div>
                                            
                                            {{-- 🔒 CONTROL DE MODALIDAD: Oculta la ciudad y dirección si la sede es virtual --}}
                                            <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed dark:text-gray-400">
                                                @if($addr->type === 'virtual')
                                                    <span class="font-medium text-purple-600 dark:text-purple-400">Atención 100% en línea desde cualquier ubicación</span>
                                                @else
                                                    {{ $addr->address }} • <span class="font-semibold">{{ $addr->city->name ?? 'Ubicación' }}</span>
                                                @endif
                                            </p>

                                        </div>

                                        {{-- Micro-alerta si pertenece a una clínica --}}
                                        @if($addr->clinic_id)
                                            <div class="mt-3 pt-2 border-t border-slate-100 dark:border-gray-600 flex items-center gap-1 text-[10px] font-bold text-purple-600 dark:text-purple-400">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://w3.org"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"></path></svg>
                                                Sede Corporativa de la Institución
                                            </div>
                                        @endif
                                    </div>
                                </label>
                            @empty
                                <div class="col-span-1 sm:col-span-2 p-6 bg-slate-50 border border-dashed rounded-2xl text-center text-xs font-bold text-slate-400 dark:bg-gray-700/50 dark:border-gray-600">
                                    No hay consultorios médicos habilitados o disponibles en este momento.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- ========================================================================= -->
                    <!-- 🩺 2. SELECTOR DE SERVICIOS (Filtrado por Sede vía Alpine.js Unificado) -->
                    <!-- ========================================================================= -->
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 md:p-8 space-y-4 dark:bg-gray-800 dark:border-gray-700" 
                         x-show="selectedAddress !== null" 
                         x-init="@if(isset($preSelectedAddress) && $preSelectedAddress) $nextTick(() => { selectAddress({{ $preSelectedAddress->id }}, '{{ $preSelectedAddress->type }}') }); @endif" 
                         x-transition>
                        
                        <div class="border-b border-slate-100 dark:border-gray-700 pb-3">
                            <h2 class="text-lg font-black text-slate-800 dark:text-white tracking-tight">2. Elige el Servicio Médico</h2>
                            <p class="text-sm text-slate-400 dark:text-gray-400">Los valores y tiempos varían según la sede seleccionada.</p>
                        </div>

                        <div class="space-y-2" x-show="availableServices.length > 0">
                            <template x-for="service in availableServices" :key="service.id">
                                <label class="cursor-pointer block select-none">
                                    <input type="radio" name="service_id" :value="service.id" 
                                           {{-- 🛡️ CAPA INTERACTIVA: Extrae la duración dinámica ya sea del objeto plano o del pivote institucional --}}
                                           x-on:change="selectService(service.id, (service.pivot ? service.pivot.duration : service.duration))" 
                                           class="sr-only peer">
                                    
                                    <div class="p-4 bg-white border border-slate-200 rounded-2xl hover:bg-slate-50 peer-checked:bg-indigo-50/50 peer-checked:border-indigo-600 transition-all flex items-center justify-between gap-4 shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:hover:bg-gray-600 dark:peer-checked:bg-indigo-950/30">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-800 text-sm dark:text-white" x-text="service.name"></span>
                                            {{-- ⏱️ Mapeo Dinámico de la Duración Corporativa vs Particular --}}
                                            <span class="text-xs text-slate-400 mt-0.5 dark:text-gray-400" 
                                                  x-text="'⏱ Duración: ' + (service.pivot ? service.pivot.duration : service.duration) + ' min'">
                                            </span>
                                        </div>
                                        {{-- 💵 Mapeo Dinámico del Precio Corporativo de la Clínica vs Particular del Médico --}}
                                        <span class="text-base font-black text-green-600 dark:text-green-400" 
                                              x-text="'$' + new Intl.NumberFormat('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(service.pivot ? service.pivot.price : service.price)">
                                        </span>
                                    </div>
                                </label>
                            </template>
                        </div>

                        <!-- Estado Vacío Condicional del Catálogo de Servicios -->
                        <div class="p-6 bg-slate-50 border-2 border-dashed border-slate-200 rounded-2xl text-center flex flex-col items-center justify-center py-8 dark:bg-gray-700/50 dark:border-gray-600" 
                             x-show="availableServices.length === 0" 
                             x-transition>
                            <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 mb-3 border border-slate-200/50 shadow-inner dark:bg-gray-700 dark:border-gray-600">
                                <svg class="w-6 h-6 text-slate-400 dark:text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v3m0 0h.008v.008H12V21zm0-6h.008v.008H12V15zm0-6h.008v.008H12V9zm0-6h.008v.008H12V3zM3.22 8.22a.75.75 0 011.06 0L12 15.69l7.72-7.47a.75.75 0 111.06 1.06l-8.25 8a.75.75 0 01-1.06 0l-8.25-8a.75.75 0 010-1.06z"></path>
                                </svg>
                            </div>
                            <h4 class="text-slate-800 font-extrabold text-sm uppercase tracking-wider dark:text-white">Catálogo No Disponible</h4>
                            <p class="text-slate-500 text-xs mt-1 max-w-sm mx-auto leading-relaxed dark:text-gray-400">
                                @if(isset($fromClinicId) && $fromClinicId) 
                                    La institución médica 
                                @else 
                                    El especialista 
                                @endif 
                                    aún no ha cargado los servicios de salud ni las tarifas correspondientes para esta sede. Por favor, selecciona otra ubicación de la lista de arriba.
                            </p>
                        </div>
                    </div>

                    <!-- ========================================================================= -->
                    <!-- 🗓️ 3. CALENDARIO CON DÍAS DISPONIBLES EN VERDE (Flatpickr + Alpine.js)       -->
                    <!-- ========================================================================= -->
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 md:p-8 space-y-4 dark:bg-gray-800 dark:border-gray-700" 
                        x-show="selectedService !== null" 
                        x-transition>
                        
                        <div class="border-b border-slate-100 dark:border-gray-700 pb-3">
                            <h2 class="text-lg font-black text-slate-800 dark:text-white tracking-tight">3. Selecciona la Fecha y Hora</h2>
                            <p class="text-xs text-slate-400 dark:text-gray-400">Los días destacados en verde cuentan con agenda habilitada para el especialista.</p>
                        </div>

                        <!-- Inicializador de la Instancia en el DOM de Laravel 11 -->                         
                         <div 
                            x-data="{ isOpen: false }" 
                            class="relative z-10"
                         >
                            <!-- 🌫️ CAPA DE OFUSCACIÓN PREMIUM (Backdrop Overlay) -->
                            <div 
                                x-show="isOpen" 
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 backdrop-blur-none"
                                x-transition:enter-end="opacity-100 backdrop-blur-sm"
                                x-transition:leave="transition ease-in duration-200"
                                x-transition:leave-start="opacity-100 backdrop-blur-sm"
                                x-transition:leave-end="opacity-0 backdrop-blur-none"
                                class="fixed inset-0 bg-slate-900/20 z-40 pointer-events-none"
                                x-cloak
                            ></div>

                            <div class="relative transition-all duration-300" id="opendoctor-calendar-wrapper">                                
                                <!-- Contenedor Relativo para anclar el Icono Corporativo -->
                                <div class="relative flex items-center"  @click.stop>
                                    <!-- 🔒 Input Estilizado Premium (Flatpickr) -->
                                    <input type="text" x-ref="datepicker" x-model="selectedDate"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-4 pl-5 pr-12 text-base font-bold focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner text-slate-700 cursor-pointer dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-all">
                                    
                                    <!-- 📅 Icono Heroicons SVG Nativo: Calendar (Posicionado de forma absoluta a la derecha) -->
                                    <div class="absolute right-4 pointer-events-none text-indigo-600 dark:text-indigo-400">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z"></path>
                                        </svg>
                                    </div>

                                </div>

                            </div>
                        </div>

                        <!-- Grilla Dinámica de Bloques Horarios (Slots Libres) -->
                        <div class="pt-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-black uppercase text-slate-400 tracking-wider dark:text-gray-400">Horarios Disponibles</span>
                                <span class="text-xs font-bold text-indigo-600 animate-pulse italic dark:text-indigo-400" x-show="loadingSlots">Consultando disponibilidad...</span>
                            </div>

                            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2">
                                <template x-for="slot in availableSlots" :key="slot.time || slot.start_time || slot">
                                    <button type="button" x-on:click="confirmBooking(slot.time || slot.start_time || slot)" class="p-3 text-center bg-white border border-slate-200 rounded-xl font-bold text-xs text-slate-700 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition-all shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:bg-indigo-600">
                                        <!-- Extrae la propiedad de hora del objeto para que no pinte [object Object] -->
                                        <span x-text="slot.time || slot.time_human || slot.start_time || slot"></span>
                                    </button>
                                </template>
                            </div>


                            <div class="p-6 bg-slate-50 border border-dashed rounded-2xl text-center text-xs font-bold text-slate-400 dark:bg-gray-700/30 dark:border-gray-600" 
                                x-show="availableSlots.length === 0 && !loadingSlots && selectedDate">
                                No hay turnos de atención disponibles para la fecha seleccionada. Por favor, elige otro día resaltado.
                            </div>
                        </div>
                    </div>

                </div> <!-- Cierre .lg:col-span-2 -->
            </div> <!-- Cierre .grid -->
        </div> <!-- Cierre .max-w-7xl -->

        <!-- ======================================================== -->
        <!-- ARQUITECTURA REACTIVA DE PRODUCCIÓN: AGENDAMIENTO        -->
        <!-- ======================================================== -->
        <script>
            // 📅 Inicializador nativo global (100% invisible para el compilador de Livewire)
            document.addEventListener("DOMContentLoaded", function() {
                const element = document.querySelector('[x-ref="datepicker"]');
                const realEnabledDates = @json($enabledDates ?? []);
                
                if (element) {
                    flatpickr(element, {
                        locale: 'es',
                        dateFormat: 'Y-m-d',
                        minDate: 'today',
                        maxDate: new Date().fp_incr(60),
                        defaultDate: '{{ $nextAvailableDate }}',
                        showMonths: 1,
                        animate: true,
                        enable: [
                            function(date) {
                                const year = date.getFullYear();
                                const month = String(date.getMonth() + 1).padStart(2, '0');
                                const day = String(date.getDate()).padStart(2, '0');
                                return realEnabledDates.includes(`${year}-${month}-${day}`);
                            }
                        ],
                        onOpen: function() {
                            const overlay = document.querySelector('[x-show="isOpen"]');
                            if(overlay) overlay.style.opacity = '1';
                        },
                        onClose: function() {
                            const overlay = document.querySelector('[x-show="isOpen"]');
                            if(overlay) overlay.style.opacity = '0';
                        },
                        onDayCreate: function(dObj, dStr, fp, dayElem) {
                            if (dayElem.classList.contains('nextMonthDay') || dayElem.classList.contains('prevMonthDay')) {
                                return;
                            }
                            const year = dayElem.dateObj.getFullYear();
                            const month = String(dayElem.dateObj.getMonth() + 1).padStart(2, '0');
                            const day = String(dayElem.dateObj.getDate()).padStart(2, '0');
                            const dateString = `${year}-${month}-${day}`;

                            if (realEnabledDates.includes(dateString)) {
                                dayElem.classList.add('has-agenda-slot');
                            } else {
                                // 🔒 CAPA VISUAL DE INHABILITACIÓN: Fuerza el color gris y bloquea interacciones
                                dayElem.style.opacity = '0.3';
                                dayElem.style.backgroundColor = '#f8fafc'; // bg-slate-50
                                dayElem.style.color = '#94a3b8'; // text-slate-400
                                dayElem.style.cursor = 'not-allowed';
                                dayElem.style.pointerEvents = 'none';
                                dayElem.classList.remove('disabled'); // Evita conflictos con clases nativas
                            }
                        },
                        onChange: function(selectedDates, dateStr) {
                            element.value = dateStr;
                            element.dispatchEvent(new Event('input', { bubbles: true }));
                            
                            const alpineData = Alpine.$data(document.querySelector('[x-data^="bookingSystem"]'));
                            if (alpineData) {
                                alpineData.selectedDate = dateStr;
                                alpineData.fetchAvailableSlots();
                            }
                        }
                    });
                }
            });


            // 🔒 MÁQUINA DE ESTADOS RECONSTRUIDA EN ALPINE.JS
            document.addEventListener('alpine:init', () => {
                Alpine.data('bookingSystem', (partnerId, profileType, maxAdvanceDays, fromClinicUrl) => ({
                    selectedAddress: null,
                    addressType: null,
                    selectedService: null,
                    serviceDuration: null,
                    selectedDate: '{{ $nextAvailableDate }}',
                    availableServices: [],
                    availableSlots: [],
                    loadingSlots: false,
                    fromClinicId: fromClinicUrl || '{{ $fromClinicId ?? "" }}',

                    addressesServicesPool: {!! json_encode(($partner->addresses ?? collect())->map(function($addr) {
                        return [
                            'id' => $addr->id,
                            'services' => ($addr->services ?? collect())->map(function($srv) {
                                return [
                                    'id' => $srv->id,
                                    'name' => $srv->name,
                                    'duration' => $srv->pivot->duration ?? $srv->duration,
                                    'price' => $srv->pivot->price ?? $srv->price
                                ];
                            })->values()->toArray()
                        ];
                    })->toArray()) !!},

                    init() {
                        this.$watch('selectedDate', (value) => {
                            if (value && this.selectedService) {
                                this.fetchAvailableSlots();
                            }
                        });
                    },

                    selectAddress(id, type) {
                        this.selectedAddress = id;
                        this.addressType = type;
                        this.selectedService = null;
                        this.availableSlots = [];
                        this.fetchServicesLocal();
                    },

                    selectService(id, duration) {
                        this.selectedService = id;
                        this.serviceDuration = duration;
                        this.fetchAvailableSlots();
                    },

                    fetchServicesLocal() {
                        const matchedAddress = this.addressesServicesPool.find(addr => addr.id === this.selectedAddress);
                        this.availableServices = matchedAddress ? matchedAddress.services : [];
                    },

                    async fetchAvailableSlots() {
                        if (!this.selectedAddress || !this.selectedService || !this.selectedDate) return;
                        this.loadingSlots = true;
                        this.availableSlots = [];

                        try {
                            const searchParams = new URLSearchParams({
                                date: this.selectedDate,
                                duration: this.serviceDuration,
                                address_id: this.selectedAddress,
                                is_virtual: this.addressType === 'virtual' ? 'true' : 'false'
                            });

                            if (profileType === 'clinic') {
                                searchParams.append('clinic_id', partnerId);
                            } else {
                                searchParams.append('doctor_id', partnerId);
                                if (this.fromClinicId) {
                                    searchParams.append('clinic_id', this.fromClinicId);
                                }
                            }

                            const url = `/slots?${searchParams.toString()}`;
                            const res = await fetch(url);
                            
                            if (!res.ok) {
                                throw new Error(`HTTP status: ${res.status}`);
                            }

                            this.availableSlots = await res.json();
                        } catch (error) {
                            console.error("Error cargando slots:", error);
                        } finally {
                            this.loadingSlots = false;
                        }
                    },

                    async confirmBooking(time) {
                        document.getElementById('loading-overlay').style.display = 'flex';
                        try {
                            const response = await fetch("{{ route('appointments.step-two') }}", {
                                method: "POST",
                                headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                                body: JSON.stringify({
                                    service_id: this.selectedService,
                                    address_id: this.selectedAddress,
                                    date: this.selectedDate,
                                    hour: time,
                                    clinic_id: this.fromClinicId ? this.fromClinicId : null
                                })
                            });
                            const res = await response.json();
                            if (res.status) {
                                window.location.href = "{{ route('appointments.patient') }}";
                            } else {
                                document.getElementById('loading-overlay').style.display = 'none';
                                alert(res.message);
                            }
                        } catch (error) {
                            document.getElementById('loading-overlay').style.display = 'none';
                            console.error("Fallo crítico:", error);
                        }
                    }
                }));
            });
        </script>

    </div> <!-- Cierre del div del fondo .bg-gray-100 -->
</x-guest-layout>
