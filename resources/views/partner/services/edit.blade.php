@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Portafolio de Servicios', 'href' => route('partner.services.index')],
    ['name' => 'Editar servicio'],
];
@endphp
<x-admin-layout :breadcrumbs="$breadcrumbs">

<div class="max-w-6xl mx-auto py-10 px-4" x-data="{ 
    type: '{{ $service->type }}',
    loading: false 
}">

    <!-- ENCABEZADO ESTILIZADO -->
    <div class="mb-8 border-b border-slate-100 pb-5">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-indigo-700 bg-indigo-50 rounded-full mb-3 tracking-wide uppercase">
            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
            </svg>
            Edición de Servicio Clínico
        </span>
        <h2 class="text-2xl font-black text-slate-800">Modificar: {{ $service->name }}</h2>
        <p class="text-sm text-slate-500 mt-1">
            Actualiza las especialidades, tarifas y sedes donde se presta este servicio.
        </p>
    </div>

    <!-- TARJETA DEL FORMULARIO -->
    <div class="bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-100">
        <form action="{{ route('partner.services.update', $service->id) }}" method="POST" @submit.prevent="loading = true; $el.closest('form').submit()" class="p-8 space-y-6">
            @csrf
            @method('PUT')

            <!-- Mensajes de Error Globales -->
            <x-validation-errors class="mb-4" />

            <!-- Nombre del Servicio -->
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label for="name" class="block text-sm font-bold text-gray-700 mb-2">Nombre del Servicio Clínico</label>
                    <input type="text" 
                           id="name"
                           name="name" 
                           value="{{ old('name', $service->name) }}" 
                           placeholder="Ej: Consulta de Especialista, Examen de Diagnóstico"
                           autocomplete="organization"
                           class="w-full rounded-2xl border-gray-300 py-3 focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-400 @enderror" 
                           required>
                    @error('name')
                        <p class="mt-2 text-xs text-red-600 font-bold">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Duración y Modalidad -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <div>
                    <label for="duration" class="block text-sm font-bold text-gray-700 mb-2">Duración de la Cita</label>
                    @php
                        $currentDuration = old('duration', $service->addresses->first()?->pivot?->duration ?? 20);
                    @endphp
                    <select id="duration"
                            name="duration" 
                            class="w-full rounded-2xl border-gray-300 py-3 focus:ring-indigo-500 @error('duration') border-red-400 @enderror" 
                            required>
                        @foreach([15, 20, 30, 45, 60] as $time)
                            <option value="{{ $time }}" {{ $currentDuration == $time ? 'selected' : '' }}>{{ $time }} min</option>
                        @endforeach
                    </select>
                    @error('duration')
                        <p class="mt-2 text-xs text-red-600 font-bold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Modalidad de Atención</label>
                    <select class="w-full rounded-2xl border-gray-300 py-3 bg-gray-50 text-gray-500 cursor-not-allowed" disabled>
                        <option value="physical" {{ $service->type === 'physical' ? 'selected' : '' }}>Presencial</option>
                        <option value="virtual" {{ $service->type === 'virtual' ? 'selected' : '' }}>Virtual</option>
                    </select>
                    <p class="text-xs text-slate-400 mt-1.5 italic">La modalidad no se puede cambiar después de crear el servicio.</p>
                </div>
            </div>

            <!-- MÓDULO TAXONÓMICO DE ESPECIALIDADES -->
            <div class="p-6 bg-gray-50 rounded-3xl border border-gray-200 space-y-3 mt-6">
                <label class="block text-sm font-bold text-gray-800 mb-2">Asociar a Especialidad(es) Médica(s)</label>
                <p class="text-xs text-gray-500 mb-4">Modifica las especialidades vinculadas que tienen permitido atender este servicio en la agenda.</p>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($specialties as $specialty)
                        @php
                            $isChecked = old('specialties') 
                                ? in_array($specialty->id, old('specialties', [])) 
                                : in_array($specialty->id, $attachedSpecialtyIds);
                        @endphp
                        <label class="flex items-center p-3 bg-white rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50 transition">
                            <input type="checkbox" 
                                   name="specialties[]" 
                                   value="{{ $specialty->id }}"
                                   class="rounded text-indigo-600 focus:ring-indigo-500"
                                   {{ $isChecked ? 'checked' : '' }}>
                            <span class="ml-3 text-sm font-medium text-gray-700">{{ $specialty->name }}</span>
                        </label>
                    @endforeach
                </div>
                
                @error('specialties')
                    <p class="mt-2 text-xs text-red-600 font-bold">{{ $message }}</p>
                @enderror
            </div>

            <!-- Input Exclusivo para Precio Virtual (solo si es virtual) -->
            <div x-show="type === 'virtual'" x-transition class="p-6 bg-purple-50 rounded-3xl border border-purple-100 space-y-4 mt-6">
                <div>
                    <label for="price_virtual" class="block text-sm font-bold text-purple-900 mb-2">Valor del Servicio Virtual / Videoconsulta ($)</label>
                    <input type="number" 
                           id="price_virtual"
                           name="price_virtual" 
                           step="0.01" 
                           min="0" 
                           value="{{ old('price_virtual', $service->addresses->firstWhere('type', 'virtual')?->pivot?->price ?? '') }}"
                           placeholder="0.00"
                           class="w-full md:w-1/2 rounded-2xl border-purple-300 py-3 focus:ring-purple-500 focus:border-purple-400 @error('price_virtual') border-red-400 @enderror">
                    @error('price_virtual')
                        <p class="mt-2 text-xs text-red-600 font-bold">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Selección de Sedes con Precios Individuales (solo si es físico) -->
            @if($addresses->isNotEmpty())
                <div x-show="type === 'physical'" x-transition class="p-6 bg-blue-50 rounded-3xl border border-blue-100 mt-6">
                    <label class="block text-sm font-bold text-blue-900 mb-4">Sedes donde se ofrece y sus precios:</label>
                    <div class="space-y-3">
                        @foreach($addresses as $address)
                            @php
                                $pivotData = $service->addresses->firstWhere('id', $address->id)?->pivot;
                                $isAssigned = !is_null($pivotData);
                                $shouldBeChecked = old('address_ids') 
                                    ? in_array($address->id, old('address_ids', [])) 
                                    : $isAssigned;
                            @endphp
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-white rounded-xl border border-blue-200 gap-4"
                                 x-data="{ checked: {{ $shouldBeChecked ? 'true' : 'false' }} }">
                                <label class="flex items-center cursor-pointer flex-1">
                                    <input type="checkbox" 
                                           name="address_ids[]" 
                                           value="{{ $address->id }}" 
                                           x-model="checked"
                                           class="rounded text-blue-600 focus:ring-blue-500"
                                           {{ $shouldBeChecked ? 'checked' : '' }}>
                                    <div class="ml-3">
                                        <span class="block text-sm font-bold text-gray-800">{{ $address->name }}</span>
                                        <span class="block text-xs text-gray-500">{{ $address->address }}, {{ $address->city->name ?? 'N/A' }}</span>
                                    </div>
                                </label>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-gray-500">Precio Sede ($)</span>
                                    <input type="number" 
                                           name="prices[{{ $address->id }}]" 
                                           step="0.01" 
                                           min="0" 
                                           value="{{ old('prices.' . $address->id, $pivotData?->price ?? '') }}"
                                           x-bind:disabled="!checked"
                                           class="w-32 rounded-xl border-gray-300 py-2 text-sm focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-400 @error('prices.' . $address->id) border-red-400 @enderror" 
                                           placeholder="0.00">
                                </div>
                                @error('prices.' . $address->id)
                                    <p class="text-xs text-red-600 font-bold mt-1 sm:mt-0 sm:absolute sm:-bottom-5">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                    @error('address_ids')
                        <p class="mt-2 text-xs text-red-600 font-bold">{{ $message }}</p>
                    @enderror
                </div>
            @else
                <div x-show="type === 'physical'" class="p-6 bg-amber-50 rounded-3xl border border-amber-200 mt-6">
                    <p class="text-sm text-amber-700 font-medium">
                        ⚠️ No tienes sedes físicas registradas. 
                        <a href="{{ route('partner.addresses.create') }}" class="underline font-bold">Registra una sede aquí</a> para asignar este servicio.
                    </p>
                </div>
            @endif

            <!-- Mensaje informativo para modalidad virtual -->
            <div x-show="type === 'virtual'" 
                 x-transition
                 class="p-4 bg-purple-50 rounded-2xl border border-purple-100 flex items-start gap-3 mt-6">
                <svg class="w-5 h-5 text-purple-600 mt-0.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.852l.041-.028M12 18.75h.007v.008H12v-.008zM12 3a9 9 0 100 18 9 9 0 000-18z" />
                </svg>
                <p class="text-xs text-purple-800 leading-relaxed">
                    Los servicios virtuales se configuran automáticamente en tu consultorio digital de telemedicina. Al actualizarlo, tus pacientes podrán seguir agendando videoconsultas de forma inmediata.
                </p>
            </div>

            <!-- Botones de Acción -->
            <div class="pt-4 flex gap-4 mt-6 border-t border-slate-100">
                <button type="submit" 
                        :disabled="loading"
                        :class="loading ? 'opacity-70 cursor-not-allowed' : ''"
                        class="flex-1 bg-blue-600 text-white font-black py-4 rounded-2xl shadow-lg hover:bg-blue-700 transition tracking-wide uppercase text-sm flex items-center justify-center gap-2">
                    <svg x-show="loading" x-cloak class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="loading ? 'Guardando cambios...' : 'Actualizar Configuración de Servicio'"></span>
                </button>
                <a href="{{ route('partner.services.index') }}" 
                   onclick="return confirm('¿Seguro que deseas cancelar? Se perderán los cambios no guardados.')"
                   class="px-8 py-4 bg-gray-100 text-gray-600 rounded-2xl font-bold hover:bg-gray-200 transition text-sm flex items-center justify-center">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
</x-admin-layout>