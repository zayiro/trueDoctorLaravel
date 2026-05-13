@php
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
            <a href="{{ route('partner.services.index') }}" class="text-blue-600 hover:underline text-sm">Volver al listado</a>
        </div>

        @if(!$hasAddresses)
            <!-- Alerta: No hay sedes -->
            <div class="mb-8 p-6 bg-amber-50 border-2 border-amber-200 rounded-3xl flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="bg-amber-200 p-3 rounded-full">
                        <svg class="w-6 h-6 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-amber-900">¡Atención! No tienes una sede física registrada</h4>
                        <p class="text-sm text-amber-700">Para ofrecer servicios presenciales, primero debes registrar una sede.</p>
                        <p class="text-amber-900 mt-2">
                            Si solo atiendes <strong>online</strong>: Crea un nuevo servicio y selecciona el tipo <span class="font-bold">"Virtual"</span>.
                        </p>
                    </div>
                </div>
                <a href="{{ route('partner.addresses.create') }}" class="whitespace-nowrap bg-amber-600 text-white px-6 py-2 rounded-xl font-bold hover:bg-amber-700 transition">
                    Registra una sede aquí
                </a>
            </div>
        @endif     

        <!-- Contenedor con Alpine.js: Si no tiene sedes físicas, fuerza el estado inicial a 'virtual' -->
        <div x-data="{ type: '{{ old('type', $hasAddresses ? 'physical' : 'virtual') }}' }" class="bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-100">
            <form action="{{ route('partner.services.store') }}" method="POST" class="p-8 space-y-6">
                @csrf

                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nombre del Servicio</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Ej: Consulta de Especialista" 
                            class="w-full rounded-2xl border-gray-300 py-3" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Duración (Mapeada dinámicamente según tus opciones) -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Duración</label>
                        <select name="duration" class="w-full rounded-2xl border-gray-300 py-3">
                            @foreach([15, 20, 30, 45, 60] as $time)
                                <option value="{{ $time }}" {{ old('duration', 20) == $time ? 'selected' : '' }}>{{ $time }} min</option>
                            @endforeach
                        </select>
                        @error('duration') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Tipo -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Modalidad</label>
                        <select name="type" x-model="type" class="w-full rounded-2xl border-gray-300 py-3">
                            @if($hasAddresses)
                                <option value="physical">Presencial (En Sede)</option>
                            @endif
                            <option value="virtual">Virtual (Telemedicina)</option>
                        </select>
                        @if(!$hasAddresses)
                            <p class="text-xs text-amber-600 mt-2">
                                ⚠️ Solo puedes crear servicios virtuales porque no tienes sedes físicas registradas.
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Input exclusivo para Precio Virtual (Añadir debajo de la fila de Duración/Modalidad) -->
                <div x-show="type === 'virtual'" class="p-6 bg-purple-50 rounded-3xl border border-purple-100 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-purple-900 mb-2">Valor del Servicio Virtual ($)</label>
                        <input type="number" name="price_virtual" step="0.01" min="0" value="{{ old('price_virtual') }}"
                            class="w-full md:w-1/2 rounded-2xl border-purple-300 py-3 focus:ring-purple-500" placeholder="0.00">
                    </div>
                </div>

                <!-- Selección de Sedes con Precios Individuales -->
                @if($hasAddresses)
                    <div x-show="type === 'physical'" x-transition:enter="transition ease-out duration-300"
                        class="p-6 bg-blue-50 rounded-3xl border border-blue-100">
                        <label class="block text-sm font-bold text-blue-900 mb-4">Sedes donde se ofrece y sus precios:</label>
                        
                        <div class="space-y-3">
                            @foreach($addresses as $address)
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-white rounded-xl border border-blue-200 gap-4">
                                <!-- Checkbox Selección Sede -->
                                <label class="flex items-center cursor-pointer flex-1">
                                    <input type="checkbox" name="address_ids[]" value="{{ $address->id }}" 
                                        class="rounded text-blue-600 focus:ring-blue-500"
                                        {{ in_array($address->id, old('address_ids', [])) ? 'checked' : '' }}>
                                    <div class="ml-3">
                                        <span class="block text-sm font-bold text-gray-800">{{ $address->name }}</span>
                                        <span class="block text-xs text-gray-500">{{ $address->address }}, {{ $address->city->name }}</span>
                                    </div>
                                </label>

                                <!-- Input Precio por Sede -->
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-gray-500">Precio Sede ($)</span>
                                    <input type="number" name="prices[{{ $address->id }}]" step="0.01" min="0" 
                                        value="{{ old('prices.'.$address->id) }}"
                                        class="w-32 rounded-xl border-gray-300 py-2 text-sm focus:ring-blue-500" placeholder="0.00">
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
                     class="p-4 bg-purple-50 rounded-2xl border border-purple-100 flex items-center gap-3">
                    <span class="text-2xl">✨</span>
                    <p class="text-sm text-purple-800 font-medium">
                        Este servicio se realizará por videollamada.<br />
                        El paciente recibirá un link para unirse a la videollamada cuando confirme la reservación.
                    </p>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-4 rounded-2xl shadow-lg shadow-blue-200 transition-all transform hover:-translate-y-1">
                        GUARDAR SERVICIO
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
