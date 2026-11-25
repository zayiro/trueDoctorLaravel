@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('administrator.dashboard')
    ],
    [
        'name' => 'Accesos a fichas'
    ],
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="container mx-auto px-4 py-8 space-y-6">

        <div>
            <h2 class="text-2xl font-bold text-gray-800">Accesos a fichas de pacientes</h2>
            <p class="text-sm text-gray-500">
                Auditoría de quién abrió o modificó una ficha. Por privacidad no se muestran datos de salud ni el nombre del paciente: solo su número y sus iniciales.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white border rounded-xl p-5 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase">Hoy</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['hoy'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-white border rounded-xl p-5 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase">Últimos 7 días</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['semana'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-white border rounded-xl p-5 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase">Intentos bloqueados (7 días)</p>
                <p class="text-2xl font-bold {{ $stats['bloqueados'] > 0 ? 'text-red-600' : 'text-gray-900' }} mt-1">{{ number_format($stats['bloqueados'], 0, ',', '.') }}</p>
            </div>
        </div>

        <form method="GET" action="{{ route('administrator.access-log.index') }}" class="bg-white border rounded-xl p-4 shadow-sm flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Desde</label>
                <input type="date" name="desde" value="{{ request('desde') }}" class="rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Hasta</label>
                <input type="date" name="hasta" value="{{ request('hasta') }}" class="rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Acción</label>
                <select name="action" class="rounded-lg border-gray-300 text-sm">
                    <option value="">Todas</option>
                    @foreach ($labels as $key => $text)
                        <option value="{{ $key }}" {{ request('action') === $key ? 'selected' : '' }}>{{ $text }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Profesional</label>
                <input type="text" name="actor" value="{{ request('actor') }}" placeholder="Nombre" class="rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">N.º de paciente</label>
                <input type="text" name="paciente" value="{{ request('paciente') }}" placeholder="Ej. 12" class="rounded-lg border-gray-300 text-sm w-28">
            </div>
            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-700">Filtrar</button>
            @if (request()->hasAny(['desde', 'hasta', 'action', 'actor', 'paciente']))
                <a href="{{ route('administrator.access-log.index') }}" class="px-4 py-2 rounded-lg border text-sm text-gray-600 hover:bg-gray-50">Limpiar</a>
            @endif
        </form>

        <div class="bg-white border rounded-xl shadow-sm overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quién</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acción</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paciente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($logs as $row)
                        @php $denied = $row->action === 'denegado'; @endphp
                        <tr class="{{ $denied ? 'bg-red-50/40' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ \Carbon\Carbon::parse($row->created_at)->timezone('America/Bogota')->isoFormat('D MMM YYYY · h:mm a') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="font-semibold text-gray-900">{{ $row->actor }}</span>
                                <span class="ml-1 text-xs text-gray-400">{{ $row->actor_role }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="{{ $denied ? 'px-2 py-0.5 rounded-md text-xs font-bold bg-red-100 text-red-700' : 'text-gray-700' }}">
                                    {{ \App\Services\Patients\PatientAccessLog::label($row->action) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $row->paciente }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-400">{{ $row->ip ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">No hay accesos registrados con esos filtros.</td>
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
