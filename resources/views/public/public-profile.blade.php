<x-guest-layout>    
    <div class="mt-5 bg-gray-50 min-h-screen py-10" x-data="bookingSystem({{ $doctor->id }})">    
        <!-- Overlay de Carga Global -->
        <div id="loading-overlay" 
            style="display:none;" 
            class="fixed inset-0 bg-white/95 z-[9999] flex flex-col items-center justify-center backdrop-blur-sm">            
            <div class="flex flex-col items-center">
                <!-- Spinner más grande y visible -->
                <svg class="animate-spin h-16 w-16 text-blue-600 mb-4" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                
                <!-- Texto con mayor peso visual -->
                <p class="text-xl font-black text-gray-800 tracking-tight">Confirmando reservación...</p>
                <p id="getHourText" class="text-sm text-gray-500 font-medium hidden">Estamos buscando los mejores horarios para ti</p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- COLUMNA IZQUIERDA: Info del Doctor -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-4 sticky top-10">
                        <div class="text-center">
                            <img src="{{ $doctor->user->profile_photo_url }}" class="w-32 h-32 rounded-full mx-auto border-4 border-blue-50">
                            <h1 class="text-2xl font-black text-gray-800 mt-4">{{ $doctor->user->name }}</h1>
                            <p class="text-blue-600 font-bold">{{ $doctor->specialties->first()->name ?? 'Especialista' }}</p>
                            <div class="flex justify-center mt-2">
                                @include('partials.stars', ['rating' => $doctor->rating])
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-2xl p-5 mt-4 border border-gray-100">
                            <div class="mt-8 space-y-1">
                                <h4 class="font-bold text-gray-700">Sobre el doctor</h4>
                                <div class="text-sm text-gray-600 leading-relaxed">{{ $doctor->bio ?? 'N/A' }}</div>
                            </div>
                            <div class="mt-3 space-y-1">
                                <h4 class="font-bold text-gray-700">Licencia médica</h4>
                                <div class="text-sm text-gray-600 leading-relaxed">{{ $doctor->medical_license ?? 'N/A' }}</div>
                            </div>
                            <div class="mt-3 space-y-1">
                                <h4 class="font-bold text-gray-700">Años de experiencia</h4>
                                <div class="text-sm text-gray-600 leading-relaxed">{{ $doctor->experience_years ?? 'N/A' }}</div>
                            </div>
                            <div class="mt-3 space-y-1">
                                <h4 class="font-bold text-gray-700">Idiomas</h4>
                                <div class="text-sm text-gray-600 leading-relaxed">{{ $doctor->language ?? 'N/A' }}</div>
                            </div>
                            <div class="mt-3 space-y-1">
                                <h4 class="font-bold text-gray-700">Con nosotros desde</h4>
                                <div class="text-sm text-gray-600 leading-relaxed">{{ $doctor->created_at->diffInYears(now()) < 1 ? '1 año' : $doctor->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: Reserva en 2 Pasos -->
                <div class="lg:col-span-2">
                    @php $allServices = $doctor->addresses->flatMap->services; @endphp

                    @if($allServices->isEmpty())
                        <div class="bg-amber-50 border-2 border-amber-100 rounded-3xl p-8 text-center shadow-sm">
                            <h3 class="text-xl font-bold text-amber-900 mb-2">No hay servicios disponibles</h3>
                            <p class="text-amber-800 mb-6">Nuestro profesional no tiene horarios o servicios configurados actualmente.</p>
                            <a href="{{ route('search') }}" class="inline-flex items-center px-6 py-3 bg-amber-600 text-white font-bold rounded-2xl">Buscar otro especialista</a>
                        </div>
                    @else
                        <div x-data="{ 
                            step: 1, 
                            selectedAddress: null,
                            selectedService: null,
                            addressName: ''
                        }" class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">

                            <!-- Indicador de Pasos Simplificado -->
                            <div class="bg-gray-50 px-8 py-4 flex justify-start gap-8 border-b border-gray-100 text-xs font-bold uppercase tracking-widest">
                                <span :class="step === 1 ? 'text-blue-600' : 'text-gray-400'">1. Sede y Servicio</span>
                                <span :class="step === 2 ? 'text-blue-600' : 'text-gray-400'">2. Horario</span>
                            </div>

                            <div class="p-8">
                                <!-- PASO 1: SELECCIÓN DE SEDE Y SERVICIO -->
                                <template x-if="step === 1">
                                    <div class="space-y-8">
                                        <h3 class="text-xl font-black text-gray-800">Selecciona dónde y qué necesitas</h3>
                                        
                                        @foreach($doctor->addresses as $address)
                                        <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100">
                                            <!-- Cabecera de la Sede -->
                                            <div class="flex items-center gap-3 mb-4">
                                                <div class="bg-blue-100 p-2 rounded-lg text-blue-600">
                                                    @if($address->type === 'virtual')
                                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                    @else
                                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                                    @endif
                                                </div>
                                                <div>
                                                    <h4 class="font-bold text-gray-800">{{ $address->name }}</h4>
                                                    <p class="text-sm text-gray-500">{{ $address->address }} {{ $address->type !== "virtual" ? ", " . $address->city->name : '' }}</p>
                                                </div>
                                            </div>

                                            <!-- Listado de Servicios Disponibles en ESTA Sede -->
                                            <div class="grid grid-cols-1 gap-3">
                                                @foreach($address->services as $service)
                                                <button type="button" 
                                                    {{-- Pasamos el precio y duración específicos del pivot al sistema x-data / JS --}}
                                                    @click="
                                                        selectedAddress = {{ $address->id }};
                                                        selectedService = {{ $service->id }};
                                                        addressName = '{{ $address->name }}';
                                                        currentDuration = {{ $service->pivot->duration }};
                                                        serviceName = '{{ $service->name }}';
                                                        servicePrice = '${{ number_format($service->pivot->price, 2) }}';
                                                        step = 2;
                                                        $wire.selectService({{ $address->id }}, {{ $service->id }}) {{-- Si usas Livewire --}}
                                                    "
                                                    class="w-full text-left p-4 bg-white hover:bg-blue-50/50 rounded-xl border border-gray-200 hover:border-blue-300 transition flex items-center justify-between group">
                                                    
                                                    <div>
                                                        <span class="font-bold text-gray-800 block group-hover:text-blue-700 transition">{{ $service->name }}</span>
                                                        <!-- DURACIÓN DESDE EL PIVOT -->
                                                        <span class="text-xs text-gray-400 font-medium flex items-center gap-1 mt-0.5">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                            {{ $service->pivot->duration }} minutos
                                                        </span>
                                                    </div>

                                                    <!-- PRECIO DESDE EL PIVOT -->
                                                    <div class="text-right">
                                                        <span class="block font-black text-green-600 text-lg">${{ number_format($service->pivot->price, 2) }}</span>
                                                        <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Seleccionar</span>
                                                    </div>
                                                </button>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </template>

                                <!-- PASO 2: HORARIO (CALENDARIO) -->
                                <!-- Aquí va componente de Calendario / Horas -->
                                <template x-if="step === 2">
                                    <div class="space-y-6">
                                        <h3 class="text-xl font-black text-gray-800 text-left">Elige el mejor momento</h3>
                                        <span class="mb-5 font-bold">Fecha y hora</span>
                                        <div class="flex items-center justify-between">
                                            <button @click="step = 1; selectedDate = null; selectedSlot = null" class="text-sm font-bold text-blue-600 flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg> Volver a sede y servico
                                            </button>
                                            <div>
                                                <div class="text-xs text-end font-black text-gray-400 uppercase" x-text="addressName"></div>
                                                <div class="text-xs text-end font-black text-gray-500" x-text="serviceName"></div>
                                                <div class="text-xs text-end font-black text-gray-500" x-text="servicePrice"></div>
                                            </div>
                                        </div>

                                        <!-- SECCIÓN DE FECHAS -->
                                        <div>
                                            <!-- Estado 1: Mostrar todos los días (Cuando NO hay fecha seleccionada) -->
                                            <div x-show="!selectedDate">
                                                <h3 class="text-lg font-black text-gray-800 mb-4">Selecciona el día</h3>
                                                <div class="flex overflow-x-auto gap-3 pb-4 no-scrollbar">
                                                    <template x-for="day in availableDays" :key="day.date">
                                                        <button @click="selectedDate = day.date; getSlots()" 
                                                            class="flex-shrink-0 w-16 h-20 rounded-2xl border-2 bg-white text-gray-500 border-gray-100 hover:border-blue-300 flex flex-col items-center justify-center transition-all">                                                            
                                                            <!-- Nombre del día (ej: LUN) -->
                                                            <span class="text-sm uppercase font-black tracking-wider opacity-70" x-text="day.dayName"></span>                                                            
                                                            <!-- Número del día (ej: 28) -->
                                                            <span class="text-xl font-black leading-none my-0.5" x-text="day.dayNumber"></span>                                                            
                                                            <!-- NUEVO: Nombre del mes (ej: MAY / JUN) -->
                                                            <span class="text-sm uppercase font-black text-blue-600 tracking-tight" x-text="day.monthName"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>

                                            <!-- Estado 2: Mostrar solo fecha seleccionada y enlace para volver -->
                                            <div x-show="selectedDate" x-transition class="bg-gray-50 rounded-2xl p-4 flex items-center justify-between border border-gray-100">
                                                <div class="flex items-center gap-4 mt-1">
                                                    <div class="bg-blue-600 text-white w-12 h-12 p-2 rounded-xl flex flex-col items-center justify-center shadow-md">
                                                        <span class="text-[9px] uppercase font-bold" x-text="formatDate(selectedDate).dayName"></span>
                                                        <span class="font-black leading-none" x-text="formatDate(selectedDate).dayNumber"></span>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-gray-400 font-bold uppercase tracking-tighter">Fecha seleccionada</p>
                                                        <p class="font-bold text-gray-800" x-text="formatDate(selectedDate).fullDate"></p>
                                                    </div>
                                                </div>
                                                <button @click="selectedDate = null; selectedSlot = null" class="text-sm font-bold text-blue-600 hover:underline">
                                                    Cambiar fecha
                                                </button>
                                            </div>
                                        </div>                                    

                                        <!-- SECCIÓN DE HORARIOS (Solo si hay fecha seleccionada) -->
                                        <div x-show="selectedDate" x-transition.opacity.duration.500ms>
                                            <h3 class="text-lg font-black text-gray-800 mb-4">Horarios para este día</h3>
                                            <!-- Mensaje de Error Amigable -->
                                            <div x-show="errorMessage" x-transition 
                                                class="bg-red-50 border-l-4 border-red-500 p-4 my-4 rounded-r-2xl flex items-center justify-between">
                                                <div class="flex items-center">
                                                    <svg class="w-5 h-5 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                    </svg>
                                                    <span class="text-sm text-red-700 font-bold" x-text="errorMessage"></span>
                                                </div>
                                                <button @click="getSlots()" class="text-xs font-black text-red-800 uppercase hover:underline">
                                                    Reintentar
                                                </button>
                                            </div>
                                            
                                            <div x-show="loadingSlots" class="py-10 text-center">
                                                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-600 border-t-transparent"></div>
                                            </div>

                                            <div x-show="!loadingSlots" class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                                                <template x-for="slot in availableSlots" :key="slot.time">
                                                    <button @click="selectedSlot = slot.time"
                                                        :class="selectedSlot === slot.time ? 'bg-green-500 text-white border-green-500' : 'bg-white text-gray-700 border-gray-100 hover:bg-blue-50'"
                                                        class="py-3 rounded-xl font-bold text-sm transition border-2">
                                                        <span x-text="formatTime12(slot.time)"></span>
                                                    </button>
                                                </template>
                                            </div>

                                            <div class="mt-8" x-show="selectedSlot">
                                                <button type="button" @click="confirmBooking()" 
                                                    class="w-full bg-blue-600 text-white py-4 rounded-2xl font-black text-lg shadow-xl shadow-blue-200 hover:bg-blue-700 transition">
                                                    Confirmar cita para las <span x-text="formatTime12(selectedSlot)"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <script>
        function bookingSystem(doctorId) {
            return {
                step: 1,
                selectedAddress: null,
                selectedService: null,
                selectedDate: null,
                selectedSlot: null,
                addressName: '',
                serviceName: '',
                servicePrice: '',
                loadingSlots: false,
                availableDays: [],
                availableSlots: [],
                selectedServiceData: { name: '', price: '' },
                errorMessage: null,

                init() {
                    // Usamos la fecha actual como base
                    const today = new Date();
                    const days = [];
                    let maxDaysToShow = 30; // Mostrar 30 días a futuro
                    
                    for (let i = 0; i < maxDaysToShow; i++) {
                        const date = new Date();
                        date.setDate(today.getDate() + i);
                        
                        // Formato ISO local para evitar desfases (YYYY-MM-DD)
                        const year = date.getFullYear();
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const day = String(date.getDate()).padStart(2, '0');
                        const dateString = `${year}-${month}-${day}`;

                        days.push({
                            date: dateString,
                            dayName: date.toLocaleDateString('es-ES', { weekday: 'short' }),
                            dayNumber: date.getDate(),
                            // Mes abreviado (ej: "may", "jun") sin punto final
                            monthName: date.toLocaleDateString('es-ES', { month: 'short' }).replace('.', '')
                        });
                    }
                    this.availableDays = days;
                },
                selectService(id, name, sId, sName, sPrice) {                    
                    this.selectedAddress = id;
                    this.addressName = name;
                    this.serviceName = sName;
                    this.servicePrice = sPrice;
                    this.selectedService = sId;
                    this.selectedServiceData = { name: sName, price: sPrice };
                    this.step = 2;
                    
                    this.isLoading = true;
                    this.showHourText = true;

                    // Simular carga (ej. llamada a API de horarios)
                    setTimeout(() => { 
                        this.isLoading = false; 
                    }, 800);
                },
                formatDate(dateStr) {
                    if(!dateStr) return {};
                    const date = new Date(dateStr + "T00:00:00");
                    return {
                        dayNumber: date.getDate(),
                        dayName: date.toLocaleDateString('es-ES', { weekday: 'short' }),
                        fullDate: date.toLocaleDateString('es-ES', { 
                            weekday: 'long', 
                            day: 'numeric', 
                            month: 'long' 
                        })
                    };
                },
                getSlots() {
                    this.loadingSlots = true;
                    this.errorMessage = null;
                    this.availableSlots = [];
                    this.selectedSlot = null; // Resetear hora al cambiar día

                    // 1. Determinar si el servicio actual es virtual evaluando la variable global 'type' de tu vista
                    const isVirtualService = this.type === 'virtual' ? 'true' : 'false';
                    
                    // 2. Obtener la duración específica recuperada en el paso anterior (por defecto 20)
                    const serviceDuration = this.serviceDuration || 20;
                    
                    // 3. Inyectar todos los nuevos parámetros requeridos por la ruta API de Laravel
                    fetch(`/api/get-slots?address_id=${this.selectedAddress}&date=${this.selectedDate}&is_virtual=${isVirtualService}&duration=${serviceDuration}`)
                    .then(res => {
                        if (!res.ok) throw new Error('Error en el servidor');
                        return res.json();
                    })
                    .then(data => {
                        this.availableSlots = data;
                        if (data.length === 0) {
                            this.errorMessage = "No hay horarios disponibles para este día.";
                        }
                        this.loadingSlots = false;
                    })
                    .catch(error => {
                        this.errorMessage = "Lo sentimos, no pudimos cargar los horarios. Inténtalo de nuevo.";
                        this.loadingSlots = false;
                    });
                },
                confirmBooking() {
                    this.loadingSubmit = true;
                    const loader = document.getElementById('loading-overlay');
                    if (loader) loader.style.display = 'flex';

                    // Datos consolidados del flujo Sede -> Horario
                    const formData = {
                        doctor_id: {{ $doctor->id }},
                        address_id: this.selectedAddress,
                        service_id: this.selectedService,
                        date: this.selectedDate,
                        hour: this.selectedSlot,
                    };

                    fetch("{{ route('appointments.step-two') }}", { // Usamos el nombre de la ruta
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(formData)
                    })
                    .then(res => {
                        if (!res.ok) return res.json().then(json => { throw json; });
                        return res.json();
                    })
                    .then(data => {
                        console.log(data);
                        if (data.status) {
                            // Redirección directa al panel del paciente
                            window.location.href = "{{ route('appointments.patient') }}";
                        }
                    })
                    .catch(error => {
                        if (loader) loader.style.display = 'none';
                        this.loadingSubmit = false;
                        // Mostrar error de validación o del servidor
                        this.errorMessage = error.message || 'Error al procesar la reserva.';
                        console.error('Error en storeStepTwo:', error);
                    });
                },
                formatTime12(timeStr) {
                    if (!timeStr) return '';
                    // timeStr viene como "13:20"
                    const [hours, minutes] = timeStr.split(':');
                    const date = new Date();
                    date.setHours(parseInt(hours), parseInt(minutes), 0);
                    
                    // Retorna el formato "1:20 p. m." o "01:20 PM" según la configuración local
                    return date.toLocaleTimeString('es-ES', { 
                        hour: '2-digit', 
                        minute: '2-digit', 
                        hour12: true 
                    });
                }
            }
        }
    </script>
</x-guest-layout>
