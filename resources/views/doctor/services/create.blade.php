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
            <a href="{{ route('doctor.services.index') }}" class="text-blue-600 hover:underline text-sm">Volver al listado</a>
        </div>

        <!-- Contenedor con Alpine.js -->
        <div x-data="{ type: 'presencial' }" class="bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-100">
            <form action="{{ route('doctor.services.store') }}" method="POST" class="p-8 space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nombre -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nombre del Servicio</label>
                        <input type="text" name="name" placeholder="Ej: Consulta de Especialista" 
                            class="w-full rounded-2xl border-gray-300 focus:ring-blue-500 focus:border-blue-500 py-3" required>
                    </div>

                    <!-- Precio -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Valor del Servicio ($)</label>
                        <input type="number" name="price" placeholder="0.00" step="0.01"
                            class="w-full rounded-2xl border-gray-300 focus:ring-blue-500 focus:border-blue-500 py-3" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Duración -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Duración (minutos)</label>
                        <select name="duration" class="w-full rounded-2xl border-gray-300 py-3">
                            <option value="15">15 min</option>
                            <option value="30" selected>30 min</option>
                            <option value="45">45 min</option>
                            <option value="60">60 min</option>
                        </select>
                    </div>

                    <!-- Tipo -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Modalidad</label>
                        <select name="type" x-model="type" class="w-full rounded-2xl border-gray-300 py-3">
                            <option value="presencial">Presencial (En Sede)</option>
                            <option value="virtual">Virtual (Telemedicina)</option>
                        </select>
                    </div>
                </div>

                <!-- Selección de Sedes (Dinámica) -->
                <div x-show="type === 'presencial'" x-transition:enter="transition ease-out duration-300"
                    class="p-6 bg-blue-50 rounded-3xl border border-blue-100">
                    <label class="block text-sm font-bold text-blue-900 mb-4">¿En qué sedes ofreces este servicio?</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($addresses as $address)
                            <label class="flex items-center p-3 bg-white rounded-xl border border-blue-200 cursor-pointer hover:bg-blue-100 transition">
                                <input type="checkbox" name="address_ids[]" value="{{ $address->id }}" class="rounded text-blue-600 focus:ring-blue-500">
                                <div class="ml-3">
                                    <span class="block text-sm font-bold text-gray-800">{{ $address->name }}</span>
                                    <span class="block text-xs text-gray-500">{{ $address->address }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('address_ids')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div x-show="type === 'virtual'" 
                     x-transition:enter="transition ease-out duration-300"
                     class="p-4 bg-purple-50 rounded-2xl border border-purple-100 flex items-center gap-3">
                    <span class="text-2xl">✨</span>
                    <p class="text-sm text-purple-800 font-medium">
                        Este servicio se realizará por videollamada. No requiere vincularse a una dirección física.
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
