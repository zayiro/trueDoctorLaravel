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
        'name' => 'Registrar Sede',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-2xl mx-auto py-10 px-4">
        <div class="bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-100 p-8">
            
            <form action="{{ route('partner.addresses.store') }}" method="POST" class="space-y-6">
                @csrf
                
                <!-- Mensajes de Error Globales -->
                <x-validation-errors class="mb-4" />

                <!-- Nombre de la Sede -->
                <div>
                    <label for="name" class="block text-sm font-bold text-gray-700 mb-2">Nombre de la Sede</label>
                    <input type="text" name="name" id="name" placeholder="Ej: Consultorio 112 - Edificio de Colores"
                        class="block w-full px-4 py-3 border border-gray-300 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" 
                        value="{{ old('name') }}" required autofocus>                    
                </div>

                <!-- Selector de Ciudad -->
                <div>
                    <label for="city_id" class="block text-sm font-bold text-gray-700 mb-2">Ciudad / Municipio</label>
                    <select name="city_id" id="city_id" class="w-full rounded-2xl border-gray-300 py-3 focus:ring-blue-500" required>
                        <option value="">Selecciona una ciudad</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}" {{ old('city_id') === $city->id ? 'selected' : '' }}>
                                {{ $city->name }}
                            </option>
                        @endforeach
                    </select>
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
                        <input type="text" name="address" id="address" placeholder="Ej: Av. Calle 100 # 15-22, Torre B"
                            class="block w-full pl-10 pr-3 py-3 border @error('address') border-red-500 @else border-gray-300 @enderror rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" 
                            value="{{ old('address') }}" required>
                    </div>
                </div>

                <!-- Teléfono de Contacto -->
                <div>
                    <label for="phone" class="block text-sm font-bold text-gray-700 mb-2">Teléfono o Celular de la Sede</label>
                    <input type="tel" name="phone" id="phone" placeholder="Ej: 3026433874"
                        class="block w-full px-4 py-3 border border-gray-300 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" 
                        value="{{ old('phone') }}" required>                    
                </div>                        

                <!-- Botones de Acción -->
                <div class="pt-6 border-t border-gray-100 flex flex-col sm:flex-row gap-4 mt-8">
                    <button type="submit" 
                        class="flex-[2] bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-2xl shadow-md transition duration-200 tracking-wide uppercase text-sm">
                        Registrar Nueva Sede
                    </button>
                    <a href="{{ route('partner.addresses.index') }}" 
                        class="flex-1 text-center px-6 py-3 border border-gray-300 font-bold rounded-2xl text-gray-600 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-200 text-sm flex items-center justify-center">
                        Cancelar
                    </a>                            
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
