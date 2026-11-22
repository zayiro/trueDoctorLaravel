@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('administrator.dashboard')],
    ['name' => 'Comisiones especiales', 'href' => route('administrator.commissions.index')],
    ['name' => 'Historial'],
];
$pct = fn ($v) => $v === null ? 'tarifa general' : \App\Services\Payments\SpecialCommissionService::percent((float) $v);
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="container mx-auto px-4 py-8">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Historial de comisiones especiales</h2>
                <p class="text-sm text-gray-500">Cada cambio queda con el motivo, quién lo hizo y desde qué IP.{{ ($type && $id) ? ' Mostrando un solo beneficiario.' : '' }}</p>
            </div>
            <div class="flex gap-2">
                @if($type && $id)
                    <a href="{{ route('administrator.commissions.history') }}" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-700 border border-gray-300 hover:bg-gray-50">Ver todos</a>
                @endif
                <a href="{{ route('administrator.commissions.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-700 border border-gray-300 hover:bg-gray-50">Volver</a>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="text-left px-4 py-3">Fecha (Colombia)</th>
                        <th class="text-left px-4 py-3">Beneficiario</th>
                        <th class="text-left px-4 py-3">Modalidad</th>
                        <th class="text-left px-4 py-3">Antes → Después</th>
                        <th class="text-left px-4 py-3">Motivo</th>
                        <th class="text-left px-4 py-3">Hecho por</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($changes as $c)
                        <tr class="border-t border-gray-100">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $c->created_at?->timezone('America/Bogota')->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $c->owner_type === 'clinic' ? 'Clínica' : 'Médico' }}</span>
                                {{ $names[$c->owner_type][$c->owner_id] ?? ('#' . $c->owner_id) }}
                            </td>
                            <td class="px-4 py-3">{{ \App\Services\Payments\SpecialCommissionService::MODALITY_LABELS[$c->modality] ?? $c->modality }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $pct($c->old_value) }} → <strong>{{ $pct($c->new_value) }}</strong></td>
                            <td class="px-4 py-3">{{ $c->reason }}</td>
                            <td class="px-4 py-3 text-xs text-gray-600">{{ $admins[$c->changed_by] ?? '—' }}<br>{{ $c->ip }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Todavía no hay cambios registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $changes->links() }}</div>
    </div>
</x-admin-layout>
