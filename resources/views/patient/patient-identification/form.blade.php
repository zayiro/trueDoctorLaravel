@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Datos clínicos',
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

    <div class="py-8">
        <h2 class="text-2xl font-bold mb-6">{{ isset($patient) ? 'Editar Paciente' : 'Nuevo Paciente' }}</h2>

        <form action="{{ isset($patient) ? route('patients.update', $patient) : route('patients.store') }}" method="POST">
            @csrf
            @if(isset($patient)) @method('PUT') @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Identificación -->
                <div>
                    <label class="block text-sm font-medium">Identificación</label>
                    <input type="text" name="identification" value="{{ $patient->identification ?? old('identification') }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- Teléfono -->
                <div>
                    <label class="block text-sm font-medium">Teléfono --</label>
                    <input type="text" name="phone" value="{{ $patient->phone ?? old('phone') }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- EPS (Aseguradora) -->
                <div>
                    <label class="block text-sm font-medium">Aseguradora (EPS)</label>
                    <select name="insurance_id" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Seleccione...</option>
                        @foreach($insurances as $insurance)
                            <option value="{{ $insurance->id }}" {{ (isset($patient) && $patient->insurance_id == $insurance->id) ? 'selected' : '' }}>
                                {{ $insurance->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Datos Clínicos Rápidos -->
                <div>
                    <label class="block text-sm font-medium">Peso (kg)</label>
                    <input type="number" step="0.01" name="weight" value="{{ $patient->weight ?? old('weight') }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
                
                <div>
                    <label class="block text-sm font-medium">Estatura (m)</label>
                    <input type="number" step="0.01" name="height" value="{{ $patient->height ?? old('height') }}" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium">Género</label>
                    <select name="gender" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="male" {{ (isset($patient) && $patient->gender == 'male') ? 'selected' : '' }}>Masculino</option>
                        <option value="female" {{ (isset($patient) && $patient->gender == 'female') ? 'selected' : '' }}>Femenino</option>
                    </select>
                </div>
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium">Condiciones Permanentes</label>
                <textarea name="permanent_conditions" rows="3" class="w-full border-gray-300 rounded-md shadow-sm">{{ $patient->permanent_conditions ?? old('permanent_conditions') }}</textarea>
            </div>

            <button type="submit" class="mt-6 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                {{ isset($patient) ? 'Actualizar Datos' : 'Guardar Paciente' }}
            </button>
        </form>
    </div>
</x-admin-layout>    
