@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('administrator.dashboard')
    ],
    [
        'name' => 'Reembolsos'
    ],
];
$byLabels = ['patient' => 'el paciente', 'doctor' => 'la clínica o el médico', 'system' => 'el sistema'];
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

        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Reembolsos</h2>
                <p class="text-sm text-gray-500">Citas pagadas en línea que se cancelaron. Devuelve el dinero desde el panel de Wompi y regístralo aquí: el paciente recibe un correo de confirmación.</p>
            </div>

            <form method="GET" action="{{ route('administrator.refunds.index') }}" class="flex gap-2">
                <select name="status" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500">
                    <option value="">Todos</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pendientes</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Devueltos</option>
                </select>
                <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-700">
                    Filtrar
                </button>
            </form>
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
            <p class="text-xs font-bold text-amber-700 uppercase tracking-wide">Pendiente por devolver</p>
            <p class="text-2xl font-black text-amber-800">${{ number_format($totalPending, 0, ',', '.') }} COP</p>
        </div>

        <div class="space-y-4">
            @forelse($refunds as $refund)
                @php
                    $done = $refund->status === 'completed';
                    $a    = $refund->appointment;
                    $warn = $refund->notes && str_contains($refund->notes, 'ATENCIÓN');
                @endphp

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col md:flex-row md:items-start md:justify-between gap-4">

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $done ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $done ? 'Devuelto' : 'Pendiente' }}
                            </span>
                            <span class="text-xs text-gray-400">
                                Cita {{ $a?->reference ?? '—' }} · cancelada por {{ $byLabels[$refund->cancelled_by] ?? 'no registrado' }}
                            </span>
                        </div>

                        <h3 class="text-lg font-bold text-gray-900 truncate">{{ $a?->patient?->user?->name ?? 'Paciente' }}</h3>
                        <p class="text-xs text-gray-500">{{ $a?->patient?->user?->email }}</p>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-3 text-xs text-gray-500">
                            <div>
                                <span class="block font-bold text-gray-700">A devolver</span>
                                <span class="text-emerald-700 font-black text-sm">${{ number_format($refund->amount_in_cents / 100, 0, ',', '.') }}</span>
                            </div>
                            <div>
                                <span class="block font-bold text-gray-700">Cita</span>
                                {{ $a?->date ? \Carbon\Carbon::parse($a->date)->format('d/m/Y') : '—' }}
                                {{ $a?->start_time ? \Carbon\Carbon::parse($a->start_time)->format('g:i A') : '' }}
                            </div>
                            <div>
                                <span class="block font-bold text-gray-700">Atendía</span>
                                {{ $a?->clinic?->name ?? ($a?->doctor?->user?->name ?? '—') }}
                            </div>
                            <div>
                                <span class="block font-bold text-gray-700">Transacción Wompi</span>
                                <span class="break-all">{{ $refund->payment?->provider_transaction_id ?? '—' }}</span>
                            </div>
                        </div>

                        @if($refund->reason)
                            <p class="text-xs text-gray-500 mt-2">Motivo: <span class="text-gray-700">{{ $refund->reason }}</span></p>
                        @endif

                        @if($refund->notes)
                            <p class="text-xs mt-2 whitespace-pre-line {{ $warn ? 'text-red-700 font-bold' : 'text-gray-500' }}">{{ $refund->notes }}</p>
                        @endif

                        @if($done)
                            <p class="text-xs text-gray-400 mt-2">
                                Ref. del reembolso: <span class="font-bold text-gray-600">{{ $refund->provider_reference }}</span>
                                · Registrado: {{ $refund->processed_at?->timezone('America/Bogota')->format('d/m/Y H:i') }}
                                @if($refund->processor) · por {{ $refund->processor->name }} @endif
                                @if($refund->admin_note) · Nota: {{ $refund->admin_note }} @endif
                            </p>
                        @endif
                    </div>

                    @if(!$done)
                        <div x-data="{ open: false }" class="flex-shrink-0 md:w-80">
                            <button @click="open = !open"
                                class="text-xs bg-emerald-600 text-white font-semibold px-4 py-2 rounded-lg shadow-sm hover:bg-emerald-700 transition">
                                Marcar como devuelto
                            </button>

                            <div x-show="open" x-transition class="mt-3 bg-slate-50 border border-slate-200 rounded-xl p-4" style="display:none;">
                                <form action="{{ route('administrator.refunds.mark-done', $refund) }}" method="POST" class="space-y-3">
                                    @csrf
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Referencia del reembolso en Wompi</label>
                                        <input type="text" name="provider_reference" required maxlength="255"
                                            placeholder="Ej: REEMB-1465367"
                                            class="w-full rounded-lg border-gray-300 text-sm focus:ring-emerald-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Nota (opcional)</label>
                                        <input type="text" name="admin_note" maxlength="500"
                                            class="w-full rounded-lg border-gray-300 text-sm focus:ring-emerald-500">
                                    </div>
                                    <button type="submit"
                                        onclick="return confirm('¿Confirmas que ya devolviste el dinero en Wompi? Se le enviará un correo al paciente.')"
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
                    No hay reembolsos registrados.
                </div>
            @endforelse

            <div class="mt-4">
                {{ $refunds->links() }}
            </div>
        </div>

    </div>
</x-admin-layout>
