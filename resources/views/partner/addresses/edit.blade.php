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
    {{-- Inyectamos los IDs actuales de la BD o de la sesión (old) para la hidratación reactiva --}}
    <div class="max-w-7xl mx-auto py-10 px-4"  
         x-data="{ 
            loading: false, 
            departmentId: '{{ old('department_id', $address->city?->department_id) }}', 
            selectedCityId: '{{ old('city_id', $address->city_id) }}',
            cities: [],
            
            init() {
                // Al arrancar Alpine, si hay un departamento precargado, dispara el fetch de inmediato
                if (this.departmentId) {
                    this.fetchCities();
                }
            },
            fetchCities() {
                if (!this.departmentId) { this.cities = []; return; }
                fetch(`/departments/${this.departmentId}/cities`)
                    .then(res => res.json())
                    .then(data => { this.cities = data; });
            }
         }">
        <!-- ENCABEZADO ESTILIZADO DE NIVEL SAAS -->
        <div class="mb-8 border-b border-slate-100 pb-5">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-indigo-700 bg-indigo-50 rounded-full mb-3 tracking-wide uppercase">
                <x-heroicon-o-pencil-square class="w-3.5 h-3.5" />
                Infraestructura Corporativa
            </span>
            <h2 class="text-2xl font-black text-slate-800">Editar Consultorio</h2>
            <p class="text-sm text-slate-500 mt-1">
                Modifica los datos de localización o contacto de esta sede física dentro de la red de OpenDoctor.
            </p>
        </div>

        <!-- TARJETA DEL FORMULARIO PRINCIPAL CON UX PREMIUM -->
        <div class="bg-white border border-slate-200 rounded-[2.5rem] p-6 md:p-8 shadow-sm">
            
            <form action="{{ route('partner.addresses.update', $address) }}" method="POST" @submit="loading = true" class="space-y-5">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex flex-col">
                        <x-label value="Nombre de la sede / Sucursal" class="mb-1 text-slate-500 font-bold text-xs" />
                        <input type="text" name="name" value="{{ old('name', $address->name) }}" required placeholder="Ej: Consultorios Norte" class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 shadow-inner">
                    </div>

                    <div class="flex flex-col">
                        <x-label value="Dirección" class="mb-1 text-slate-500 font-bold text-xs" />
                        <input type="text" name="address" value="{{ old('address', $address->address) }}" required placeholder="Ej: Av. Calle 100 # 15-22, Torre B" class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 shadow-inner">
                    </div>

                    <div class="flex flex-col">
                        <x-label value="Teléfono de contacto de la sede" class="mb-1 text-slate-500 font-bold text-xs" />
                        <input type="text" name="phone" value="{{ old('phone', $address->phone) }}" placeholder="Ej: 3026433874" class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 shadow-inner">
                    </div>
                </div>

                <!-- Fila 2: Selector de Departamento y Municipio con marcaje dinámico -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">                    
                    <div class="flex flex-col">
                        <x-label value="Departamento" class="mb-1 text-slate-500 font-bold text-xs" />
                        <select x-model="departmentId" @change="fetchCities(); selectedCityId = ''" required class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm text-slate-500 bg-white focus:ring-2 focus:ring-indigo-500 shadow-inner">
                            <option value="" selected disabled>Selecciona el departamento</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col">
                        <x-label value="Municipio / Ciudad" class="mb-1 text-slate-500 font-bold text-xs" />
                        {{-- Sincronizamos el select completo con el ID guardado --}}
                        <select name="city_id" x-model="selectedCityId" required :disabled="cities.length === 0" class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm text-slate-500 bg-white focus:ring-2 focus:ring-indigo-500 shadow-inner disabled:bg-slate-50 disabled:cursor-not-allowed">
                            <option value="" selected disabled x-text="cities.length === 0 ? 'Cargando municipios...' : 'Selecciona el municipio'"></option>
                            <template x-for="city in cities" :key="city.id">
                                {{-- Forzamos la selección comparando con selectedCityId --}}
                                <option :value="city.id" :selected="city.id == selectedCityId" x-text="city.name"></option>
                            </template>
                        </select>
                    </div>
                </div>
                <!-- BOTONERA DE ACCIÓN MANTENIENDO TU ESTRUCTURA ORIGINAL -->
                <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-4 border-t border-slate-100 pt-5 mt-6">
                    <a href="{{ route('partner.addresses.index') }}" 
                        class="w-full sm:w-auto text-center px-6 py-3.5 border border-slate-200 font-bold rounded-2xl text-slate-600 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition duration-150 text-xs uppercase tracking-wider">
                        Cancelar
                    </a>

                    <button type="submit" :disabled="loading"
                        class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 px-8 rounded-2xl shadow-md transition duration-150 tracking-wider uppercase text-xs flex items-center justify-center gap-2">
                        <svg x-show="loading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" x-cloak>
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="loading ? 'Guardando...' : 'Guardar Cambios'"></span>
                    </button>                            
                </div>
            </form>
        </div>        
    </div>
</x-admin-layout>
