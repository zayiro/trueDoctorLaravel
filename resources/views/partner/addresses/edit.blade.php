@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Consultorios',
        'href' => route('partner.addresses.index'),
    ],
    [
        'name' => 'Editar Consultorio',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">        
    <div class="max-w-2xl mx-auto py-10 px-4">
        <div class="bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-100 p-8">
            
            <form action="{{ route('partner.addresses.update', $address) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Nombre del Consultorio -->
                <div>
                    <label for="name" class="block text-sm font-bold text-gray-700 mb-2">Nombre de la Sede</label>
                    <input type="text" name="name" id="name" autocomplete="name"
                        class="block w-full px-4 py-3 border border-gray-300 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" 
                        value="{{ old('name', $address->name) }}" required placeholder="Ej: Consultorio 402, Sede Norte">
                    <x-input-error for="name" class="mt-2 text-xs font-semibold" />
                </div>

                <!-- Selector de Ciudad -->
                <div>
                    <label for="city_id" class="block text-sm font-bold text-gray-700 mb-2">Ciudad / Municipio</label>
                    <select name="city_id" id="city_id" class="w-full rounded-2xl border-gray-300 py-3 focus:ring-blue-500" required>
                        <option value="">Selecciona una ciudad</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}" {{ old('city_id', $address->city_id) === $city->id ? 'selected' : '' }}>
                                {{ $city->name }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error for="city_id" class="mt-2 text-xs font-semibold" />
                </div>
                <!-- Dirección Física -->
                <div>
                    <label for="address" class="block text-sm font-bold text-gray-700 mb-2">Dirección Física Completa</label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <input type="text" name="address" id="address" autocomplete="address" 
                            class="block w-full pl-10 pr-3 py-3 border @error('address') border-red-500 @else border-gray-300 @enderror rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" 
                            value="{{ old('address', $address->address) }}" required placeholder="Ej: Av. Calle 100 # 15-22, Oficina 301">
                    </div>
                    @error('address')
                        <p class="mt-2 text-xs text-red-600 font-semibold">{{ $message }}</p>
                    @enderror                                      
                </div>

                <!-- Teléfono de Contacto -->
                <div>
                    <label for="phone" class="block text-sm font-bold text-gray-700 mb-2">Teléfono o Celular de la Sede</label>
                    <input type="text" name="phone" id="phone" autocomplete="phone" 
                        class="block w-full px-4 py-3 border border-gray-300 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" 
                        value="{{ old('phone', $address->phone) }}" required placeholder="Ej: 3001234567">
                    <x-input-error for="phone" class="mt-2 text-xs font-semibold" />
                </div>                        

                <!-- Botones de Acción -->
                <div class="pt-6 border-t border-gray-100 flex flex-col sm:flex-row gap-4 mt-8">
                    <button type="submit" 
                        class="flex-1 bg-blue-600 text-white font-bold py-3 px-6 rounded-2xl shadow-md hover:bg-blue-700 transition duration-200 tracking-wide uppercase text-sm">
                        Guardar Cambios
                    </button>
                    <a href="{{ route('partner.addresses.index') }}" 
                        class="px-6 py-3 border border-gray-300 rounded-2xl text-gray-600 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-200 text-sm font-bold flex items-center justify-center">
                        Cancelar
                    </a>                            
                </div>
            </form>
        </div>        
    </div>
</x-admin-layout>
