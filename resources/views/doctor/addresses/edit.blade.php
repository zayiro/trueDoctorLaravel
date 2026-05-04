@php
$breadcrumbs = [
    [
        'name' => 'Doctor',
        'href' => route('doctor.addresses.index'),
    ],
    [
        'name' => 'Editar Consultorio',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">        
    <div class="max-w-2xl mx-auto">
        <div class="p-8">
            <form action="{{ route('doctor.addresses.update', $address) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <!-- Nombre -->
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">Nombre</label>
                    <input type="text" name="name" id="name" autocomplete="name"
                        class="block w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" 
                        value="{{ old('name', $address->name) }}" required>
                    <x-input-error for="name" class="mt-2" />
                </div>

                <div>
                    <label class="block font-bold text-gray-700">Ciudad</label>
                    <select name="city_id" class="w-full rounded-xl border-gray-300" required>
                        <option value="">Selecciona una ciudad</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}" {{ $address->city_id === $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Dirección -->
                <div>
                    <label for="address" class="block text-sm font-semibold text-gray-700 mb-1">Dirección</label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <input type="text" name="address" id="address" autocomplete="address" 
                            class="block w-full pl-10 pr-3 py-3 border @error('address') border-red-500 @else border-gray-300 @enderror rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" 
                            value="{{ old('address', $address->address) }}" required>
                    </div>
                    @error('address')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror                                      
                </div>

                <div>
                    <!-- Phone -->
                    <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1">Teléfono</label>
                    <input type="text" name="phone" id="phone" autocomplete="phone" 
                        class="block w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" 
                        value="{{ old('phone', $address->phone) }}" required>
                    
                </div>                        

                <!-- Divisor -->
                <div class="pt-4 border-t border-gray-100 mt-6">
                    <div>
                        <button type="submit" 
                            class="bg-blue-500 hover:bg-blue-700 text-white py-2 px-4 rounded">
                            Guardar Cambios
                        </button>
                        <a href="{{ route('doctor.addresses.index') }}" 
                            class="w-1/3 text-center px-4 py-3 border border-gray-300 rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-200">
                            Cancelar
                        </a>                            
                    </div>
                </div>
            </form>
        </div>        
    </div>
</x-admin-layout>