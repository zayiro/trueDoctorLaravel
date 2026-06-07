@php
// 🔒 CORREGIDO: Redirección del Breadcrumb al Dashboard de Aliados del SaaS
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Crear servicio',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-6xl mx-auto py-10 px-4">
        <div class="mb-6 flex items-center justify-between">
            <a href="{{ route('partner.services.index') }}" class="text-blue-600 hover:underline text-sm flex items-center gap-1">
                <!-- 🎯 HEROICONS MIGRADO: arrow-left (outline) -->
                <svg class="w-4 h-4" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Volver al listado
            </a>
        </div>

        @if(!$hasAddresses)
            <!-- Alerta Preventiva: No hay sedes físicas registradas en el plan -->
            <div class="mb-8 p-6 bg-amber-50 border-2 border-amber-200 rounded-3xl flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="bg-amber-200 p-3 rounded-full">
                        <!-- 🎯 HEROICONS MIGRADO: exclamation-triangle (outline) -->
                        <svg class="w-6 h-6 text-amber-700" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-bold text-amber-900">¡Atención! No tienes una sede física registrada</h4>
                        <p class="text-sm text-amber-700 mt-0.5">Para ofrecer servicios de salud presenciales, primero debes dar de alta un consultorio físico.</p>
                        <p class="text-xs text-amber-900 mt-2 bg-amber-100 p-2 rounded-lg inline-block">
                            Si solo atiendes <strong>online</strong>: Procede a rellenar el formulario seleccionando la modalidad <span class="font-bold">"Virtual"</span>.
                        </p>
                    </div>
                </div>
                <a href="{{ route('partner.addresses.create') }}" class="whitespace-nowrap bg-amber-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-amber-700 transition shadow-sm text-sm">
                    Registra una sede aquí
                </a>
            </div>
        @endif
        <!-- Contenedor Base con Alpine.js -->
        <div x-data="{ type: '{{ old('type', $hasAddresses ? 'physical' : 'virtual') }}' }" class="bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-100">
            <form action="{{ route('partner.services.store') }}" method="POST" class="p-8 space-y-6">
                @csrf

                <x-validation-errors class="mb-4" />

                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nombre del Servicio Clínico</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Ej: Consulta de Especialista, Examen de Diagnóstico" 
                            class="w-full rounded-2xl border-gray-300 py-3 focus:ring-indigo-500 focus:border-indigo-500" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Duración de la Cita</label>
                        <select name="duration" class="w-full rounded-2xl border-gray-300 py-3 focus:ring-indigo-500">
                            @foreach([15, 20, 30, 45, 60] as $time)
                                <option value="{{ $time }}" {{ old('duration', 20) == $time ? 'selected' : '' }}>{{ $time }} min</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Modalidad de Atención</label>
                        <select name="type" x-model="type" class="w-full rounded-2xl border-gray-300 py-3 focus:ring-indigo-500">
                            @if($hasAddresses)
                                <option value="physical">Presencial (En Sede Física)</option>
                            @endif
                            <option value="virtual">Virtual (Telemedicina)</option>
                        </select>
                        @if(!$hasAddresses)
                            <p class="text-xs text-amber-600 mt-2 font-medium bg-amber-50 p-2 rounded-lg border border-amber-100 inline-block">
                                ⚠️ Solo puedes crear servicios virtuales debido a que no posees sedes físicas activas.
                            </p>
                        @endif
                    </div>
                </div>
                <!-- MÓDULO TAXONÓMICO DE ESPECIALIDADES ASOCIADAS -->
                <div class="p-6 bg-gray-50 rounded-3xl border border-gray-200 space-y-3 mt-6">
                    <label class="block text-sm font-bold text-gray-800 mb-2">Asociar a Especialidad(es) Médica(s)</label>
                    <p class="text-xs text-gray-500 mb-4">Selecciona las especialidades de tu perfil que atienden o son afines a este servicio clínico.</p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($specialties as $specialty)
                            <label class="flex items-center p-3 bg-white rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50 transition">
                                <input type="checkbox" name="specialties[]" value="{{ $specialty->id }}"
                                    class="rounded text-indigo-600 focus:ring-indigo-500"
                                    {{ in_array($specialty->id, old('specialties', [])) ? 'checked' : '' }}>
                                <span class="ml-3 text-sm font-medium text-gray-700">{{ $specialty->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('specialties') <p class="mt-2 text-xs text-red-600 font-bold">{{ $message }}</p> @enderror
                </div>
                <!-- Input Exclusivo para Precio Virtual -->
                <div x-show="type === 'virtual'" x-transition class="p-6 bg-purple-50 rounded-3xl border border-purple-100 space-y-4 mt-6">
                    <div>
                        <label class="block text-sm font-bold text-purple-900 mb-2">Valor del Servicio Virtual / Videoconsulta ($)</label>
                        <input type="number" name="price_virtual" step="0.01" min="0" value="{{ old('price_virtual') }}"
                            class="w-full md:w-1/2 rounded-2xl border-purple-300 py-3 focus:ring-purple-500 focus:border-purple-400" placeholder="0.00">
                    </div>
                </div>
                <!-- Selección de Sedes con Precios Individuales -->
                @if($hasAddresses)
                    <div x-show="type === 'physical'" x-transition:enter="transition ease-out duration-300"
                        class="p-6 bg-blue-50 rounded-3xl border border-blue-100 mt-6">
                        <label class="block text-sm font-bold text-blue-900 mb-4">Sedes donde se ofrece y sus precios:</label>
                        
                        <div class="space-y-3">
                            @foreach($addresses as $address)
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-white rounded-xl border border-blue-200 gap-4"
                                 x-data="{ checked: {{ in_array($address->id, old('address_ids', [])) ? 'true' : 'false' }} }">
                                
                                <label class="flex items-center cursor-pointer flex-1">
                                    <input type="checkbox" name="address_ids[]" value="{{ $address->id }}" 
                                        x-model="checked"
                                        class="rounded text-blue-600 focus:ring-blue-500"
                                        {{ in_array($address->id, old('address_ids', [])) ? 'checked' : '' }}>
                                    <div class="ml-3">
                                        <span class="block text-sm font-bold text-gray-800">{{ $address->name }}</span>
                                        <span class="block text-xs text-gray-500">{{ $address->address }}, {{ $address->city->name ?? 'N/A' }}</span>
                                    </div>
                                </label>

                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-gray-500">Precio Sede ($)</span>
                                    <input type="number" name="prices[{{ $address->id }}]" step="0.01" min="0" 
                                        value="{{ old('prices.'.$address->id) }}"
                                        x-bind:disabled="!checked"
                                        class="w-32 rounded-xl border-gray-300 py-2 text-sm focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-400" placeholder="0.00">
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @error('address_ids') <p class="mt-2 text-xs text-red-600 font-bold">{{ $message }}</p> @enderror
                        @error('prices.*') <p class="mt-2 text-xs text-red-600 font-bold">Asegúrate de asignar precios válidos a las sedes seleccionadas.</p> @enderror
                    </div>
                @endif
                <!-- Mensaje informativo para modalidad virtual -->
                <div x-show="type === 'virtual'" 
                     x-transition:enter="transition ease-out duration-300"
                     class="p-4 bg-purple-50 rounded-2xl border border-purple-100 flex items-start gap-3 mt-6">
                    <!-- 🎯 HEROICONS MIGRADO: information-circle (outline) -->
                    <svg class="w-5 h-5 text-purple-600 mt-0.5 flex-shrink-0" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.852l.041-.028M12 18.75h.007v.008H12v-.008zM12 3a9 9 0 100 18 9 9 0 000-18z" />
                    </svg>
                    <p class="text-xs text-purple-800 leading-relaxed">
                        Los servicios virtuales se configuran automáticamente en tu consultorio digital de telemedicina. Al activarlo, tus pacientes podrán agendar videoconsultas de forma inmediata.
                    </p>
                </div>

                <!-- Botones de Acción -->
                <div class="pt-4 flex gap-4 mt-6">
                    <button type="submit" class="flex-1 bg-blue-600 text-white font-black py-4 rounded-2xl shadow-lg hover:bg-blue-700 transition tracking-wide uppercase text-sm">
                        Crear y Configurar Servicio
                    </button>
                    <a href="{{ route('partner.services.index') }}" class="px-8 py-4 bg-gray-100 text-gray-600 rounded-2xl font-bold hover:bg-gray-200 transition text-sm flex items-center justify-center">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
