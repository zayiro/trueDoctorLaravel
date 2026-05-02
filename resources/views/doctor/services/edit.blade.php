@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Editar servicio',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-6xl mx-auto py-10 px-4">
        <div x-data="{ type: '{{ $service->type }}' }" class="bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-100">
            
            <form action="{{ route('doctor.services.update', $service->id) }}" method="POST" class="p-8 space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nombre del Servicio</label>
                        <input type="text" name="name" value="{{ old('name', $service->name) }}" 
                               class="w-full rounded-2xl border-gray-300 py-3" required>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Precio ($)</label>
                        <input type="number" name="price" step="0.01" value="{{ old('price', $service->price) }}" 
                               class="w-full rounded-2xl border-gray-300 py-3" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Duración</label>
                        <select name="duration" class="w-full rounded-2xl border-gray-300 py-3">
                            @foreach([15, 30, 45, 60] as $t)
                                <option value="{{ $t }}" {{ $service->duration == $t ? 'selected' : '' }}>{{ $t }} min</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Modalidad</label>
                        <select name="type" x-model="type" class="w-full rounded-2xl border-gray-300 py-3">
                            <option value="presencial">Presencial</option>
                            <option value="virtual">Virtual</option>
                        </select>
                    </div>
                </div>

                <!-- Sedes con checkboxes pre-marcados -->
                <div x-show="type === 'presencial'" class="p-6 bg-blue-50 rounded-3xl border border-blue-100">
                    <label class="block text-sm font-bold text-blue-900 mb-4">Sedes donde se ofrece:</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($addresses as $address)
                            <label class="flex items-center p-3 bg-white rounded-xl border border-blue-200 cursor-pointer">
                                <input type="checkbox" name="address_ids[]" value="{{ $address->id }}" 
                                    class="rounded text-blue-600"
                                    {{ $service->addresses->contains($address->id) ? 'checked' : '' }}>
                                <span class="ml-3 text-sm font-bold text-gray-800">{{ $address->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="pt-4 flex gap-4">
                    <button type="submit" class="flex-1 bg-blue-600 text-white font-black py-4 rounded-2xl shadow-lg">
                        ACTUALIZAR SERVICIO
                    </button>
                    <a href="{{ route('doctor.services.index') }}" class="px-8 py-4 bg-gray-100 text-gray-600 rounded-2xl font-bold">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
