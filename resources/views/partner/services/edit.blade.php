@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Portafolio de Servicios', 'href' => route('partner.services.index')],
    ['name' => 'Editar servicio'],
];
$currentDuration = (int) old('duration', $pivot->duration ?? 20);
$durationOptions = collect([15, 20, 30, 45, 60, $currentDuration])->unique()->sort()->values();
@endphp
<x-admin-layout :breadcrumbs="$breadcrumbs">

<div class="py-10 px-4" x-data="{ loading: false }" @pageshow.window="loading = false">

    <!-- ENCABEZADO -->
    <div class="mb-8 border-b border-slate-100 pb-5">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-indigo-700 bg-indigo-50 rounded-full mb-3 tracking-wide uppercase">
            Edición de Servicio Clínico
        </span>
        <h2 class="text-2xl font-black text-slate-800">Modificar: {{ $service->name }}</h2>
        <p class="text-sm text-slate-500 mt-1">
            Los cambios se aplican <strong>solo en esta sede</strong>
            ({{ $service->type === 'virtual' ? 'Telemedicina' : $address->name }}). Tus otras sedes y los demás profesionales no se ven afectados.
        </p>
    </div>

    <div class="bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-100">
        <form action="{{ route('partner.services.update', $service->id) }}" method="POST" @submit="loading = true" class="p-8 space-y-6">
            @csrf
            @method('PUT')

            <x-validation-errors class="mb-4" />

            <!-- Nombre -->
            <div>
                <label for="name" class="block text-sm font-bold text-gray-700 mb-2">Nombre del Servicio Clínico</label>
                <input type="text" id="name" name="name" value="{{ old('name', $service->name) }}" required maxlength="100"
                       placeholder="Ej: Consulta de Especialista"
                       autocomplete="off"
                       class="w-full rounded-2xl border-gray-300 py-3 focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-400 @enderror">
                @error('name')
                    <p class="mt-2 text-xs text-red-600 font-bold">{{ $message }}</p>
                @enderror
            </div>

            <!-- Duración, modalidad y sede -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="duration" class="block text-sm font-bold text-gray-700 mb-2">Duración de la Cita</label>
                    <select 
                        id="duration" 
                        name="duration" 
                        required
                        class="w-full rounded-2xl border-gray-300 py-3 focus:ring-indigo-500 @error('duration') border-red-400 @enderror"
                    >
                        @foreach($durations as $duration)
                            <option 
                                value="{{ $duration->minutes }}" 
                                {{ old('duration', $currentDuration) == $duration->minutes ? 'selected' : '' }}
                            >
                                {{ $duration->minutes }} min 
                                @if($duration->additional_price > 0)
                                    (+${{ number_format($duration->additional_price, 0, ',', '.') }})
                                @endif
                            </option>
                        @endforeach
                    </select>

                    @error('duration')
                        <p class="mt-2 text-xs text-red-600 font-bold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Modalidad de Atención</label>
                    <div class="w-full rounded-2xl border border-gray-200 bg-gray-50 py-3 px-4 text-sm text-gray-500">
                        {{ $service->type === 'virtual' ? 'Virtual' : 'Presencial' }}
                    </div>
                    <p class="text-xs text-slate-400 mt-1.5 italic">No se puede cambiar después de crear el servicio.</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Sede</label>
                    <div class="w-full rounded-2xl border border-gray-200 bg-gray-50 py-3 px-4 text-sm text-gray-500">
                        {{ $service->type === 'virtual' ? 'Telemedicina' : $address->name }}
                    </div>
                    <p class="text-xs text-slate-400 mt-1.5 italic">Para usarlo en otra sede, créalo allí.</p>
                </div>
            </div>

            <!-- Valor -->
            <div class="p-6 {{ $service->type === 'virtual' ? 'bg-purple-50 border-purple-100' : 'bg-blue-50 border-blue-100' }} rounded-3xl border">
                <label for="price" class="block text-sm font-bold text-gray-800 mb-2">Valor del servicio en esta sede ($)</label>
                <input type="number" id="price" name="price" step="0.01" min="0" required
                       value="{{ old('price', $pivot->price ?? '') }}" placeholder="0.00"
                       class="w-full md:w-1/2 rounded-2xl border-gray-300 py-3 focus:ring-indigo-500 @error('price') border-red-400 @enderror">
                @error('price')
                    <p class="mt-2 text-xs text-red-600 font-bold">{{ $message }}</p>
                @enderror
            </div>

            <!-- Especialidades -->
            <div class="p-6 bg-gray-50 rounded-3xl border border-gray-200 space-y-3">
                <label class="block text-sm font-bold text-gray-800 mb-2">Asociar a Especialidad(es) Médica(s)</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($specialties as $specialty)
                        @php
                            $isChecked = old('specialties')
                                ? in_array($specialty->id, old('specialties', []))
                                : in_array($specialty->id, $attachedSpecialtyIds);
                        @endphp
                        <label class="flex items-center p-3 bg-white rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50 transition">
                            <input type="checkbox" name="specialties[]" value="{{ $specialty->id }}"
                                   class="rounded text-indigo-600 focus:ring-indigo-500" {{ $isChecked ? 'checked' : '' }}>
                            <span class="ml-3 text-sm font-medium text-gray-700">{{ $specialty->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('specialties')
                    <p class="mt-2 text-xs text-red-600 font-bold">{{ $message }}</p>
                @enderror
            </div>

            <!-- Botones -->
            <div class="pt-4 flex gap-4 border-t border-slate-100">
                <button type="submit" :disabled="loading" :class="loading ? 'opacity-70 cursor-not-allowed' : ''"
                        class="flex-1 bg-blue-600 text-white font-black py-4 rounded-2xl shadow-lg hover:bg-blue-700 transition tracking-wide uppercase text-sm">
                    <span x-text="loading ? 'Guardando cambios...' : 'Actualizar Servicio'"></span>
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