@php
// 🔒 ENFOQUE CLÍNICO: Direccionamiento al ecosistema corporativo
$breadcrumbs = [
    [
        'name' => 'Panel Institucional',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Editar Servicio Clínico',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-7xl mx-auto py-10 px-4">
        {{-- Inserta este bloque justo antes de <div x-data="{ type: '...' }"> --}}

        <!-- Alertas de Notificación para el Usuario en Producción -->
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-r-2xl shadow-sm flex items-center gap-3">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="text-sm font-bold text-green-800">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-2xl shadow-sm flex items-center gap-3">
                <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <span class="text-sm font-bold text-red-800">{{ session('error') }}</span>
            </div>
        @endif

        {{-- Contenedor reactivo para inicializar Alpine.js --}}
        <div x-data="{ type: '{{ $service->type }}' }"> 

            <form action="{{ route('partner.clinic.services.update', [$address, $service]) }}" method="POST" class="space-y-6"> 
                @csrf
                @method('PUT')

                <x-validation-errors class="mb-4" />

                {{-- Campos de Nombre y Catálogo Global --}}
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-bold text-gray-700 mb-2">Nombre del Servicio (Global en Catálogo)</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $service->name) }}" class="w-full rounded-2xl border-gray-300 py-3 text-gray-800 focus:ring-indigo-500 focus:border-indigo-500" required>
                    </div>
                </div>

                {{-- Duración del Turno y Modalidad --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Duración del Turno</label>
                        <select name="duration" class="w-full rounded-2xl border-gray-300 py-3 focus:ring-indigo-500">
                            @php 
                                $currentDuration = old('duration', $service->addresses->first()?->pivot->duration);
                            @endphp
                            @foreach([15, 20, 30, 45, 60] as $t)
                                <option value="{{ $t }}" {{ $currentDuration == $t ? 'selected' : '' }}>{{ $t }} min</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Modalidad de Servicio</label>
                        <select class="w-full rounded-2xl border-gray-300 py-3 bg-gray-50 text-gray-500 cursor-not-allowed" disabled>
                            <option value="physical" {{ $service->type === 'physical' ? 'selected' : '' }}>Presencial</option>
                            <option value="virtual" {{ $service->type === 'virtual' ? 'selected' : '' }}>Virtual</option>
                        </select>
                    </div>
                </div>
                <!-- MÓDULO TAXONÓMICO: ESPECIALIDADES DE LA CLÍNICA EN EDICIÓN -->
                <div class="p-6 bg-gray-50 rounded-3xl border border-gray-200 space-y-3 mt-6">
                    <label class="block text-sm font-bold text-gray-800 mb-2">Modificar Especialidad(es) de la Institución</label>
                    <p class="text-xs text-gray-500 mb-4">Ajuste las ramas médicas del centro médico autorizadas para ofrecer este servicio en la agenda.</p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($specialties as $specialty)
                            @php
                                $isChecked = old('specialties') 
                                    ? in_array($specialty->id, old('specialties', [])) 
                                    : in_array($specialty->id, $attachedSpecialtyIds);
                            @endphp
                            <label class="flex items-center p-3 bg-white rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50 transition">
                                <input type="checkbox" name="specialties[]" value="{{ $specialty->id }}"
                                    class="rounded text-indigo-600 focus:ring-indigo-500"
                                    {{ $isChecked ? 'checked' : '' }}>
                                <span class="ml-3 text-sm font-medium text-gray-700">{{ $specialty->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('specialties') <p class="mt-2 text-xs text-red-600 font-bold">{{ $message }}</p> @enderror
                </div>
                <!-- Input exclusivo para Precio Virtual Institucional en Edición -->
                <div x-show="type === 'virtual'" class="p-6 bg-purple-50 rounded-3xl border border-purple-100 mt-6">
                    <label class="block text-sm font-bold text-purple-900 mb-2">Valor de la Telemedicina Institucional ($)</label>
                    <input type="number" name="price_virtual" step="0.01" min="0" 
                        value="{{ old('price_virtual', $service->addresses->firstWhere('type', 'virtual')?->pivot->price) }}"
                        class="w-full md:w-1/2 rounded-2xl border-purple-300 py-3 focus:ring-purple-500 text-gray-800">
                </div>

                <!-- Sedes Corporativas con Tarifas Pre-guardadas -->
                <div x-show="type === 'physical'" class="p-6 bg-blue-50 rounded-3xl border border-blue-100 mt-6">
                    <label class="block text-sm font-bold text-blue-900 mb-4">Sedes habilitadas de la clínica y sus precios:</label>
                    <div class="space-y-3">
                        @foreach($addresses as $addr)
                            @php
                                $pivotData = $service->addresses->firstWhere('id', $addr->id)?->pivot;
                                $isAssigned = !is_null($pivotData);
                                
                                $shouldBeChecked = old('address_ids') 
                                    ? in_array($addr->id, old('address_ids', [])) 
                                    : $isAssigned;
                            @endphp
                            
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-white rounded-xl border border-blue-200 gap-4"
                                x-data="{ checked: {{ $shouldBeChecked ? 'true' : 'false' }} }">
                                
                                <label class="flex items-center cursor-pointer flex-1">
                                    <input type="checkbox" name="address_ids[]" value="{{ $addr->id }}" 
                                        x-model="checked"
                                        class="rounded text-blue-600 focus:ring-blue-500">
                                    <div class="ml-3">
                                        <span class="block text-sm font-bold text-gray-800">{{ $addr->name }}</span>
                                        <span class="block text-xs text-gray-500">{{ $addr->address }}, {{ $addr->city->name ?? 'N/A' }}</span>
                                    </div>
                                </label>

                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-gray-500">Precio Sede ($)</span>
                                    <input type="number" name="prices[{{ $addr->id }}]" step="0.01" min="0"
                                        value="{{ old('prices.'.$addr->id, $pivotData?->price ?? '') }}"
                                        x-bind:disabled="!checked"
                                        class="w-32 rounded-xl border-gray-300 py-2 text-sm focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-400" placeholder="0.00">
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('prices.*') 
                        <p class="mt-2 text-xs text-red-600 font-bold">Revisa que los precios ingresados sean números válidos mayores a 0.</p> 
                    @enderror
                </div>
                <!-- Botones de Acción del Formulario -->
                <div class="pt-4 flex gap-4 mt-6">
                    <button type="submit" class="flex-1 bg-blue-600 text-white font-black py-4 rounded-2xl shadow-lg hover:bg-blue-700 transition tracking-wide uppercase text-sm">
                        Actualizar Catálogo Institucional
                    </button>
                    <a href="{{ route('partner.clinic.services.index') }}" class="px-8 py-4 bg-gray-100 text-gray-600 rounded-2xl font-bold hover:bg-gray-200 transition text-sm flex items-center justify-center">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
