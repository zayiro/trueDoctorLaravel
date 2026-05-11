@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Historial familiar',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    {{-- Mensajes de Éxito --}}
    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 shadow-sm rounded-r" role="alert">
            <div class="flex items-center">
                <span class="mr-2">✅</span>
                <p class="font-bold">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    {{-- Errores de Validación (Formularios) --}}
    @if ($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 shadow-sm rounded-r" role="alert">
            <div class="flex items-center mb-2">
                <span class="mr-2">❌</span>
                <p class="font-bold">Por favor, corrige los siguientes errores:</p>
            </div>
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="p-6 bg-white shadow-md rounded-lg">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Historial Familiar de: {{ $patient->name }}</h2>

        <!-- Formulario de Registro -->
        <form action="{{ route('patient.family-history.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            @csrf
            <input type="hidden" name="patient_id" value="{{ $patient->id }}">

            <div>
                <label class="block text-sm font-medium text-gray-700">Condición Médica</label>
                <input type="text" name="condition" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Ej: Diabetes Tipo 2" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Parentesco</label>
                <select name="relationship" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">Seleccione uno...</option>
                    <option value="Padre">Padre</option>
                    <option value="Madre">Madre</option>
                    <option value="Abuelo/a">Abuelo/a</option>
                    <option value="Hermano/a">Hermano/a</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Notas Adicionales</label>
                <textarea name="notes" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Opcional: edad de diagnóstico, tratamiento..."></textarea>
            </div>

            <div class="md:col-span-2">
                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition">
                    Guardar Antecedente
                </button>
            </div>
        </form>

        <hr class="my-8">

        <!-- Listado de Antecedentes -->
        <h3 class="text-lg font-semibold mb-4 text-gray-700">Registros Existentes</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Condición</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parentesco</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($patient->familyHistories as $history)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $history->condition }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $history->relationship }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $history->notes ?? '-' }}</td>
                       <td>
                        <form action="{{ route('patient.family-history.destroy', $history->id) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    onclick="return confirm('¿Eliminar este antecedente familiar?')"
                                    class="text-red-600 hover:text-red-800 text-sm font-bold flex items-center gap-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </form>
                       </td> 
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
