@php
$breadcrumbs = [
    [
        'name' => 'Dashboard', 
        'href' => route('administrator.dashboard')
    ],
    [
        'name' => 'Pagos a Médicos / Clínicas'
    ],
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="container mx-auto px-4 py-8">

        @if(session('success'))
            <div class="p-4 mb-3 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 mb-3 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200 shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <!-- Header -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Pagos a Médicos / Clínicas</h2>
                <p class="text-sm text-gray-500">Gestión de desembolsos pendientes y realizados.</p>
            </div>

            <!-- Filtro de estado -->
            <form method="GET" action="{{ route('administrator.payouts.index') }}" class="flex gap-2">
                <select name="status" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500">
                    <option value="">Todos</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pendientes</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Pagados</option>
                </select>
                <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-700">
                    Filtrar
                </button>
            </form>
        </div>

        <!-- Resumen -->
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-amber-700 uppercase tracking-wide">Total pendiente por pagar</p>
                <p class="text-2xl font-black text-amber-800">${{ number_format($totalPending, 0, ',', '.') }} COP</p>
            </div>
            <svg class="w-10 h-10 text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/>
            </svg>
        </div>

        <!-- Lista -->
        <div class="space-y-4">
            @forelse($payouts as $payout)
                @php
                    $isPaid    = $payout->status === 'paid';
                    $isClinic  = $payout->payable_type === 'App\Models\Clinic';
                    $recipient = $isClinic
                        ? $payout->appointment->clinic->name ?? 'Clínica'
                        : ($payout->appointment->doctor->user->name ?? 'Médico');
                @endphp

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $isClinic ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ $isClinic ? 'Clínica' : 'Médico' }}
                            </span>
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $isPaid ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $isPaid ? 'Pagado' : 'Pendiente' }}
                            </span>
                            <span class="text-xs text-gray-400">
                                Vence: {{ $payout->due_date?->format('d/m/Y') ?? 'N/A' }}
                            </span>
                        </div>

                        <h3 class="text-lg font-bold text-gray-900 truncate">{{ $recipient }}</h3>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-2 text-xs text-gray-500">
                            <div>
                                <span class="block font-bold text-gray-700">Total cobrado</span>
                                ${{ number_format($payout->total_charged, 0, ',', '.') }}
                            </div>
                            <div>
                                <span class="block font-bold text-gray-700">Fee Wompi</span>
                                ${{ number_format($payout->wompi_fee, 0, ',', '.') }}
                            </div>
                            <div>
                                <span class="block font-bold text-gray-700">Comisión plataforma</span>
                                ${{ number_format($payout->platform_commission, 0, ',', '.') }}
                            </div>
                            <div>
                                <span class="block font-bold text-emerald-700 text-sm">A pagar</span>
                                <span class="text-emerald-700 font-black text-sm">${{ number_format($payout->amount_to_pay, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        @if($isPaid)
                            <p class="text-xs text-gray-400 mt-2">
                                Ref. transferencia: <span class="font-bold text-gray-600">{{ $payout->transfer_reference }}</span>
                                · Pagado: {{ $payout->paid_at?->format('d/m/Y H:i') }}
                            </p>
                        @endif
                    </div>

                    <!-- Acción -->
                    @if(!$isPaid)
                        <div x-data="{ open: false }" class="flex-shrink-0">
                            <button @click="open = !open"
                                class="text-xs bg-emerald-600 text-white font-semibold px-4 py-2 rounded-lg shadow-sm hover:bg-emerald-700 transition">
                                Registrar pago
                            </button>

                            <div x-show="open" x-transition class="mt-3 bg-slate-50 border border-slate-200 rounded-xl p-4" style="display:none;">
                                <form action="{{ route('administrator.payouts.mark-paid', $payout) }}" method="POST" class="flex gap-2 items-end">
                                    @csrf
                                    <div class="flex-1">
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Referencia de transferencia</label>
                                        <input type="text" name="transfer_reference" required
                                            placeholder="Ej: TRF-20260626-001"
                                            class="w-full rounded-lg border-gray-300 text-sm focus:ring-emerald-500">
                                    </div>
                                    <button type="submit"
                                        onclick="return confirm('¿Confirmas que realizaste esta transferencia?')"
                                        class="text-xs bg-gray-800 text-white font-semibold px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                                        Confirmar
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif

                </div>
            @empty
                <div class="bg-white rounded-xl border p-8 text-center text-gray-500">
                    No hay pagos registrados.
                </div>
            @endforelse

            <div class="mt-4">
                {{ $payouts->links() }}
            </div>
        </div>

    </div>
</x-admin-layout>