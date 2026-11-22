@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('administrator.dashboard')],
    ['name' => 'Liquidaciones', 'href' => route('administrator.payouts.index')],
    ['name' => 'Lotes de pago'],
];
$money = fn ($v) => '$' . number_format((float) $v, 0, ',', '.');
$colors = ['draft' => 'bg-gray-100 text-gray-700', 'approved' => 'bg-blue-100 text-blue-700', 'paid' => 'bg-emerald-100 text-emerald-700', 'cancelled' => 'bg-red-100 text-red-700'];
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
            <h2 class="text-2xl font-bold text-gray-800">Lotes de pago</h2>
            <p class="text-sm text-gray-500">Cada viernes se prepara un borrador con las citas realizadas hasta el jueves anterior. Tú lo revisas, lo apruebas y registras las transferencias.</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <p class="text-sm text-gray-700">
                Con corte al <strong>{{ $cutoff->format('d/m/Y') }}</strong> hay <strong>{{ $eligibleCount }}</strong> liquidación(es) elegible(s) por <strong>{{ $money($eligibleTotal) }}</strong>.
                @if($unbatched > $eligibleCount)
                    <span class="text-gray-500">Otras {{ $unbatched - $eligibleCount }} pendientes todavía no son elegibles (cita no realizada, posterior al corte o con reembolso).</span>
                @endif
            </p>
            <form method="POST" action="{{ route('administrator.payouts.batches.store') }}" class="mt-4 flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Fecha de corte (opcional)</label>
                    <input type="date" name="cutoff" max="{{ now('America/Bogota')->format('Y-m-d') }}" class="rounded-lg border-gray-300 text-sm">
                </div>
                <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-700">Generar lote (borrador)</button>
                <span class="text-xs text-gray-400">Sin fecha usa el último jueves.</span>
            </form>
        </div>

        <div class="space-y-3">
            @forelse($batches as $b)
                <a href="{{ route('administrator.payouts.batches.show', $b) }}" class="block bg-white rounded-xl border border-gray-200 shadow-sm p-5 hover:border-indigo-300 transition">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-gray-900">{{ $b->number }}</h3>
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $colors[$b->status] ?? 'bg-gray-100 text-gray-700' }}">{{ $b->statusLabel() }}</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                Pago: {{ $b->pay_date->format('d/m/Y') }} · Corte: {{ $b->cutoff_at->timezone('America/Bogota')->format('d/m/Y H:i') }}
                                · {{ $b->payouts_count }} liquidación(es) · {{ $b->beneficiaries_count }} beneficiario(s)
                            </p>
                        </div>
                        <p class="text-xl font-black text-gray-800">{{ $money($b->total_amount) }}</p>
                    </div>
                </a>
            @empty
                <div class="bg-white rounded-xl border p-8 text-center text-gray-500">Todavía no hay lotes.</div>
            @endforelse
            <div class="mt-4">{{ $batches->links() }}</div>
        </div>
    </div>
</x-admin-layout>
