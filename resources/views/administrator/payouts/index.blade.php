@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('administrator.dashboard')],
    ['name' => 'Liquidaciones'],
];
$money = fn ($v) => '$' . number_format((float) $v, 0, ',', '.');
$labels = \App\Services\Payments\PayoutFilters::DATE_FIELDS;
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="container mx-auto px-4 py-8">

        @if(session('success'))
            <div class="p-4 mb-3 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 shadow-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="p-4 mb-3 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200 shadow-sm">{{ session('error') }}</div>
        @endif

        @include('administrator.payouts._tabs')

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Liquidaciones</h2>
            <p class="text-sm text-gray-500">Lo que OpenDoctor debe transferir a médicos y clínicas por las citas pagadas en línea. El pago en sede no aparece aquí.</p>
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
            <p class="text-xs font-bold text-amber-700 uppercase tracking-wide">Total pendiente por pagar (todas las liquidaciones)</p>
            <p class="text-2xl font-black text-amber-800">{{ $money($totalPending) }} COP</p>
        </div>

        <!-- Filtros -->
        <form method="GET" action="{{ route('administrator.payouts.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
            @if($filters['batch'])
                <input type="hidden" name="batch" value="{{ $filters['batch'] }}">
                <p class="mb-3 text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-100 rounded-lg px-3 py-2">Mostrando solo las liquidaciones de un lote. <a href="{{ route('administrator.payouts.index', ['status' => 'all']) }}" class="underline">Quitar</a></p>
            @endif
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Estado</label>
                    <select name="status" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Pendientes</option>
                        <option value="paid" {{ $filters['status'] === 'paid' ? 'selected' : '' }}>Liquidadas</option>
                        <option value="all" {{ $filters['status'] === 'all' ? 'selected' : '' }}>Todas</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Modalidad</label>
                    <select name="modalidad" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">Todas</option>
                        <option value="virtual" {{ $filters['modalidad'] === 'virtual' ? 'selected' : '' }}>Virtual</option>
                        <option value="presencial" {{ $filters['modalidad'] === 'presencial' ? 'selected' : '' }}>Presencial</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Beneficiario</label>
                    <select name="beneficiario" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">Médicos y clínicas</option>
                        <option value="doctor" {{ $filters['beneficiario'] === 'doctor' ? 'selected' : '' }}>Solo médicos</option>
                        <option value="clinic" {{ $filters['beneficiario'] === 'clinic' ? 'selected' : '' }}>Solo clínicas</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Buscar</label>
                    <input type="text" name="q" value="{{ $filters['q'] }}" maxlength="100" placeholder="Médico, clínica o ref. de transferencia" class="w-full rounded-lg border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Filtrar fechas por</label>
                    <select name="date_field" class="w-full rounded-lg border-gray-300 text-sm">
                        @foreach($labels as $k => $label)
                            <option value="{{ $k }}" {{ $filters['date_field'] === $k ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Desde</label>
                    <input type="date" name="from" value="{{ $filters['from'] }}" class="w-full rounded-lg border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Hasta</label>
                    <input type="date" name="to" value="{{ $filters['to'] }}" class="w-full rounded-lg border-gray-300 text-sm">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-700">Filtrar</button>
                    <a href="{{ route('administrator.payouts.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">Limpiar</a>
                </div>
            </div>
        </form>

        <!-- Subtotales del filtro -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-[11px] font-bold text-gray-500 uppercase">Liquidaciones</p>
                <p class="text-xl font-black text-gray-800">{{ number_format($totals->n, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-[11px] font-bold text-gray-500 uppercase">Cobrado al paciente</p>
                <p class="text-xl font-black text-gray-800">{{ $money($totals->total_charged) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-[11px] font-bold text-gray-500 uppercase">Fee Wompi</p>
                <p class="text-xl font-black text-gray-800">{{ $money($totals->wompi_fee) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-[11px] font-bold text-gray-500 uppercase">Comisión plataforma</p>
                <p class="text-xl font-black text-gray-800">{{ $money($totals->platform_commission) }}</p>
            </div>
            <div class="bg-emerald-50 rounded-xl border border-emerald-200 p-4 col-span-2 lg:col-span-1">
                <p class="text-[11px] font-bold text-emerald-700 uppercase">A pagar (filtro)</p>
                <p class="text-xl font-black text-emerald-800">{{ $money($totals->amount_to_pay) }}</p>
            </div>
        </div>

        <div class="flex justify-end mb-3">
            <a href="{{ route('administrator.payouts.export', request()->query()) }}" class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-gray-700">
                Exportar CSV (con estos filtros)
            </a>
        </div>

        <!-- Lista -->
        <div class="space-y-3">
            @forelse($payouts as $payout)
                @php
                    $a         = $payout->appointment;
                    $isPaid    = $payout->status === 'paid';
                    $isPending = $payout->status === 'pending';
                    $isClinic  = $payout->payable_type === \App\Models\Clinic::class;
                    $isVirtual = ($a?->service?->type) === 'virtual';
                    $doctorName = $a?->doctor?->user?->name;
                    $recipient = $isClinic ? ($a?->clinic?->user?->name ?? 'Clínica') : ($doctorName ?? 'Médico');
                    $sede      = $isVirtual ? 'Virtual' : ($a?->address?->name ?? '—');
                    $aStatus   = $a?->status instanceof \BackedEnum ? $a->status->value : (string) $a?->status;
                    $citaDate  = $a?->date ? \Carbon\Carbon::parse($a->date)->format('d/m/Y') : '—';
                    $cobroDate = $payout->payment?->approved_at ? \Carbon\Carbon::parse($payout->payment->approved_at)->timezone('America/Bogota')->format('d/m/Y H:i') : '—';
                @endphp

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $isClinic ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">{{ $isClinic ? 'Clínica' : 'Médico' }}</span>
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $isVirtual ? 'bg-sky-100 text-sky-700' : 'bg-orange-100 text-orange-700' }}">{{ $isVirtual ? 'Virtual' : 'Presencial' }}</span>
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $isPaid ? 'bg-emerald-100 text-emerald-700' : ($isPending ? 'bg-amber-100 text-amber-700' : 'bg-gray-200 text-gray-700') }}">{{ $isPaid ? 'Liquidada' : ($isPending ? 'Pendiente' : $payout->status) }}</span>
                            @if($aStatus === 'cancelled')
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-700">Cita cancelada</span>
                            @endif
                            <span class="text-xs text-gray-400">Liquidación #{{ $payout->id }} · Cita #{{ $payout->appointment_id }}</span>
                        </div>

                        <h3 class="text-lg font-bold text-gray-900 truncate">{{ $recipient }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            @if($isClinic && $doctorName)Atendió: {{ $doctorName }} · @endif
                            Sede: {{ $sede }} · Cita: {{ $citaDate }} · Cobro: {{ $cobroDate }}
                        </p>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-3 text-xs text-gray-500">
                            <div><span class="block font-bold text-gray-700">Cobrado al paciente</span>{{ $money($payout->total_charged) }}</div>
                            <div><span class="block font-bold text-gray-700">Fee Wompi</span>{{ $money($payout->wompi_fee) }}</div>
                            <div><span class="block font-bold text-gray-700">Comisión plataforma</span>{{ $money($payout->platform_commission) }}</div>
                            <div><span class="block font-bold text-emerald-700 text-sm">A pagar</span><span class="text-emerald-700 font-black text-sm">{{ $money($payout->amount_to_pay) }}</span></div>
                        </div>

                        @if($isPaid)
                            <p class="text-xs text-gray-400 mt-2">
                                Ref. transferencia: <span class="font-bold text-gray-600">{{ $payout->transfer_reference }}</span>
                                · Pagado: {{ $payout->paid_at?->timezone('America/Bogota')->format('d/m/Y H:i') }}
                                @if($payout->paidBy) · Registró: {{ $payout->paidBy->name }} @endif
                            </p>
                        @endif
                    </div>

                    @if($isPending && $payout->batch_id)
                        <div class="flex-shrink-0 lg:w-72 text-xs text-gray-500">
                            En el lote <a href="{{ route('administrator.payouts.batches.show', $payout->batch_id) }}" class="font-bold text-indigo-700 underline">{{ $payout->batch?->number }}</a>. El pago se registra desde el lote.
                        </div>
                    @elseif($isPending)
                        <div x-data="{ open: false }" class="flex-shrink-0 lg:w-72">
                            <button type="button" @click="open = !open" class="text-xs bg-emerald-600 text-white font-semibold px-4 py-2 rounded-lg shadow-sm hover:bg-emerald-700 transition">Registrar pago</button>
                            <div x-show="open" x-transition x-cloak class="mt-3 bg-slate-50 border border-slate-200 rounded-xl p-4">
                                <form action="{{ route('administrator.payouts.mark-paid', $payout) }}" method="POST" class="flex gap-2 items-end">
                                    @csrf
                                    <div class="flex-1">
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Referencia de transferencia</label>
                                        <input type="text" name="transfer_reference" required maxlength="255" placeholder="Ej: TRF-20261122-001" class="w-full rounded-lg border-gray-300 text-sm focus:ring-emerald-500">
                                    </div>
                                    <button type="submit" onclick="return confirm('¿Confirmas que realizaste esta transferencia por {{ $money($payout->amount_to_pay) }}?')" class="text-xs bg-gray-800 text-white font-semibold px-4 py-2 rounded-lg hover:bg-gray-700 transition">Confirmar</button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-white rounded-xl border p-8 text-center text-gray-500">No hay liquidaciones con estos filtros.</div>
            @endforelse

            <div class="mt-4">{{ $payouts->links() }}</div>
        </div>
    </div>
</x-admin-layout>
