@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Historia CLinica',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-5xl mx-auto py-10 px-4">
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-gray-900">Historial Clínico</h1>
            <p class="text-gray-500">Consulta tus diagnósticos y planes de tratamiento anteriores.</p>
        </div>

        <div class="space-y-8">
            @forelse($history as $entry)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <!-- Cabecera de la Entrada -->
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider
                                {{ $entry->entry_type === 'emergency' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ $entry->entry_type }}
                            </span>
                            <span class="text-sm text-gray-500 font-medium">
                                {{ $entry->created_at->format('d/m/Y') }}
                            </span>
                        </div>
                        <span class="text-xs text-gray-400 font-mono">ID-{{ $entry->id }}</span>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Columna Izquierda: Resumen -->
                            <div class="space-y-4">
                                <div>
                                    <h4 class="text-xs font-bold text-gray-400 uppercase">Motivo</h4>
                                    <p class="text-gray-900 font-semibold">{{ $entry->reason_for_consultation }}</p>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-gray-400 uppercase">Médico</h4>
                                    <p class="text-gray-700">Dr. {{ $entry->doctor->name }}</p>
                                    @if($entry->appointment)
                                        <p class="text-xs text-blue-600">{{ $entry->appointment->service->name }}</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Columna Central: Diagnóstico -->
                            <div class="md:col-span-2 space-y-4">
                                <div>
                                    <h4 class="text-xs font-bold text-gray-400 uppercase mb-1">Síntomas</h4>
                                    <p class="text-sm text-gray-600 italic">{{ $entry->symptoms ?? 'No registrados' }}</p>
                                </div>
                                
                                <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                                    <h4 class="text-sm font-bold text-blue-800 mb-1">Diagnóstico</h4>
                                    <p class="text-gray-800">{{ $entry->diagnosis }}</p>
                                </div>

                                @if($entry->treatment_plan)
                                    <div>
                                        <h4 class="text-sm font-bold text-gray-800 mb-1">Plan de Tratamiento</h4>
                                        <p class="text-sm text-gray-600 leading-relaxed">{{ $entry->treatment_plan }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-16 bg-gray-50 rounded-xl border-2 border-dashed border-gray-300">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay registros médicos</h3>
                    <p class="mt-1 text-sm text-gray-500">Tu historial aparecerá aquí después de tu primera consulta.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $history->links() }}
        </div>
    </div>
</x-admin-layout>
