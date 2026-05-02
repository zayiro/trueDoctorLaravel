<x-guest-layout>    
    <div class="mt-5 bg-gray-50 min-h-screen py-10" x-data="bookingSystem()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- COLUMNA IZQUIERDA: Info del Doctor -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 sticky top-10">
                        <div class="text-center">
                            <img src="{{ $doctor->user->profile_photo_url }}" class="w-32 h-32 rounded-full mx-auto border-4 border-blue-50">
                            <h1 class="text-2xl font-black text-gray-800 mt-4">{{ $doctor->user->name }}</h1>
                            <p class="text-blue-600 font-bold">{{ $doctor->specialties->first()->name }}</p>
                            
                            <!-- Estrellas que ya habías creado -->
                            <div class="flex justify-center mt-2">
                                @include('partials.stars', ['rating' => $doctor->rating])
                            </div>
                        </div>

                        <div class="mt-8 space-y-4">
                            <h4 class="font-bold text-gray-700">Sobre el doctor</h4>
                            <div class="text-sm text-gray-600 leading-relaxed">{{ $doctor->bio }}</div>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: Flujo de Reserva Inteligente -->
                <div class="lg:col-span-2">
                    <div class="lg:col-span-2">
                        @if($doctor->services->isEmpty())
                            <!-- Aviso de Servicios No Disponibles -->
                            <div class="bg-amber-50 border-2 border-amber-100 rounded-3xl p-5 text-center shadow-sm">
                                <div class="bg-amber-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-amber-900 mb-2">Servicios no disponibles</h3>
                                <p class="text-amber-800 mb-6">Este profesional aún no ha configurado sus servicios o modalidades de atención.</p>
                                
                                <a href="{{ route('search') }}" class="inline-flex items-center px-6 py-3 bg-amber-600 text-white font-bold rounded-2xl hover:bg-amber-700 transition shadow-lg shadow-amber-200">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    Buscar otro especialista
                                </a>
                            </div>
                        @else
                            <div x-data="{ 
                                step: 1, 
                                selectedService: null, 
                                serviceType: '', 
                                addressId: null,
                                addressName: '' 
                            }" class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">

                                <!-- Indicador de Pasos -->
                                <div class="bg-gray-50 px-8 py-4 flex justify-between border-b border-gray-100 text-xs font-bold uppercase tracking-widest">
                                    <span :class="step >= 1 ? 'text-blue-600' : 'text-gray-400'">1. Servicio</span>
                                    <span :class="step >= 2 ? 'text-blue-600' : 'text-gray-400'" x-show="serviceType !== 'virtual'">2. Sede</span>
                                    <span :class="step >= 3 ? 'text-blue-600' : 'text-gray-400'">3. Horario</span>
                                </div>

                                <div class="p-8">
                                    <!-- PASO 1: SELECCIÓN DE SERVICIO -->
                                    <template x-if="step === 1">
                                        <div>
                                            <h3 class="text-xl font-black text-gray-800 mb-6">¿Qué servicio necesitas?</h3>
                                            <div class="grid grid-cols-1 gap-4">
                                                @foreach($doctor->services as $service)
                                                    <button @click="
                                                        selectedService = {{ $service->id }}; 
                                                        serviceType = '{{ $service->type }}';
                                                        step = (serviceType === 'virtual') ? 3 : 2;
                                                        if(serviceType === 'virtual') initCalendar('virtual');
                                                    " class="w-full text-left p-5 rounded-2xl border-2 border-gray-100 hover:border-blue-500 hover:bg-blue-50 transition group">
                                                        <div class="flex justify-between items-center">
                                                            <div>
                                                                <span class="block font-bold text-gray-800 group-hover:text-blue-700">{{ $service->name }}</span>
                                                                <span class="text-xs text-gray-500">{{ $service->duration }} min | {{ ucfirst($service->type) }}</span>
                                                            </div>
                                                            <div class="text-right">
                                                                <span class="block font-black text-blue-600">${{ number_format($service->price, 0) }}</span>
                                                            </div>
                                                        </div>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    </template>

                                    <!-- PASO 2: SELECCIÓN DE SEDE (Solo presencial) -->
                                    <template x-if="step === 2">
                                        <div>
                                            <button @click="step = 1" class="mb-4 text-sm text-blue-600 font-bold">← Volver a servicios</button>
                                            <h3 class="text-xl font-black text-gray-800 mb-6">Selecciona el consultorio</h3>
                                            <div class="space-y-3">
                                                @foreach($doctor->addresses as $address)
                                                    <button @click="
                                                        addressId = {{ $address->id }};
                                                        addressName = '{{ $address->name }}';
                                                        step = 3;
                                                        initCalendar({{ $address->id }});
                                                    " class="w-full text-left p-5 rounded-2xl border-2 border-gray-100 hover:border-blue-500 transition">
                                                        <p class="font-bold text-gray-800">{{ $address->name }}</p>
                                                        <p class="text-sm text-gray-500">{{ $address->address }}, {{ $address->city->name }}</p>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    </template>

                                    <!-- PASO 3: CALENDARIO -->
                                    <template x-if="step === 3">
                                        <div>
                                            <button @click="step = (serviceType === 'virtual' ? 1 : 2)" class="mb-4 text-sm text-blue-600 font-bold">← Volver atrás</button>
                                            <h3 class="text-xl font-black text-gray-800 mb-2">Selecciona tu horario</h3>
                                            <p class="text-sm text-gray-500 mb-6" x-text="serviceType === 'virtual' ? 'Consulta por Telemedicina' : 'Sede: ' + addressName"></p>
                                            
                                            <div id="calendar-public" class="rounded-xl border border-gray-100"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                        @endif
                    </div>                    
                </div>
            </div>
        </div>
    </div>

    <script>
        function bookingSystem() {
            return {
                step: 1,
                selectedService: null,
                serviceType: '',
                addressId: null,
                addressName: '',
                calendar: null,

                selectService(service) {
                    this.selectedService = service.id;
                    this.serviceType = service.type;
                    this.serviceDuration = service.duration;

                    // Si ya hay un calendario creado, lo actualizamos
                    if (this.calendar) {
                        this.updateCalendarDuration(this.serviceDuration);
                    }

                    this.step = (this.serviceType === 'virtual') ? 3 : 2;
                },

                selectAddress(address) {
                    this.addressId = address.id;
                    this.addressName = address.name;
                    this.step = 3;
                },

                goBackFromCalendar() {
                    this.step = (this.serviceType === 'virtual') ? 1 : 2;
                },
                updateCalendarDuration(minutes) {
                    let durationString = this.formatDuration(minutes);
                    
                    this.calendar.setOption('slotDuration', durationString);
                    this.calendar.setOption('snapDuration', durationString);
                    this.calendar.render();
                },
                formatDuration(minutes) {
                    // Convierte 45 a "00:45:00"
                    let h = Math.floor(minutes / 60).toString().padStart(2, '0');
                    let m = (minutes % 60).toString().padStart(2, '0');
                    return `${h}:${m}:00`;
                },
                initCalendar(addressId) {
                    this.$nextTick(() => {
                        const calendarEl = document.getElementById('calendar-public');
                        
                        this.calendar = new FullCalendar.Calendar(calendarEl, {
                            initialView: 'timeGridWeek',
                            // Usamos la duración del servicio seleccionado aquí
                            slotDuration: this.formatDuration(this.serviceDuration),
                            events: `/api/availability/${addressId}`,
                            // ... resto de la configuración
                        });
                        this.calendar.render();
                    });
                }
            }
        }
    </script>
</x-guest-layout>
