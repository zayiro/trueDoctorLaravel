<x-guest-layout>    
    <!-- Inyección exclusiva para el HEAD (SEO Estructurado Avanzado Híbrido) -->
    <x-slot:seo>
        <meta name="title" content="{{ $seoTitle }}">
        <meta name="description" content="{{ $seoDescription }}">
        <meta name="robots" content="{{ $metaRobots }}">
        @php
            $isClinicProfile = $profileType === 'clinic';
            
            // Construimos el mapa relacional plano adaptado por rol comercial en el SaaS
            $schemaData = [
                "@context" => "https://schema.org",
                "@type" => $isClinicProfile ? "MedicalBusiness" : "Physician",
                "name" => $isClinicProfile ? $partner->name : ($partner->gender === 'female' ? 'Doctora ' . $partner->user->name : 'Doctor ' . $partner->user->name),
                "image" => $isClinicProfile ? ($partner->user->profile_photo_url ?? asset('images/default-clinic.png')) : $partner->user->profile_photo_url,
                "medicalSpecialty" => $partner->specialties->first()?->name ?? 'Medicina General',
                "telephone" => $partner->phone ?? 'N/A',
                "url" => url()->current(),
                "description" => $partner->bio ? str(strip_tags($partner->bio))->limit(160, '...')->toString() : 'Servicios profesionales de salud dedicados a mantener y recuperar el bienestar humano.'
            ];

            // Acoplamos las calificaciones de reputación corporativa de forma unificada
            if ($partner->reviews_count > 0) {
                $schemaData["aggregateRating"] = [
                    "@type" => "AggregateRating",
                    "ratingValue" => (string) $partner->rating,
                    "reviewCount" => (string) $partner->reviews_count
                ];
            }
        @endphp

        <script type="application/ld+json">
            {!! json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
        </script>        
   </x-slot:seo>
    <!-- Inicialización del ecosistema con las cuotas máximas de reserva y control Multi-tenant -->
    <div class="mt-5 bg-gray-100 min-h-screen py-12" x-data="bookingSystem({{ $partner->id }}, '{{ $profileType }}', {{ $partner->settings->max_advance_days ?? 30 }})">    
        
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
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5 sticky top-10 space-y-5">
                        
                        <!-- Contenedor Superior (Siempre Visible) -->
                        <div class="text-center relative">
                            <button @click="open = !open" type="button" class="absolute top-0 right-0 p-2 text-slate-400 hover:text-slate-600 lg:hidden focus:outline-none transition-transform duration-200" :class="open ? 'rotate-180' : ''" title="Alternar información del perfil">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </button>

                            <img src="{{ $partner->user->profile_photo_url ?? asset('images/default-clinic.png') }}" class="w-32 h-32 rounded-full mx-auto border-4 border-indigo-50 shadow-sm object-cover">
                            
                            <div class="font-black text-xs text-indigo-600 uppercase tracking-widest mt-4">
                                @if($profileType === 'clinic') Centro Médico @else {{ $partner->gender === 'female' ? 'Doctora' : 'Doctor' }} @endif
                            </div>
                            <h1 class="text-2xl font-black text-slate-800 mt-0.5 tracking-tight">
                                @if($profileType === 'clinic') {{ $partner->name }} @else {{ ucfirst($partner->user->name) }} @endif
                            </h1>
                            <p class="text-slate-500 font-semibold text-sm mt-0.5">
                                {{ $partner->specialties->first()->name ?? 'Atención Médica' }}
                            </p>
                            
                            <div class="flex justify-center mt-2.5">
                                @include('partials.stars', ['rating' => $partner->rating])
                            </div>
                        </div>

                        <!-- Bloque Informativo Colapsable (Móviles / Desktop Abierto) -->
                        <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-5 lg:!block">
                            <div class="p-4 text-xs text-slate-600 bg-indigo-50/60 rounded-2xl text-center border border-indigo-100/30 leading-relaxed font-medium">
                                @if($profileType === 'clinic')
                                    Agende una cita presencial o virtual en minutos con nuestro personal de salud calificado en la sede que prefiera.
                                @else
                                    Agende una cita presencial o virtual en minutos con {{ $partner->gender === 'female' ? 'la doctora ' . $partner->user->name : 'el doctor ' . $partner->user->name }} en la sede que mejor se adapte a sus necesidades.
                                @endif
                            </div>
                            <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100/70 space-y-4">    
                                <!-- Perfil Médico -->
                                <div class="space-y-1">
                                    <h4 class="font-bold text-[10px] uppercase text-slate-400 tracking-wider">Perfil Médico</h4>
                                    <div class="text-sm text-slate-600 leading-relaxed">                                    
                                        @if($partner->bio)
                                            <span>Consulta general y preventiva.</span>
                                        @else
                                            <span class="text-xs text-slate-400 italic">Perfil en proceso de actualización.</span>                                        
                                        @endif
                                    </div>
                                </div>

                                <!-- Especialidades Habilitadas -->
                                <div class="space-y-1 border-t border-slate-200/50 pt-3">
                                    <h4 class="font-bold text-[10px] uppercase text-slate-400 tracking-wider">Especialidades Habilitadas</h4>
                                    <div class="text-sm text-slate-800 font-semibold">    
                                        @php
                                            $partnerSpecialties = $partner->specialties->isNotEmpty() ? $partner->specialties->pluck('name')->toArray() : [];
                                        @endphp
                                        {{ !empty($partnerSpecialties) ? implode(', ', $partnerSpecialties) : 'Medicina General' }}                                
                                    </div>
                                </div>
                                
                                <!-- Código Legal y Trayectoria -->
                                <div class="grid grid-cols-2 gap-4 border-t border-slate-200/50 pt-3">
                                    @if($profileType === 'doctor')
                                        <div class="space-y-0.5">
                                            <h4 class="font-bold text-[10px] uppercase text-slate-400 tracking-wider">Licencia Médica</h4>
                                            <div class="text-sm font-semibold text-slate-800">{{ $partner->medical_license ?? 'N/A' }}</div>
                                        </div>
                                    @else
                                        <div class="space-y-0.5">
                                            <h4 class="font-bold text-[10px] uppercase text-slate-400 tracking-wider">Código REPS</h4>
                                            <div class="text-sm font-semibold text-slate-800">{{ $partner->reps_code ?? 'N/A' }}</div>
                                        </div>
                                    @endif
                                    
                                    <div class="space-y-0.5">
                                        <h4 class="font-bold text-[10px] uppercase text-slate-400 tracking-wider">Trayectoria</h4>
                                        <div class="text-sm font-semibold text-slate-800">{{ $partner->experience_years ? $partner->experience_years . ' años' : 'N/A' }}</div>
                                    </div>
                                </div>

                                <!-- Idiomas de Atención (Exclusivo Doctores) -->
                                @if($profileType === 'doctor')
                                    <div class="space-y-1 border-t border-slate-200/50 pt-3">
                                        <h4 class="font-bold text-[10px] uppercase text-slate-400 tracking-wider">Idiomas de atención</h4>
                                        <div class="text-sm font-medium text-slate-700">    
                                            @php
                                                $langNames = ['es' => 'Español', 'en' => 'Inglés', 'pt' => 'Portugués', 'fr' => 'Francés', 'de' => 'Alemán'];
                                                $partnerLanguages = is_array($partner->languages) ? array_map(fn($code) => $langNames[$code] ?? strtoupper($code), $partner->languages) : [];
                                            @endphp
                                            {{ !empty($partnerLanguages) ? implode(', ', $partnerLanguages) : 'Español' }}                                
                                        </div>
                                    </div>
                                @endif

                                <!-- Experiencias y Tratamientos -->
                                <div class="space-y-2 border-t border-slate-200/50 pt-3">
                                    <h4 class="font-bold text-[10px] uppercase text-slate-400 tracking-wider">Tratamientos Comunes</h4>
                                    <div class="flex flex-wrap gap-1.5 pt-0.5">
                                        @if($profileType === 'doctor' && isset($partner->expertises))
                                            @forelse($partner->expertises as $expertise)
                                                <span class="text-xs bg-white text-slate-700 px-2.5 py-1 rounded-lg border border-slate-200/70 font-medium shadow-2xs transition-colors hover:border-slate-300" title="Síntomas: {{ $expertise->symptoms_keywords }}">
                                                    🔍 {{ $expertise->disease_name }}
                                                </span>
                                            @empty
                                                <span class="text-xs text-slate-400 italic">Consulta general y preventiva.</span>
                                            @endforelse
                                        @else
                                            <span class="text-xs text-slate-400 italic">Consulta institucional y especializada.</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- COLUMNA DERECHA: MOTOR DE RESERVAS (Sedes, Servicios y Slots) -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- 1. SELECTOR INTELIGENTE DE SEDES (Amarrado y Bloqueado si viene de Clínica) -->
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 md:p-8 space-y-4">
                        <div class="border-b border-slate-100 pb-3">
                            <h2 class="text-lg font-black text-slate-800 tracking-tight">1. Selecciona el Consultorio o Sede</h2>
                            @if(isset($fromClinicId) && $fromClinicId)
                                <p class="text-xs text-indigo-600 font-bold flex items-center gap-1 mt-0.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39 1.593 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/></svg>
                                    Ubicación corporativa fijada por la clínica seleccionada.
                                </p>
                            @else
                                <p class="text-xs text-slate-400">Elige la ubicación física institucional o la modalidad virtual.</p>
                            @endif
                        </div>

                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @php
                                // Captura unificada del contexto Multi-tenant
                                $currentClinicId = request()->input('clinic_id') ?? request()->input('from_clinic');
                                $urlAddressId = request()->input('address_id');

                                if ($currentClinicId) {
                                    // 🔥 FILTRO DE RENDERIZADO: Si viene de clínica, el médico solo puede atender telemedicina.
                                    // Descartamos fulminantemente cualquier consultorio físico particular de producción.
                                    $activeAddresses = $partner->addresses->where('type', 'virtual');
                                    
                                    // 🚀 FALLBACK AUTOMÁTICO DE ID: Como la URL trae address_id=1, ignoramos ese filtro
                                    // y seleccionamos la primera dirección virtual institucional legítima del doctor (ej: 8 o 10)
                                    $preSelectedAddress = $activeAddresses->where('id', $urlAddressId)->first() ?? $activeAddresses->first();
                                } else {
                                    // ENTORNO DE PRODUCCIÓN ORIGINAL: El médico es consultado de forma directa. Flujo intacto.
                                    $activeAddresses = $partner->addresses;
                                    $preSelectedAddress = $urlAddressId ? $activeAddresses->where('id', $urlAddressId)->first() : null;
                                }
                            @endphp

                            @foreach($activeAddresses as $address)
                                <label class="cursor-pointer block select-none group">
                                    <input type="radio" name="address_id" value="{{ $address->id }}" 
                                        x-on:change="selectAddress({{ $address->id }}, '{{ $address->type }}')" 
                                        {{ ($preSelectedAddress && $preSelectedAddress->id === $address->id) ? 'checked' : '' }}
                                        class="sr-only">
                                    
                                    <!-- Control visual reactivo asignado a Alpine.js -->
                                    <div :class="selectedAddress === {{ $address->id }} ? 'bg-indigo-50/70 border-indigo-600 ring-2 ring-indigo-600/20' : 'bg-white border-slate-200 hover:bg-slate-50'"
                                         class="p-4 border rounded-2xl transition-all shadow-sm flex flex-col justify-between h-full">
                                        
                                        <div class="space-y-0.5">
                                            <span class="font-extrabold text-slate-800 text-sm block group-hover:text-indigo-600 transition-colors">{{ $address->name }}</span>
                                            <span class="text-xs text-slate-500 block leading-tight">{{ $address->address_line ?? $address->address }}</span>
                                        </div>

                                        <div class="mt-3 border-t border-slate-100 pt-2 flex items-center justify-between">
                                            @if($currentClinicId)
                                                {{-- Forzamos visualmente el badge de la clínica institucional si viene derivado de allí --}}
                                                <span class="text-[9px] font-black text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-100/30">🏢 Sede Virtual Institucional</span>
                                            @else
                                                <span class="text-[9px] font-black text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-100/30">👨‍⚕️ Consulta Privada</span>
                                            @endif
                                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">{{ $address->type === 'virtual' ? 'Online' : 'Presencial' }}</span>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>


                    </div>

                    <!-- 2. SELECTOR DE SERVICIOS (Filtrado por Sede vía Alpine.js) -->
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 md:p-8 space-y-4" x-show="selectedAddress !== null" x-init="@if(isset($preSelectedAddress) && $preSelectedAddress) $nextTick(() => { selectAddress({{ $preSelectedAddress->id }}, '{{ $preSelectedAddress->type }}') }); @endif" x-transition>
                        <div class="border-b border-slate-100 pb-3">
                            <h2 class="text-lg font-black text-slate-800 tracking-tight">2. Elige el Servicio Médico</h2>
                            <p class="text-xs text-slate-400">Los valores y tiempos varían según la sede seleccionada.</p>
                        </div>

                        <div class="space-y-2" x-show="availableServices.length > 0">
                            <template x-for="service in availableServices" :key="service.id">
                                <label class="cursor-pointer block select-none">
                                    <input type="radio" name="service_id" :value="service.id" x-on:change="selectService(service.id, service.duration)" class="sr-only peer">
                                    <div class="p-4 bg-white border border-slate-200 rounded-2xl hover:bg-slate-50 peer-checked:bg-indigo-50/50 peer-checked:border-indigo-600 transition-all flex items-center justify-between gap-4 shadow-sm">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-800 text-sm" x-text="service.name"></span>
                                            <span class="text-xs text-slate-400 mt-0.5" x-text="'⏱ Duración: ' + service.duration + ' min'"></span>
                                        </div>
                                        <span class="text-base font-black text-green-600" x-text="'$' + new Intl.NumberFormat('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(service.price)"></span>
                                    </div>
                                </label>
                            </template>
                        </div>
                        <!-- Estado Vacío Condicional del Catálogo de Servicios -->
                        <div class="p-6 bg-slate-50 border-2 border-dashed border-slate-200 rounded-2xl text-center flex flex-col items-center justify-center py-8" x-show="availableServices.length === 0" x-transition>
                            <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 mb-3 border border-slate-200/50 shadow-inner">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                            </div>
                            <h4 class="text-slate-800 font-extrabold text-sm uppercase tracking-wider">Catálogo No Disponible</h4>
                            <p class="text-slate-500 text-xs mt-1 max-w-sm mx-auto leading-relaxed">
                                @if($profileType === 'clinic') El centro médico @else El especialista @endif aún no ha cargado los servicios de salud ni las tarifas correspondientes para esta sede. Por favor, selecciona otro consultorio de la lista de arriba.
                            </p>
                        </div>
                    </div>

                    <!-- 3. CALENDARIO Y SLOTS DE ATENCIÓN (Alpine.js) -->
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 md:p-8 space-y-4" x-show="selectedService !== null" x-transition>
                        <div class="border-b border-slate-100 pb-3">
                            <h2 class="text-lg font-black text-slate-800 tracking-tight">3. Selecciona la Fecha y Hora</h2>
                            <p class="text-xs text-slate-400">Selecciona el día en el calendario para calcular los espacios disponibles en tiempo real.</p>
                        </div>

                        <!-- Selector de Fecha Nativo con Bloqueo de Pasado -->
                        <div>
                            <input type="date" x-model="selectedDate" x-on:change="fetchAvailableSlots()" min="{{ \Carbon\Carbon::now()->toDateString() }}" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 text-base font-bold focus:ring-2 focus:ring-indigo-500 shadow-inner text-slate-700">
                        </div>

                        <!-- Grilla Dinámica de Bloques Horarios (Slots Libres) -->
                        <div class="pt-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-black uppercase text-slate-400 tracking-wider">Horarios Disponibles</span>
                                <span class="text-xs font-bold text-indigo-600 animate-pulse italic" x-show="loadingSlots">Consultando disponibilidad...</span>
                            </div>

                            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2">
                                <template x-for="slot in availableSlots" :key="slot.time">
                                    <button type="button" x-on:click="confirmBooking(slot.time)" class="p-3 text-center bg-white border border-slate-200 rounded-xl font-bold text-xs text-slate-700 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition-all shadow-sm">
                                        <span x-text="slot.time"></span>
                                    </button>
                                </template>
                            </div>

                            <!-- Estado Vacío del Calendario -->
                            <div class="p-6 bg-slate-50 border border-dashed rounded-2xl text-center text-xs font-bold text-slate-400" x-show="availableSlots.length === 0 && !loadingSlots && selectedDate">
                                No hay turnos de atención disponibles o configurados para la fecha seleccionada. Por favor, elige otro día.
                            </div>
                        </div>
                    </div>

                </div> <!-- Cierre .lg:col-span-2 -->
            </div> <!-- Cierre .grid -->
        </div> <!-- Cierre .max-w-7xl -->

    <!-- ======================================================== -->
    <!-- ARQUITECTURA REACTIVA CON ALPINE.JS (MÁQUINA DE ESTADOS) -->
    <!-- ======================================================== -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('bookingSystem', (partnerId, profileType, maxAdvanceDays) => ({
                selectedAddress: null,
                addressType: null,
                selectedService: null,
                serviceDuration: null,
                selectedDate: '{{ \Carbon\Carbon::now()->toDateString() }}',
                availableServices: [],
                availableSlots: [],
                loadingSlots: false,

                // Capturar el ID global de la clínica aliada si proviene de derivación corporativa
                fromClinicId: '{{ $fromClinicId ?? "" }}',

                // 🔥 SOLUCIÓN AL PARSEERROR: Inyección directa limpia de la colección relacional mapeada desde el backend
                addressesServicesPool: {!! json_encode($activeAddresses->map(function($addr) {
                    return [
                        'id' => $addr->id,
                        'services' => $addr->services->where('active', true)->map(function($srv) {
                            return [
                                'id' => $srv->id,
                                'name' => $srv->name,
                                'duration' => $srv->pivot->duration ?? $srv->duration,
                                'price' => $srv->pivot->price ?? $srv->price
                            ];
                        })->values()->toArray()
                    ];
                })->toArray()) !!},

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

                // ⏱️ INSTANTÁNEO: Extrae los servicios directamente desde la memoria cacheada en Blade
                fetchServicesLocal() {
                    const matchedAddress = this.addressesServicesPool.find(addr => addr.id === this.selectedAddress);
                    this.availableServices = matchedAddress ? matchedAddress.services : [];
                },

                async fetchAvailableSlots() {
                    if (!this.selectedAddress || !this.selectedService || !this.selectedDate) return;
                    this.loadingSlots = true;
                    this.availableSlots = [];

                    try {
                        // 🔒 PARÁMETROS CRUCIALES INTEGRADOS: Mantiene el ID del médico y de la clínica vinculados
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
                            // 🔥 ARREGLO CRÍTICO: Si el perfil es médico pero proviene de una clínica, inyectamos la co-propiedad
                            if (this.fromClinicId) {
                                searchParams.append('clinic_id', this.fromClinicId);
                            }
                        }

                        const url = `/slots?${searchParams.toString()}`;
                        const response = await fetch(url);
                        this.availableSlots = await response.json();
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
                                // Sincronizamos el ID de la clínica en el payload para no perder la Co-propiedad
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
        