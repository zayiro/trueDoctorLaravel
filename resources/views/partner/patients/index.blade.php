@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Directorio de Pacientes',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="p-6 bg-white rounded-lg shadow">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Buscador de Pacientes</h2>
            
            {{-- Badge del Plan Actual --}}
            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $plan->slug === 'free' ? 'bg-gray-100' : 'bg-gold-100 text-gold-700' }}">
                {{ $plan->name }}
            </span>
        </div>

        @if($plan->can_search_patients)
            {{-- BUSCADOR ACTIVO PARA PREMIUM/GOLD --}}
            <form action="{{ route('partner.patients.index') }}" method="GET" class="mb-8">
                <div class="flex gap-2">
                    <div class="flex w-full">
                        <input type="text" name="query" value="{{ request('query') }}" 
                            placeholder="Buscar por nombre, apellidos o documento..."
                            class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-blue-500">                        
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                        Buscar
                    </button>
                </div>
            </form>
        @else
            {{-- AVISO PARA USUARIOS FREE --}}
            <div class="mb-8 p-4 bg-blue-50 border-l-4 border-blue-500 text-blue-700">
                <p class="font-bold">Vista Limitada</p>
                <p class="text-sm">Actualmente solo ves pacientes con citas para hoy. 
                <strong>Actualiza el plan</strong> para buscar en toda tu base de datos histórica.</p>
                <a href="{{ route('plans.index') }}" class="text-sm underline font-bold mt-2 inline-block">Ver Planes Pro</a>
            </div>
        @endif

        {{-- TABLA DE RESULTADOS (Aquí aplicas el scroll horizontal que vimos antes) --}}
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paciente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Última Cita</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($patients as $patient)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $patient->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $patient->appointments->last()?->fecha->format('d/m/Y') ?? 'Sin citas' }}</td>
                            <td class="px-6 py-4">
                                <a href="{{ route('partner.patients.show', $patient) }}" class="text-blue-600 hover:text-blue-900">Ver Ficha</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-10 text-center text-gray-500">
                                No se encontrarón pacientes para mostrar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>