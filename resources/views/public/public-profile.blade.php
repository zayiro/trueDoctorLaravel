<x-guest-layout>    
    <!-- Inyección exclusiva para el HEAD (SEO Estructurado Avanzado) -->
    <x-slot:seo>
        <meta name="title" content="{{ $seoTitle }}">
        <meta name="description" content="{{ $seoDescription }}">
        <meta name="robots" content="{{ $metaRobots }}">
        @php
            // 1. Construimos el mapa relacional plano para los rastreadores de Google
            $schemaData = [
                "@context" => "https://schema.org",
                "@type" => "Physician",
                "name" => "Dr(a). " . $doctor->user->name,
                "image" => $doctor->user->profile_photo_url,
                "medicalSpecialty" => $doctor->specialties->first()?->name ?? 'Medicina General',
                "telephone" => $doctor->phone ?? 'N/A',
                "url" => url()->current(),
                "description" => $doctor->bio ? str(strip_tags($doctor->bio))->limit(160, '...')->toString() : 'Profesional de la salud dedicado a mantener y recuperar el bienestar humano'
            ];

            // 2. Acoplamos las calificaciones de reputación corporativa si registran datos
            if ($doctor->reviews_count > 0) {
                $schemaData["aggregateRating"] = [
                    "@type" => "AggregateRating",
                    "ratingValue" => (string) $doctor->rating,
                    "reviewCount" => (string) $doctor->reviews_count
                ];
            }
        @endphp

        {{-- Renderizado inyectado: Blindado contra saltos de línea o comillas rotas --}}
        <script type="application/ld+json">
            {!! json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
        </script>        
   </x-slot:seo> 
    
    <!-- Inicialización del ecosistema con las cuotas máximas de reserva programadas -->
    <div class="mt-5 bg-gray-100 min-h-screen py-12" x-data="bookingSystem({{ $doctor->id }}, {{ $doctor->settings->max_advance_days ?? 30 }})">    
        
        <!-- Overlay de Carga Atómico Global (Pantalla de Espera) -->
        <div id="loading-overlay" 
            style="display:none;" 
            class="fixed inset-0 bg-white/95 z-[9999] flex flex-col items-center justify-center backdrop-blur-sm">            
            <div class="flex flex-col items-center">
                <svg class="animate-spin h-16 w-16 text-indigo-600 mb-4" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                
                <p class="text-xl font-black text-slate-800 tracking-tight">Confirmando reservación médica...</p>
                <p id="getHourText" class="text-sm text-slate-400 font-medium hidden">Buscando los mejores espacios horarios libres para ti</p>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- COLUMNA IZQUIERDA: Tarjeta Informativa del Especialista -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5 sticky top-10 space-y-5">
                        <div class="text-center">
                            <img src="{{ $doctor->user->profile_photo_url }}" class="w-32 h-32 rounded-full mx-auto border-4 border-indigo-50 shadow-sm object-cover">
                            <div class="font-black text-xs text-indigo-600 uppercase tracking-widest mt-4">{{ $doctor->gender === 'female' ? 'Doctora' : 'Doctor' }}</div>
                            <h1 class="text-2xl font-black text-slate-800 mt-0.5 tracking-tight">{{ ucfirst($doctor->user->name) }}</h1>
                            <p class="text-slate-500 font-semibold text-sm mt-0.5">{{ $doctor->specialties->first()->name ?? 'Especialista Médico' }}</p>
                            
                            <div class="flex justify-center mt-2.5">
                                @include('partials.stars', ['rating' => $doctor->rating])
                            </div>
                        </div>

                        <div class="p-4 text-xs text-slate-600 bg-indigo-50/60 rounded-2xl text-center border border-indigo-100/30 leading-relaxed font-medium">
                            Agende una cita presencial o virtual en minutos con <span>{{ $doctor->gender === 'female' ? 'la doctora ' . $doctor->user->name : 'el doctor ' . $doctor->user->name }}</span> en la sede y horario que mejor se adapte a sus necesidades.
                        </div>

                        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100/70 space-y-4">
                            <div class="space-y-1">
                                <h4 class="font-bold text-xs uppercase text-slate-400 tracking-wider">Sobre el profesional</h4>
                                <div class="text-sm text-slate-600 leading-relaxed whitespace-pre-line">{{ $doctor->bio ?? 'Perfil profesional en proceso de actualización.' }}</div>
                            </div>

                            <div class="space-y-1">
                                <h4 class="font-bold text-xs uppercase text-slate-400 tracking-wider">Especialidades</h4>
                                <div class="text-sm text-slate-700 font-semibold">    
                                    @php
                                        $doctorSpecialties = $doctor->specialties->isNotEmpty() 
                                            ? $doctor->specialties->pluck('name')->toArray() 
                                            : [];
                                    @endphp
                                    {{ !empty($doctorSpecialties) ? implode(', ', $doctorSpecialties) : 'Medicina General' }}                                
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-3 border-t border-slate-200/50 pt-3">
                                <div class="space-y-0.5">
                                    <h4 class="font-bold text-[10px] uppercase text-slate-400 tracking-wider">Licencia Médica</h4>
                                    <div class="text-xs font-bold text-slate-700">{{ $doctor->medical_license ?? 'N/A' }}</div>
                                </div>
                                <div class="space-y-0.5">
                                    <h4 class="font-bold text-[10px] uppercase text-slate-400 tracking-wider">Experiencia</h4>
                                    <div class="text-xs font-bold text-slate-700">{{ $doctor->experience_years ? $doctor->experience_years . ' años' : 'N/A' }}</div>
                                </div>
                            </div>

                            <div class="space-y-1 border-t border-slate-200/50 pt-3">
                                <h4 class="font-bold text-[10px] uppercase text-slate-400 tracking-wider">Idiomas de atención</h4>
                                <div class="text-xs font-semibold text-slate-700">    
                                    @php
                                        $langNames = ['es' => 'Español', 'en' => 'Inglés', 'pt' => 'Portugués', 'fr' => 'Francés', 'de' => 'Alemán'];
                                        $doctorLanguages = is_array($doctor->languages) 
                                            ? array_map(fn($code) => $langNames[$code] ?? strtoupper($code), $doctor->languages) 
                                            : [];
                                    @endphp
                                    {{ !empty($doctorLanguages) ? implode(', ', $doctorLanguages) : 'Español' }}                                
                                </div>
                            </div>

                            <div class="space-y-2 border-t border-slate-200/50 pt-3">
                                <h4 class="font-bold text-[10px] uppercase text-slate-400 tracking-wider">Enfermedades y síntomas que trata</h4>
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse($doctor->expertises as $expertise)
                                        <span class="text-xs bg-slate-100 text-slate-700 px-3 py-1 rounded-xl border border-slate-200/40 font-medium" title="Síntomas relacionados: {{ $expertise->symptoms_keywords }}">
                                            🔍 {{ $expertise->disease_name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-slate-400 italic">Consulta general y preventiva.</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- COLUMNA DERECHA: MOTOR DE RESERVAS (Sedes, Servicios y Slots) -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- 1. SELECTOR INTELIGENTE DE SEDES (Privadas + Clínicas) -->
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 md:p-8 space-y-4">
                        <div class="border-b border-slate-100 pb-3">
                            <h2 class="text-lg font-black text-slate-800 tracking-tight">1. Selecciona el Consultorio o Sede</h2>
                            <p class="text-xs text-slate-400">Elige la ubicación física institucional o la modalidad virtual.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @php
                                // 1. Obtener los IDs de las clínicas del doctor
                                $clinicIds = $doctor->clinics->pluck('id')->toArray();

                                // 2. Traer todas las sedes (las del doctor OR las de sus clínicas) en una sola consulta
                                $allAddresses = \App\Models\Address::where('status', true)
                                    ->where(function($query) use ($doctor, $clinicIds) {
                                        $query->where('doctor_id', $doctor->id)
                                            ->orWhereIn('clinic_id', $clinicIds);
                                    })
                                    ->get();
                            @endphp

                            @foreach($allAddresses as $address)
                                <label class="cursor-pointer block select-none group">
                                    <!-- Alpine.js captura el cambio de sede y actualiza los catálogos locales instantáneamente -->
                                    <input type="radio" name="address_id" value="{{ $address->id }}" 
                                        x-on:change="selectAddress({{ $address->id }}, '{{ $address->type }}')" 
                                        class="sr-only peer">
                                    
                                    <div class="p-4 bg-white border border-slate-200 rounded-2xl hover:bg-slate-50 peer-checked:bg-indigo-50/50 peer-checked:border-indigo-600 peer-checked:ring-1 peer-checked:ring-indigo-600 transition-all shadow-sm flex flex-col justify-between h-full">
                                        <div class="space-y-0.5">
                                            <span class="font-extrabold text-slate-800 text-sm block group-hover:text-indigo-600 transition-colors">
                                                {{ $address->name }}
                                            </span>
                                            <span class="text-xs text-slate-500 block leading-tight">
                                                {{ $address->address }}{{ $address->type === 'virtual' ? '' : ', ' . ($address->city->name ?? '') }}
                                            </span>
                                        </div>

                                        <!-- Badge Distintivo de Co-propiedad Corporativa -->
                                        <div class="mt-3 border-t border-slate-100 pt-2 flex items-center justify-between">
                                            @if($address->clinic_id)
                                                <span class="text-[9px] font-black text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-100/30">
                                                    🏢 {{ $address->clinic->name }}
                                                </span>
                                            @else
                                                <span class="text-[9px] font-black text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-100/30">
                                                    👨‍⚕️ Consulta Privada
                                                </span>
                                            @endif
                                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">
                                                {{ $address->type === 'virtual' ? 'Online' : 'Presencial' }}
                                            </span>
                                        </div>
                                    </div>
                                </label>
                            @endforeach

                        </div>
                    </div>

                    <!-- 2. SELECTOR DE SERVICIOS (Filtrado por Sede vía Alpine.js) -->
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 md:p-8 space-y-4" x-show="selectedAddress !== null" x-transition>
                        <div class="border-b border-slate-100 pb-3">
                            <h2 class="text-lg font-black text-slate-800 tracking-tight">2. Elige el Servicio Médico</h2>
                            <p class="text-xs text-slate-400">Los valores y tiempos varían según la sede seleccionada.</p>
                        </div>

                        <!-- Contenedor del listado de servicios válidos -->
                        <div class="space-y-2" x-show="availableServices.length > 0">
                            <!-- Bucle Reactivo de Servicios inyectados por la API según la Sede elegida -->
                            <template x-for="service in availableServices" :key="service.id">
                                <label class="cursor-pointer block select-none">
                                    <input type="radio" name="service_id" :value="service.id" 
                                        x-on:change="selectService(service.id, service.duration)" 
                                        class="sr-only peer">
                                    <div class="p-4 bg-white border border-slate-200 rounded-2xl hover:bg-slate-50 peer-checked:bg-indigo-50/50 peer-checked:border-indigo-600 transition-all flex items-center justify-between gap-4 shadow-sm">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-800 text-sm" x-text="service.name"></span>
                                            <span class="text-xs text-slate-400 mt-0.5" x-text="'⏱ Duración: ' + service.duration + ' min'"></span>
                                        </div>
                                        <span class="text-base font-black text-green-600" x-text="'$' + parseFloat(service.price).toFixed(2)"></span>
                                    </div>
                                </label>
                            </template>
                        </div>

                        <!-- 🔥 NUEVO: MENSAJE DE CONTINGENCIA UX CUANDO LA SEDE ESTÁ VACÍA -->
                        <div class="p-6 bg-slate-50 border-2 border-dashed border-slate-200 rounded-2xl text-center flex flex-col items-center justify-center py-8" 
                            x-show="availableServices.length === 0" x-transition>
                            <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 mb-3 border border-slate-200/50 shadow-inner">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                            </div>
                            <h4 class="text-slate-800 font-extrabold text-sm uppercase tracking-wider">Catálogo No Disponible</h4>
                            <p class="text-slate-500 text-xs mt-1 max-w-sm mx-auto leading-relaxed">
                                El especialista o centro médico aún no ha cargado los servicios de salud ni las tarifas correspondientes para esta sede. Por favor, selecciona otro de los consultorios disponibles en la lista de arriba.
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
                            <input type="date" x-model="selectedDate" x-on:change="fetchAvailableSlots()"
                                   min="{{ now()->toDateString() }}" 
                                   class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 text-base font-bold focus:ring-2 focus:ring-indigo-500 shadow-inner text-slate-700">
                        </div>

                        <!-- Grilla Dinámica de Bloques Horarios (Slots Libres) -->
                        <div class="pt-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-black uppercase text-slate-400 tracking-wider">Horarios Disponibles</span>
                                <span class="text-xs font-bold text-indigo-600 animate-pulse italic" x-show="loadingSlots">Consultando disponibilidad...</span>
                            </div>

                            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2">
                                <template x-for="slot in availableSlots" :key="slot.time">
                                    <button type="button" 
                                            x-on:click="confirmBooking(slot.time)"
                                            class="p-3 text-center bg-white border border-slate-200 rounded-xl font-bold text-xs text-slate-700 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition-all shadow-sm">
                                        <span x-text="slot.time.substring(0, 5)"></span>
                                    </button>
                                </template>
                            </div>

                            <!-- Estado Vacío del Calendario -->
                            <div class="p-6 bg-slate-50 border border-dashed rounded-2xl text-center text-xs font-bold text-slate-400" 
                                 x-show="availableSlots.length === 0 && !loadingSlots && selectedDate">
                                No hay turnos de atención disponibles o configurados para la fecha seleccionada. Por favor, elige otro día.
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- ARQUITECTURA REACTIVA CON ALPINE.JS (MÁQUINA DE ESTADOS) -->
    <!-- ======================================================== -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('bookingSystem', (doctorId, maxAdvanceDays) => ({
                selectedAddress: null,
                addressType: null,
                selectedService: null,
                serviceDuration: null,
                selectedDate: '{{ now()->toDateString() }}',
                availableServices: [],
                availableSlots: [],
                loadingSlots: false,

                selectAddress(id, type) {
                    this.selectedAddress = id;
                    this.addressType = type;
                    this.selectedService = null;
                    this.availableSlots = [];
                    this.fetchServices();
                },

                selectService(id, duration) {
                    this.selectedService = id;
                    this.serviceDuration = duration;
                    this.fetchAvailableSlots();
                },

                async fetchServices() {
                    try {
                        const response = await fetch(`/api/addresses/${this.selectedAddress}/services`);
                        this.availableServices = await response.json();
                    } catch (error) {
                        console.error("Error cargando el catálogo de la sede:", error);
                    }
                },

                async fetchAvailableSlots() {
                    if (!this.selectedAddress || !this.selectedService || !this.selectedDate) return;
                    
                    this.loadingSlots = true;
                    this.availableSlots = [];

                    try {
                        const url = `/api/slots?date=${this.selectedDate}&duration=${this.serviceDuration}&address_id=${this.selectedAddress}&doctor_id=${doctorId}&is_virtual=${this.addressType === 'virtual' ? 'true' : 'false'}`;
                        const response = await fetch(url);
                        this.availableSlots = await response.json();
                    } catch (error) {
                        console.error("Error consultando los espacios libres:", error);
                    } finally {
                        this.loadingSlots = false;
                    }
                },

                async confirmBooking(time) {
                    document.getElementById('loading-overlay').style.display = 'flex';

                    try {
                        const response = await fetch("{{ route('appointments.step-two') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            body: JSON.stringify({
                                service_id: this.selectedService,
                                address_id: this.selectedAddress,
                                date: this.selectedDate,
                                hour: time
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
                        console.error("Fallo crítico en el túnel de reserva:", error);
                    }
                }
            }));
        });
    </script>
</x-guest-layout>
