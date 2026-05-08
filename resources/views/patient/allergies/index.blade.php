@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Alergias',
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

    <div class="mb-1">
        <div class="md:col-span-2 bg-white rounded-3xl p-6 shadow-sm border border-gray-100 mt-4">            
            <form action="{{ route('patient.allergies.store', $patient->id) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre de la Alergia</label>
                    <input type="text" name="name" placeholder="Ej: Penicilina" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipo</label>
                        <select name="type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="drug">Medicamento</option>
                            <option value="food">Alimento</option>
                            <option value="environment">Entorno</option>
                            <option value="other">Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Severidad</label>
                        <select name="severity" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="mild">Leve</option>
                            <option value="moderate">Moderada</option>
                            <option value="severe">Severa</option>
                        </select>
                    </div>
                </div>
            
                <div>
                    <label class="block text-sm font-medium text-gray-700">Reacción (Opcional)</label>
                    <textarea name="reaction" rows="2" placeholder="Ej: Ronchas y picazón" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>
            
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white py-2 px-4 rounded">
                    Registrar Alergia
                </button>
            </form>
        </div>
    </div>

    <div class="mt-8 bg-white shadow overflow-hidden sm:rounded-lg border border-gray-200">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6 bg-gray-50">
            <h3 class="text-lg leading-6 font-bold text-gray-900 flex items-center">
                <span class="mr-2">📋</span> Mis Alergias Registradas
            </h3>
        </div>
        
        <ul class="divide-y divide-gray-200">
            @forelse($patient->allergies as $allergy)
                @php
                switch ($allergy->type) {
                    case 'drug': $type = 'Medicamento';
                    break;
                    case 'food': $type = 'Comida';
                    break;
                    case 'environment': $type = 'Entorno';
                    break;
                    case 'other': $type = 'Otro';
                    break;
                }

                switch ($allergy->severity) {
                    case 'severe': $severity = 'Leve';
                    break;
                    case 'moderate': $severity = 'Moderada';
                    break;
                    case 'mild': $severity = 'Severa';
                    break;
                }
                @endphp
                <li class="p-4 hover:bg-gray-50 transition">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-indigo-600 uppercase">{{ $type }}</span>
                            <span class="text-lg font-semibold text-gray-800">{{ $allergy->name }}</span>
                        </div>
                        
                        <div class="flex items-center space-x-4 gap-2">
                            {{-- Badge de Severidad --}}
                            <div class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $allergy->severity === 'severe' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $allergy->severity === 'moderate' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $allergy->severity === 'mild' ? 'bg-green-100 text-green-800' : '' }}">
                                {{ strtoupper($severity) }}
                            </div>

                            <div class="text-xs text-gray-400">
                                {{ $allergy->created_at->format('d/m/Y') }}
                            </div>
                        </div>
                    </div>

                    @if($allergy->reaction)
                        <div class="mt-2 text-sm text-gray-600 bg-gray-100 p-2 rounded italic">
                            <strong>Reacción:</strong> {{ $allergy->reaction }}
                        </div>
                    @endif
                    
                    <div class="mt-3 text-end">
                        <form action="{{ route('patient.allergies.destroy', $allergy->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('¿Eliminar esta alergia?')">
                                <p class="text-red-600 dark:text-red-400 text-sm underline">Eliminar</p>
                            </button>
                        </form>
                    </div>
                </li>
            @empty
                <li class="p-8 text-center text-gray-500">
                    <div class="text-4xl mb-2">🍃</div>
                    No tienes alergias registradas en tu historial.
                </li>
            @endforelse
        </ul>
    </div>

</x-admin-layout>
