@php
$breadcrumbs = [
    [
        'name' => 'Doctor',
        'href' => route('partner.addresses.index'),
    ],
    [
        'name' => 'Consultorio',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-2xl mx-auto">
        <div class="p-8">
            <form action="{{ route('partner.addresses.store') }}" method="POST" class="space-y-6">
                @csrf
                
                <div>
                    <!-- Nombre -->
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">Nombre de la sede</label>
                    <input type="text" name="name" id="name" placeholder="Ej: Edificio de colores - Consultorio 112"
                        class="block w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" 
                        required>                    
                </div>

                <div>
                    <label class="block font-bold text-gray-700">Ciudad</label>
                    <select name="city_id" class="w-full rounded-xl border-gray-300" required>
                        <option value="">Selecciona una ciudad</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}">{{ $city->name }}</option>
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
                        <input type="text" name="address" id="address" 
                            class="block w-full pl-10 pr-3 py-3 border @error('address') border-red-500 @else border-gray-300 @enderror rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" 
                            required>
                    </div>
                </div>

                <div>
                    <!-- Phone -->
                    <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1">Teléfono</label>
                    <input type="phone" name="phone" id="phone" 
                        class="block w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" 
                            required>                    
                </div>                        

                <!-- Divisor -->
                <div class="pt-4 border-t border-gray-100 mt-6">
                    <div class="flex gap-4">
                        <button type="submit" 
                            class="w-2/3 flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                            Nueva sede
                        </button>
                        <a href="{{ route('partner.addresses.index') }}" 
                            class="w-1/3 text-center px-4 py-3 border border-gray-300 font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-200">
                            Cancelar
                        </a>                            
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>