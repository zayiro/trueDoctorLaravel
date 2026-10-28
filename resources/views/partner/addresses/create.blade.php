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

    <!-- 🔒 BLOQUEO TEMPRANO SI NO PUEDE AGREGAR MÁS SEDES -->
    @if (!$canAddAddress)
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 mb-6 flex items-start gap-4">
            <svg class="w-6 h-6 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <h3 class="font-bold text-amber-900 text-sm">Límite de sedes alcanzado</h3>
                <p class="text-sm text-amber-700 mt-1">
                    Tu plan actual no te permite crear más sedes. 
                    <a href="{{ route('partner.profile.edit') }}" class="underline font-bold hover:text-amber-900">Mejora tu plan aquí</a> para desbloquear esta funcionalidad.
                </p>
            </div>
        </div>
    @else
        <!-- TARJETA DEL FORMULARIO PRINCIPAL CON UX PREMIUM -->
        <div class="bg-white border border-slate-200 rounded-[2.5rem] p-6 md:p-8 shadow-sm">
            <form action="{{ route('partner.addresses.store') }}" method="POST" @submit.prevent="loading = true; $el.closest('form').submit()" class="space-y-5">
                @csrf

                <!-- Mensajes de Error Globales -->
                <x-validation-errors class="mb-4" />

                <!-- Fila 1: Nombre de la Sede y Ciudad -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div class="flex flex-col">
                        <x-label for="name" value="Nombre de la sede / Sucursal" class="mb-1 text-slate-500 font-bold text-xs" />
                        <input type="text" 
                               name="name" 
                               id="name" 
                               placeholder="Ej: Consultorio 112 - Edificio Médicos"
                               autocomplete="organization"
                               class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner @error('name') border-red-400 @enderror" 
                               value="{{ old('name') }}" 
                               required 
                               autofocus>
                        @error('name')
                            <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex flex-col">
                        <x-label for="city_id" value="Ciudad / Municipio" class="mb-1 text-slate-500 font-bold text-xs" />
                        <select name="city_id" 
                                id="city_id" 
                                class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm text-slate-500 bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner @error('city_id') border-red-400 @enderror" 
                                required>
                            <option value="" disabled selected>Selecciona una ciudad</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}" {{ old('city_id') === (string) $city->id ? 'selected' : '' }}>
                                    {{ $city->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('city_id')
                            <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
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
                            <input type="text" 
                                   name="address" 
                                   id="address" 
                                   placeholder="Ej: Av. Calle 100 # 15-22, Torre B"
                                   autocomplete="street-address"
                                   class="w-full pl-11 pr-5 py-4 border @error('address') border-red-400 @else border-slate-200 @enderror rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner" 
                                   value="{{ old('address') }}" 
                                   required>
                        </div>
                        @error('address')
                            <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex flex-col">
                        <x-label for="phone" value="Teléfono de contacto de la sede (Opcional)" class="mb-1 text-slate-500 font-bold text-xs" />
                        <input type="tel" 
                               name="phone" 
                               id="phone" 
                               placeholder="Ej: 3026433874"
                               autocomplete="tel"
                               pattern="[0-9+\s\-()]+"
                               title="Solo números, espacios, guiones y paréntesis"
                               class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner @error('phone') border-red-400 @enderror" 
                               value="{{ old('phone') }}">
                        <p class="text-[10px] text-slate-400 mt-1">Opcional. Solo números, máximo 20 caracteres.</p>
                        @error('phone')
                            <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>                        
                </div>

                <!-- BOTONERA DE ACCIÓN CON CONTROL DE CARGA ALPINE -->
                <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-4 border-t border-slate-100 pt-5 mt-6">
                    <a href="{{ route('partner.addresses.index') }}" 
                       onclick="return confirm('¿Seguro que deseas cancelar? Se perderán los datos no guardados.')"
                       class="w-full sm:w-auto text-center px-6 py-3.5 border border-slate-200 font-bold rounded-2xl text-slate-600 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition duration-150 text-xs uppercase tracking-wider">
                        Cancelar
                    </a>   
                    <button type="submit" 
                            :disabled="loading"
                            :class="loading ? 'opacity-70 cursor-not-allowed' : ''"
                            class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 px-8 rounded-2xl shadow-md transition duration-150 tracking-wider uppercase text-xs flex items-center justify-center gap-2">
                        <!-- Spinner Animado -->
                        <svg x-show="loading" x-cloak class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="loading ? 'Procesando...' : 'Registrar Sede'"></span>
                    </button>                      
                </div>
            </form>
        </div>
    @endif
</div>
</x-admin-layout>