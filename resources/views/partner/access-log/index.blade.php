@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Mis accesos a fichas',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="py-8 px-4 space-y-6">

        <div>
            <h1 class="text-2xl font-bold text-gray-900">Mis accesos a fichas</h1>
            <p class="text-sm text-gray-500">
                Registro de las fichas que has abierto o modificado. Los pacientes pueden ver tus accesos a su historia, y la plataforma los conserva como trazabilidad.
            </p>
        </div>

        <form method="GET" action="{{ route('partner.access-log.index') }}" class="bg-white border rounded-xl p-4 shadow-sm flex flex-wrap items-end gap-3">
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
                <a href="{{ route('partner.access-log.index') }}" class="px-4 py-2 rounded-lg border text-sm text-gray-600 hover:bg-gray-50">Limpiar</a>
            @endif
        </form>

        <div class="bg-white border rounded-xl shadow-sm overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paciente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qué hiciste</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($logs as $row)
                        @php $denied = $row->action === 'denegado'; @endphp
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ \Carbon\Carbon::parse($row->created_at)->timezone('America/Bogota')->isoFormat('D MMM YYYY · h:mm a') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ $row->paciente }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="{{ $denied ? 'px-2 py-0.5 rounded-md text-xs font-bold bg-red-50 text-red-700' : 'text-gray-700' }}">
                                    {{ \App\Services\Patients\PatientAccessLog::label($row->action) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-10 text-center text-gray-500">
                                @if (request('desde') || request('hasta'))
                                    No hay accesos en ese rango de fechas.
                                @else
                                    Aún no has abierto fichas de pacientes.
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
