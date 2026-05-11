@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Editar Cirugia',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <form action="{{ route('patient.surgeries.update', $surgery->id) }}" method="POST" class="space-y-6 bg-white p-8 rounded-xl shadow-sm border">
        @csrf
        @method('PUT') <!-- OBLIGATORIO PARA EDITAR -->

        <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Nombre de la Cirugía</label>
            <input type="text" name="name" value="{{ old('name', $surgery->name) }}" class="w-full border-gray-300 rounded-lg">
        </div>

        <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Año</label>
            <input type="number" name="year" value="{{ old('year', $surgery->year) }}" class="w-full border-gray-300 rounded-lg">
        </div>

        <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Observaciones</label>
            <textarea name="observations" rows="3" class="w-full border-gray-300 rounded-lg">{{ old('observations', $surgery->observations) }}</textarea>
        </div>

        <div class="p-4 bg-red-50 rounded-lg border border-red-100">
            <label class="inline-flex items-center">
                <input type="checkbox" name="anesthesia_complications" value="1" 
                    {{ old('anesthesia_complications', $surgery->anesthesia_complications) ? 'checked' : '' }}
                    onchange="document.getElementById('details').classList.toggle('hidden', !this.checked)"
                    class="rounded border-gray-300 text-red-600">
                <span class="ml-2 text-sm font-bold text-red-700">¿Hubo complicaciones con anestesia?</span>
            </label>

            <div id="details" class="{{ old('anesthesia_complications', $surgery->anesthesia_complications) ? '' : 'hidden' }} mt-4">
                <label class="block text-xs font-bold text-red-400 uppercase mb-1">Detalles de la complicación</label>
                <textarea name="anesthesia_details" class="w-full border-red-200 rounded-lg text-sm">{{ old('anesthesia_details', $surgery->anesthesia_details) }}</textarea>
            </div>
        </div>

        <div class="flex justify-end gap-4">
            <a href="{{ route('patient.surgeries.index') }}" class="px-4 py-2 text-gray-500 hover:text-gray-700">Cancelar</a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold">Guardar Cambios</button>
        </div>
    </form>
</x-admin-layout>    
