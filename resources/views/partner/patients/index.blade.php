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
$isClinic   = $isClinic ?? false;
$searchTerm = $searchTerm ?? null;
$searching  = $searching ?? false;
$canSearch  = (bool) ($plan?->can_search_patients);
$cap        = $cap ?? ($plan?->max_patients_list ?? 15);
$paid       = (float) ($plan?->price ?? 0) > 0;
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="p-6 bg-white rounded-lg shadow">
        @if (session('success'))
            <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-100 text-sm font-semibold text-green-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-100 text-sm font-semibold text-red-800">{{ session('error') }}</div>
        @endif

        <div class="flex justify-between items-center mb-2">
            <h2 class="text-2xl font-bold">Directorio de Pacientes</h2>

            {{-- Badge del plan actual --}}
            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $paid ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700' }}">
                {{ $plan?->name }}
            </span>
        </div>
        <p class="text-sm text-gray-500 mb-6">
            @if ($isClinic)
                Pacientes con al menos una consulta completada (Zoom o presencial) en tu clínica. La historia clínica la ve solo el médico tratante.
            @else
                Pacientes con al menos una consulta completada contigo (Zoom o presencial).
            @endif
        </p>

        @if ($canSearch)
            <form action="{{ $isClinic ? route('partner.clinic.patients.index') : route('partner.patients.index') }}" method="GET" class="mb-6">
                <div class="flex gap-2">
                    <div class="flex w-full">
                        <input type="text" name="query" value="{{ $searchTerm }}" minlength="3"
                            placeholder="Buscar por nombre, apellidos, correo o documento completo..."
                            class="w-full pl-4 pr-4 py-2 border rounded-lg focus:ring-blue-500">
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                        Buscar
                    </button>
                    @if ($searchTerm)
                        <a href="{{ $isClinic ? route('partner.clinic.patients.index') : route('partner.patients.index') }}" class="px-4 py-2 rounded-lg border text-sm text-gray-600 hover:bg-gray-50 whitespace-nowrap">Limpiar</a>
                    @endif
                </div>
                <p class="text-xs text-gray-400 mt-1">Escribe al menos 3 letras. El documento se busca completo, sin puntos.</p>
            </form>
        @elseif ($plan)
            <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-500 text-blue-700">
                <p class="font-bold">Vista limitada</p>
                <p class="text-sm">Tu plan muestra tus últimos {{ $cap }} pacientes atendidos. <strong>Mejora tu plan</strong> para buscar en todo tu historial.</p>
                <a href="{{ route('plans.index') }}" class="text-sm underline font-bold mt-2 inline-block">Ver planes</a>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paciente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Consultas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Última cita</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Próxima cita</th>
                        @if ($isClinic)
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Atendido por</th>
                        @endif
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($patients as $item)
                        @php $p = $item->patient; $inactive = !$p->active; @endphp
                        <tr class="{{ $inactive ? 'bg-gray-50 text-gray-400' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-semibold {{ $inactive ? 'text-gray-400' : 'text-gray-900' }}">
                                    {{ $p->user?->name }}
                                    @if ($inactive)
                                        <span class="ml-2 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-gray-200 text-gray-500">Desactivado</span>
                                    @endif
                                </div>
                                <div class="text-xs {{ $inactive ? 'text-gray-400' : 'text-gray-500' }}">
                                    @if ($item->documento) Doc. {{ $item->documento }} · @endif
                                    {{ is_numeric($p->age) ? $p->age . ' años' : 'Edad no registrada' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $item->consultas }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $item->ultima->isoFormat('D MMM YYYY') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $inactive ? '—' : ($item->proxima ? $item->proxima->isoFormat('D MMM YYYY') : '—') }}</td>
                            @if ($isClinic)
                                <td class="px-6 py-4 whitespace-nowrap">{{ $item->medico ?? '—' }}</td>
                            @endif
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if (!$inactive && !$isClinic)
                                    <a href="{{ route('partner.patients.show', $p->id) }}" class="text-blue-600 hover:text-blue-900">Ver ficha</a>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isClinic ? 6 : 5 }}" class="px-6 py-10 text-center text-gray-500">
                                @if ($searching)
                                    No encontramos pacientes para «{{ $searchTerm }}». Revisa el nombre o escribe el documento completo.
                                @else
                                    Aún no hay pacientes atendidos. Aparecerán aquí cuando se complete la primera consulta.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($patients->hasPages())
            <div class="mt-4">{{ $patients->links() }}</div>
        @endif
    </div>
</x-admin-layout>
