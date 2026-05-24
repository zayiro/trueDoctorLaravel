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
            <a href="{{ route('partner.services.index') }}" class="text-blue-600 hover:underline text-sm flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver al listado
            </a>
        </div>

        @if(!$hasAddresses)
            <!-- Alerta Preventiva: No hay sedes físicas registradas en el plan -->
            <div class="mb-8 p-6 bg-amber-50 border-2 border-amber-200 rounded-3xl flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="bg-amber-200 p-3 rounded-full">
                        <svg class="w-6 h-6 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
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

                <!-- Render de Errores nativo de Jetstream/Fortify -->
                <x-validation-errors class="mb-4" />

                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nombre del Servicio Clínico</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Ej: Consulta de Especialista, Examen de Diagnóstico" 
                            class="w-full rounded-2xl border-gray-300 py-3 focus:ring-indigo-500 focus:border-indigo-500" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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

                <!-- Input Exclusivo para Precio Virtual -->
                <div x-show="type === 'virtual'" x-transition class="p-6 bg-purple-50 rounded-3xl border border-purple-100 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-purple-900 mb-2">Valor del Servicio Virtual / Videoconsulta ($)</label>
                        <input type="number" name="price_virtual" step="0.01" min="0" value="{{ old('price_virtual') }}"
                            class="w-full md:w-1/2 rounded-2xl border-purple-300 py-3 focus:ring-purple-500 focus:border-purple-400" placeholder="0.00">
                    </div>
                </div>
                <!-- Selección de Sedes con Precios Individuales -->
                @if($hasAddresses)
                    <div x-show="type === 'physical'" x-transition:enter="transition ease-out duration-300"
                        class="p-6 bg-blue-50 rounded-3xl border border-blue-100">
                        <label class="block text-sm font-bold text-blue-900 mb-4">Sedes donde se ofrece y sus precios:</label>
                        
                        <div class="space-y-3">
                            @foreach($addresses as $address)
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-white rounded-xl border border-blue-200 gap-4"
                                 x-data="{ checked: {{ in_array($address->id, old('address_ids', [])) ? 'true' : 'false' }} }">
                                <!-- Checkbox Selección Sede -->
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

                                <!-- Input Precio por Sede -->
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-gray-500">Precio Sede ($)</span>
                                    <input type="number" name="prices[{{ $address->id }}]" step="0.01" min="0" 
                                        value="{{ old('prices.'.$address->id) }}"
                                        :required="checked"
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
                     class="p-4 bg-purple-50 rounded-2xl border border-purple-100 flex items-start gap-3">
                    <svg class="w-5 h-5 text-purple-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-xs text-purple-800 leading-relaxed">
                        Los servicios virtuales se configuran automáticamente en tu consultorio digital de telemedicina. Al activarlo, tus pacientes podrán agendar videoconsultas de forma inmediata.
                    </p>
                </div>

                <!-- Botones de Acción -->
                <div class="pt-4 flex gap-4">
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
