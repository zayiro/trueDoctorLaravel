@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Quién vio mi historia',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="py-8 px-4 space-y-6">

        <div>
            <h1 class="text-2xl font-bold text-gray-900">Quién vio mi historia</h1>
            <p class="text-sm text-gray-500">
                Cada vez que un profesional abre o modifica tu historia clínica queda registrado aquí.
                Si ves un acceso que no reconoces, avísanos por el formulario de contacto.
            </p>
        </div>

        <form method="GET" action="{{ route('patient.access-log.index') }}" class="bg-white border rounded-xl p-4 shadow-sm flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Desde</label>
                <input type="date" name="desde" value="{{ request('desde') }}" class="rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Hasta</label>
                <input type="date" name="hasta" value="{{ request('hasta') }}" class="rounded-lg border-gray-300 text-sm">
            </div>
            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-700">Filtrar</button>
            @if (request('desde') || request('hasta'))
                <a href="{{ route('patient.access-log.index') }}" class="px-4 py-2 rounded-lg border text-sm text-gray-600 hover:bg-gray-50">Limpiar</a>
            @endif
        </form>

        <div class="bg-white border rounded-xl shadow-sm overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Profesional</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qué hizo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($logs as $row)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ \Carbon\Carbon::parse($row->created_at)->timezone('America/Bogota')->isoFormat('D MMM YYYY · h:mm a') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ $row->actor }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ \App\Services\Patients\PatientAccessLog::label($row->action) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-10 text-center text-gray-500">
                                @if (request('desde') || request('hasta'))
                                    No hay accesos en ese rango de fechas.
                                @else
                                    Todavía nadie ha abierto tu historia clínica. Cuando ocurra, lo verás aquí.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div>{{ $logs->links() }}</div>
        @endif
    </div>
</x-admin-layout>
