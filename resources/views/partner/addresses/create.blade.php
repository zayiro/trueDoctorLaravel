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
        'name' => 'Sedes fisicas (Consultorios)',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-7xl mx-auto py-10 px-4" x-data="{ loading: false }">
        
        <!-- ENCABEZADO ESTILIZADO DE NIVEL SAAS -->
        <div class="mb-8 border-b border-slate-100 pb-5">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-indigo-700 bg-indigo-50 rounded-full mb-3 tracking-wide uppercase">
                <x-heroicon-o-map-pin class="w-3.5 h-3.5" />
                Control de Infraestructura
            </span>
            <h2 class="text-2xl font-black text-slate-800">Registrar Nueva Sede</h2>
            <p class="text-sm text-slate-500 mt-1">
                Añade un consultorio físico o sucursal médica a la red del ecosistema institucional de OpenDoctor.
            </p>
        </div>

        <!-- TARJETA DEL FORMULARIO PRINCIPAL CON UX PREMIUM -->
        <div class="bg-white border border-slate-200 rounded-[2.5rem] p-6 md:p-8 shadow-sm">
            
            <form action="{{ route('partner.addresses.store') }}" method="POST" @submit="loading = true" class="space-y-5">
                @csrf
                
                <!-- Mensajes de Error Globales -->
                <x-validation-errors class="mb-4" />

                <!-- Fila 1: Nombre de la Sede y Ciudad -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div class="flex flex-col">
                        <x-label for="name" value="Nombre de la sede / Sucursal" class="mb-1 text-slate-500 font-bold text-xs" />
                        <input type="text" name="name" id="name" placeholder="Ej: Consultorio 112 - Edificio Médicos"
                            class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner" 
                            value="{{ old('name') }}" required autofocus>                    
                    </div>

                    <div class="flex flex-col">
                        <x-label for="city_id" value="Ciudad / Municipio" class="mb-1 text-slate-500 font-bold text-xs" />
                        <select name="city_id" id="city_id" class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm text-slate-500 bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner" required>
                            <option value="" disabled selected>Selecciona una ciudad</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}" {{ old('city_id') === $city->id ? 'selected' : '' }}>
                                    {{ $city->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Fila 2: Dirección Física y Teléfono -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div class="flex flex-col">
                        <x-label for="address" value="Dirección Física Completa" class="mb-1 text-slate-500 font-bold text-xs" />
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <x-heroicon-o-map-pin class="h-5 w-5" />
                            </div>
                            <input type="text" name="address" id="address" placeholder="Ej: Av. Calle 100 # 15-22, Torre B"
                                class="w-full pl-11 pr-5 py-4 border @error('address') border-red-400 @else border-slate-200 @enderror rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner" 
                                value="{{ old('address') }}" required>
                        </div>
                    </div>

                    <div class="flex flex-col">
                        <x-label for="phone" value="Teléfono de contacto de la sede" class="mb-1 text-slate-500 font-bold text-xs" />
                        <input type="tel" name="phone" id="phone" placeholder="Ej: 3026433874"
                            class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner" 
                            value="{{ old('phone') }}" required>                    
                    </div>                        
                </div>

                <!-- BOTONERA DE ACCIÓN CON CONTROL DE CARGA ALPINE -->
                <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-4 border-t border-slate-100 pt-5 mt-6">
                    <a href="{{ route('partner.addresses.index') }}" 
                        class="w-full sm:w-auto text-center px-6 py-3.5 border border-slate-200 font-bold rounded-2xl text-slate-600 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition duration-150 text-xs uppercase tracking-wider">
                        Cancelar
                    </a>   
                    @if ($canAddAddress)
                    <button type="submit" :disabled="loading"
                        class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 px-8 rounded-2xl shadow-md transition duration-150 tracking-wider uppercase text-xs flex items-center justify-center gap-2">
                        <!-- Spinner Animado -->
                        <svg x-show="loading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" x-cloak>
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="loading ? 'Procesando...' : 'Registrar Sede'"></span>
                    </button>   
                    @endif                      
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
