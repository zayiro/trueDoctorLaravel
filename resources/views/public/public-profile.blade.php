<x-guest-layout>    
    <div class="mt-5 bg-gray-50 min-h-screen py-10" x-data="bookingSystem()">
        <!-- Overlay de Carga Global -->
        <div id="loading-overlay" style="display:none;" class="fixed inset-0 bg-white/80 z-[9999] flex items-center justify-center">
            <div class="flex flex-col items-center">
                <svg class="animate-spin h-12 w-12 text-blue-600 mb-4" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="font-bold text-gray-700">Cargando disponibilidad...</p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- COLUMNA IZQUIERDA: Info del Doctor -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 sticky top-10">
                        <div class="text-center">
                            <img src="{{ $doctor->user->profile_photo_url }}" class="w-32 h-32 rounded-full mx-auto border-4 border-blue-50">
                            <h1 class="text-2xl font-black text-gray-800 mt-4">{{ $doctor->user->name }}</h1>
                            <p class="text-blue-600 font-bold">{{ $doctor->specialties->first()->name ?? 'Especialista' }}</p>
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

                <!-- COLUMNA DERECHA: Reserva en 2 Pasos -->
                <div class="lg:col-span-2">
                    @php $allServices = $doctor->addresses->flatMap->services; @endphp

                    @if($allServices->isEmpty())
                        <div class="bg-amber-50 border-2 border-amber-100 rounded-3xl p-8 text-center shadow-sm">
                            <h3 class="text-xl font-bold text-amber-900 mb-2">No hay servicios disponibles</h3>
                            <p class="text-amber-800 mb-6">El profesional no tiene horarios o servicios configurados actualmente.</p>
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
                                            <div class="flex items-center gap-3 mb-4">
                                                <div class="bg-blue-100 p-2 rounded-lg text-blue-600">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                                </div>
                                                <div>
                                                    <h4 class="font-bold text-gray-800">{{ $address->name }}</h4>
                                                    <p class="text-sm text-gray-500">{{ $address->address }} {{ $address->type !== "virtual" ? "," . $address->city->name : '' }}</p>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 gap-3">
                                                @foreach($address->services as $service)
                                                <button @click="
                                                    selectedAddress = {{ $address->id }};
                                                    selectedService = {{ $service->id }};
                                                    addressName = '{{ $address->name }}';
                                                    step = 2;
                                                    document.getElementById('loading-overlay').style.display = 'flex';
                                                    // Aquí llamarías a tu función de cargar horarios
                                                    setTimeout(() => { document.getElementById('loading-overlay').style.display = 'none'; }, 800);
                                                " class="w-full text-left p-4 bg-white rounded-xl border border-gray-200 hover:border-blue-500 hover:shadow-md transition group">
                                                    <div class="flex justify-between items-center">
                                                        <div>
                                                            <span class="block font-bold text-gray-700 group-hover:text-blue-600">{{ $service->name }}</span>
                                                            <span class="text-xs text-gray-400">{{ $service->duration }} min | {{ $address->type }}</span>
                                                        </div>
                                                        <span class="font-black text-blue-600">${{ number_format($service->price, 0) }}</span>
                                                    </div>
                                                </button>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </template>

                                <!-- PASO 2: HORARIO (CALENDARIO) -->
                                <template x-if="step === 2">
                                    <div>
                                        <div class="flex justify-between items-center mb-6">
                                            <button @click="step = 1" class="text-sm font-bold text-blue-600 flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg> Cambiar Sede
                                            </button>
                                            <span class="text-xs font-bold text-gray-400" x-text="addressName"></span>
                                        </div>
                                        
                                        <h3 class="text-xl font-black text-gray-800 mb-6">Elige el mejor momento</h3>
                                        
                                        <!-- Aquí va tu componente de Calendario / Horas -->
                                        <div class="bg-gray-50 p-10 rounded-3xl border-2 border-dashed border-gray-200 text-center">
                                            <p class="text-gray-500">[ Aquí cargar el calendario para la sede elegida ]</p>
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
</x-guest-layout>
