@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('administrator.dashboard')],
    ['name' => 'Comisiones especiales'],
];
$pct = fn ($v) => \App\Services\Payments\SpecialCommissionService::percent($v === null ? null : (float) $v);
$gen = $generals[$type];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="container mx-auto px-4 py-8">

        @if(session('success'))
            <div class="p-4 mb-3 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 shadow-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="p-4 mb-3 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200 shadow-sm">{{ session('error') }}</div>
        @endif

        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Comisiones especiales</h2>
                <p class="text-sm text-gray-500 max-w-3xl">Porcentaje de servicio que paga el paciente al pagar en línea. Si un médico o una clínica tiene un porcentaje especial, se usa ese; si no, la tarifa general de <a href="{{ route('administrator.settings.index') }}" class="underline">Configuraciones</a>. Aplica a los pagos nuevos: las citas ya pagadas conservan el porcentaje con el que se cobraron. Las citas atendidas en nombre de una clínica usan el porcentaje de la clínica.</p>
            </div>
            <a href="{{ route('administrator.commissions.history') }}" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-700 border border-gray-300 hover:bg-gray-50">Ver historial de cambios</a>
        </div>

        <div class="flex gap-2 mb-4">
            @foreach(['doctor' => 'Médicos', 'clinic' => 'Clínicas'] as $t => $label)
                <a href="{{ route('administrator.commissions.index', ['type' => $t]) }}"
                   class="px-4 py-2 rounded-lg text-sm font-semibold {{ $type === $t ? 'bg-gray-800 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                    {{ $label }} <span class="ml-1 text-xs opacity-75">({{ $counts[$t] }} con especial)</span>
                </a>
            @endforeach
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4 text-sm text-blue-900">
            Tarifa general para {{ $type === 'clinic' ? 'clínicas' : 'médicos' }}:
            <strong>virtual {{ $pct($gen['virtual']) }}</strong> · <strong>presencial {{ $pct($gen['presential']) }}</strong>
        </div>

        <form method="GET" action="{{ route('administrator.commissions.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6 flex flex-wrap items-end gap-3">
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-bold text-gray-600 mb-1">Buscar por nombre o correo</label>
                <input type="text" name="q" value="{{ $q }}" maxlength="100" class="w-full rounded-lg border-gray-300 text-sm">
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700 pb-2">
                <input type="checkbox" name="only" value="1" {{ $only ? 'checked' : '' }} class="rounded border-gray-300">
                Solo con porcentaje especial
            </label>
            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-700">Filtrar</button>
            <a href="{{ route('administrator.commissions.index', ['type' => $type]) }}" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">Limpiar</a>
        </form>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="text-left px-4 py-3">{{ $type === 'clinic' ? 'Clínica' : 'Médico' }}</th>
                        <th class="text-left px-4 py-3">Virtual (se aplica)</th>
                        <th class="text-left px-4 py-3">Presencial (se aplica)</th>
                        <th class="text-right px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                @forelse($owners as $o)
                    @php
                        $key = $type . '-' . $o->id;
                        $s = $o->settings;
                        $sv = ($s && $s->special_virtual_commission !== null) ? (float) $s->special_virtual_commission : null;
                        $sp = ($s && $s->special_presential_commission !== null) ? (float) $s->special_presential_commission : null;
                        $isRow = old('_row') === $key;
                        $valV = $isRow ? old('virtual') : ($sv === null ? '' : rtrim(rtrim(number_format($sv, 2, ',', ''), '0'), ','));
                        $valP = $isRow ? old('presential') : ($sp === null ? '' : rtrim(rtrim(number_format($sp, 2, ',', ''), '0'), ','));
                    @endphp
                    <tbody x-data="{ open: {{ $isRow ? 'true' : 'false' }} }" class="border-t border-gray-100">
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-800">{{ $o->user?->name ?? ('#' . $o->id) }}</p>
                                <p class="text-xs text-gray-500">{{ $o->user?->email }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-semibold">{{ $pct(\App\Services\Payments\CommissionRates::forOwner($o, 'virtual')) }}</span>
                                <span class="ml-1 text-xs px-2 py-0.5 rounded-full {{ $sv !== null ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600' }}">{{ $sv !== null ? 'especial' : 'general' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-semibold">{{ $pct(\App\Services\Payments\CommissionRates::forOwner($o, 'presential')) }}</span>
                                <span class="ml-1 text-xs px-2 py-0.5 rounded-full {{ $sp !== null ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600' }}">{{ $sp !== null ? 'especial' : 'general' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('administrator.commissions.history', ['type' => $type, 'id' => $o->id]) }}" class="text-xs text-gray-600 underline mr-3">Historial</a>
                                @if($s)
                                    <button type="button" @click="open = !open" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-800 text-white hover:bg-gray-700">Editar</button>
                                @else
                                    <span class="text-xs text-gray-400">Sin configuración</span>
                                @endif
                            </td>
                        </tr>
                        @if($s)
                            <tr x-show="open" x-cloak class="bg-gray-50">
                                <td colspan="4" class="px-4 py-4">
                                    <form method="POST" action="{{ route('administrator.commissions.update', ['type' => $type, 'id' => $o->id]) }}" class="grid gap-3 md:grid-cols-4">
                                        @csrf
                                        @method('PUT')
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 mb-1">Virtual % (vacío = general {{ $pct($gen['virtual']) }})</label>
                                            <input type="text" inputmode="decimal" name="virtual" value="{{ $valV }}" placeholder="General" class="w-full rounded-lg border-gray-300 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 mb-1">Presencial % (vacío = general {{ $pct($gen['presential']) }})</label>
                                            <input type="text" inputmode="decimal" name="presential" value="{{ $valP }}" placeholder="General" class="w-full rounded-lg border-gray-300 text-sm">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-bold text-gray-600 mb-1">Motivo del cambio (obligatorio)</label>
                                            <input type="text" name="reason" value="{{ $isRow ? old('reason') : '' }}" maxlength="255" placeholder="Ej.: acuerdo comercial firmado el 20/11/2026" class="w-full rounded-lg border-gray-300 text-sm">
                                        </div>
                                        <label class="md:col-span-3 flex items-center gap-2 text-xs text-gray-700">
                                            <input type="checkbox" name="confirm" value="1" class="rounded border-gray-300">
                                            Confirmo el cambio (obligatorio si el porcentaje es 0 % o mayor a {{ $confirmAbove }} %). Se le avisará por correo.
                                        </label>
                                        <div class="flex items-end justify-end">
                                            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700">Guardar</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                @empty
                    <tbody>
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No hay resultados con esos filtros.</td></tr>
                    </tbody>
                @endforelse
            </table>
        </div>

        <div class="mt-4">{{ $owners->links() }}</div>
    </div>
</x-admin-layout>
