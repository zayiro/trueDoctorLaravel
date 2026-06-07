@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Sedes Institucionales']
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    {{-- Contenedor con Alpine.js para la inyección de carga y peticiones dinámicas de municipios --}}
    <div class="max-w-6xl mx-auto py-8 px-4" 
         x-data="{ 
            loading: false, 
            departmentId: '', 
            cities: [],
            fetchCities() {
                if (!this.departmentId) { this.cities = []; return; }
                fetch(`/departments/${this.departmentId}/cities`)
                    .then(res => res.json())
                    .then(data => { this.cities = data; });
            }
         }">
        
        <!-- ALERTAS TRADICIONALES -->
        @if (session('success'))
            <div id="alert-success" class="flex items-center p-4 mb-6 text-green-800 rounded-2xl bg-green-50 border border-green-100 shadow-sm" role="alert">
                <x-heroicon-s-check-circle class="flex-shrink-0 w-5 h-5 text-green-500" />
                <div class="ms-3 text-sm font-bold">{{ session('success') }}</div>
                <button type="button" class="ms-auto bg-green-50 text-green-500 rounded-lg p-1.5 hover:bg-green-100 inline-flex items-center justify-center h-8 w-8" onclick="document.getElementById('alert-success').remove()">
                    <span class="text-xl">&times;</span>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div id="alert-error" class="flex items-center p-4 mb-6 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm" role="alert">
                <x-heroicon-s-x-circle class="flex-shrink-0 w-5 h-5 text-red-500" />
                <div class="ms-3 text-sm font-bold">{{ session('error') }}</div>
                <button type="button" class="ms-auto bg-red-50 text-red-500 rounded-lg p-1.5 hover:bg-red-100 inline-flex items-center justify-center h-8 w-8" onclick="document.getElementById('alert-error').remove()">
                    <span class="text-xl">&times;</span>
                </button>
            </div>
        @endif

        <!-- FORMULARIO DE ALTA DE SEDE (DISEÑO PREMIUM SAAS) -->
        <div class="bg-white border rounded-[2.5rem] p-6 md:p-8 shadow-sm border-slate-100 mb-8">
            <div class="flex items-center gap-3 mb-6 border-b border-slate-50 pb-4">
                <div class="p-2 bg-indigo-500 text-white rounded-xl shadow-md shadow-indigo-100">
                    <x-heroicon-o-map-pin class="w-5 h-5" />
                </div>
                <div>
                    <h3 class="text-xl font-black text-slate-800 tracking-tight">Registrar Nueva Sede</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Añade consultorios físicos o salas virtuales respetando los límites de tu suscripción.</p>
                </div>
            </div>
            <form action="{{ route('partner.clinic.addresses.store') }}" method="POST" @submit="loading = true" class="space-y-4">
                @csrf
                
                <!-- Fila 1: Nombre de la sede, Dirección, Tipo de modalidad -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex flex-col">
                        <x-label value="Nombre de la sede / Sucursal" class="mb-1 text-slate-500 font-bold text-xs" />
                        <input type="text" name="name" required placeholder="Ej: Consultorios Norte" class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 shadow-inner">
                    </div>

                    <div class="flex flex-col">
                        <x-label value="Dirección" class="mb-1 text-slate-500 font-bold text-xs" />
                        <input type="text" name="address" required placeholder="Ej: Av. Calle 100 # 15-22, Torre B" class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 shadow-inner">
                    </div>

                    <div class="flex flex-col">
                        <x-label value="Teléfono de contacto de la sede" class="mb-1 text-slate-500 font-bold text-xs" />
                        <input type="text" name="phone" placeholder="Ej: 3026433874" class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 shadow-inner">
                    </div>
                </div>

                <!-- Fila 2: Teléfono, Departamento y Municipio (Filtrado con Alpine) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">                    
                    <div class="flex flex-col">
                        <x-label value="Departamento" class="mb-1 text-slate-500 font-bold text-xs" />
                        <select x-model="departmentId" @change="fetchCities()" required class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm text-slate-500 bg-white focus:ring-2 focus:ring-indigo-500 shadow-inner">
                            <option value="" selected disabled>Selecciona el departamento</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col">
                        <x-label value="Municipio / Ciudad" class="mb-1 text-slate-500 font-bold text-xs" />
                        <select name="city_id" required :disabled="cities.length === 0" class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm text-slate-500 bg-white focus:ring-2 focus:ring-indigo-500 shadow-inner disabled:bg-slate-50 disabled:cursor-not-allowed">
                            <option value="" selected disabled x-text="cities.length === 0 ? 'Elige primero un departamento' : 'Selecciona el municipio'"></option>
                            <template x-for="city in cities" :key="city.id">
                                <option :value="city.id" x-text="city.name"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <!-- Botón de Envío -->
                <div class="flex justify-end pt-2">
                    <button type="submit" :disabled="loading" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black px-8 py-4 rounded-2xl text-xs uppercase tracking-wider shadow-lg shadow-indigo-100 transition-all flex items-center justify-center gap-2 w-full sm:w-auto">
                        <svg x-show="loading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" x-cloak><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span x-text="loading ? 'Guardando sede...' : 'Registrar Sede'"></span>
                    </button>
                </div>
            </form>
        </div>
        <!-- LISTADO DE SEDES ACTUALES -->
        <div class="space-y-4">
            <div class="flex items-center justify-between mb-4 px-2">
                <h4 class="text-xs font-black uppercase text-slate-400 tracking-wider">Infraestructura Médica Actual</h4>
                <span class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-1 rounded-full">
                    {{ $addresses->count() }} Sede(s) Instalada(s)
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($addresses as $addr)
                    <div class="bg-white border rounded-[2rem] p-5 shadow-sm border-slate-100 flex flex-col justify-between gap-4 transition-all hover:shadow-md">
                        <div class="flex items-start gap-4">
                            <div class="p-3 rounded-2xl {{ $addr->type === 'virtual' ? 'bg-blue-50 text-blue-600' : 'bg-emerald-50 text-emerald-600' }} shadow-xs flex-shrink-0">
                                @if($addr->type === 'virtual')
                                    <x-heroicon-o-video-camera class="w-6 h-6" />
                                @else
                                    <x-heroicon-o-building-office class="w-6 h-6" />
                                @endif
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <h4 class="text-base font-extrabold text-slate-800 truncate leading-snug">{{ $addr->name }}</h4>
                                <span class="text-[11px] text-slate-500 block font-medium mt-0.5">{{ $addr->address }}</span>
                                <span class="text-[11px] text-indigo-600 font-bold block mt-0.5">{{ $addr->city->name }} • {{ $addr->city->department->name }}</span>
                                
                                @if($addr->phone)
                                    <span class="text-[11px] text-slate-400 block mt-1">Teléfono: {{ $addr->phone }}</span>
                                @endif
                            </div>
                        </div>
                        <!-- Barra Inferior: Estado y Acciones Operativas -->
                        <div class="flex items-center justify-between border-t border-slate-50 pt-4 mt-1">
                            <div>
                                @if($addr->status)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>Operativa
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>Pausada
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-1.5">
                                <!-- Interruptor Activar/Desactivar Sede -->
                                <form action="{{ route('partner.clinic.addresses.toggle', $addr) }}" method="POST" class="inline">
                                    @csrf 
                                    @method('PATCH')
                                    <button type="submit" class="inline-flex items-center justify-center px-3 py-2 border rounded-xl text-xs font-bold transition-all outline-none {{ $addr->status ? 'border-amber-100 text-amber-700 bg-amber-50 hover:bg-amber-100' : 'border-emerald-100 text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }}">
                                        {{ $addr->status ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>

                                <!-- Dar de baja (SoftDeletes) - OCULTO SI ES SEDE VIRTUAL -->
                                @if($addr->type !== 'virtual')
                                    <form action="{{ route('partner.clinic.addresses.destroy', $addr) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de dar de baja esta sede de la infraestructura institucional? Esta acción suspenderá su disponibilidad en la agenda.');">
                                        @csrf 
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center justify-center p-2 rounded-xl text-xs font-bold border border-rose-100 text-rose-700 bg-rose-50 hover:bg-rose-100 transition-colors outline-none">
                                            <x-heroicon-o-trash class="w-4 h-4" />
                                        </button>
                                    </form>
                                @endif
                            </div>

                        </div>
                    </div>
                @empty
                    <div class="col-span-1 md:col-span-2 bg-white border border-dashed border-slate-200 rounded-[2rem] p-12 text-center text-slate-400">
                        <p class="text-sm font-medium">La institución médica no tiene sedes registradas bajo este plan actualmente.</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div> {{-- Cierre de x-data Alpine.js principal --}}
</x-admin-layout>
