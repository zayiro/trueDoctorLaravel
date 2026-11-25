@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('administrator.dashboard')
    ],
    [
        'name' => 'Reembolsos de planes'
    ],
];
$badge = [
    'approved' => ['Aprobado', 'bg-green-50 text-green-700 border-green-200'],
    'refunded' => ['Reembolsado', 'bg-blue-50 text-blue-700 border-blue-200'],
    'voided'   => ['Anulado', 'bg-gray-100 text-gray-600 border-gray-200'],
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="container mx-auto px-4 py-8 space-y-6">

        <div>
            <h2 class="text-2xl font-bold text-gray-800">Reembolsos de planes</h2>
            <p class="text-sm text-gray-500">
                Primero devuelve el dinero desde el panel de Wompi y luego regístralo aquí. El plan se retira, el usuario recibe un correo y el pago deja de contar en los ingresos.
            </p>
        </div>

        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 text-green-800 text-sm px-4 py-3">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 text-red-800 text-sm px-4 py-3">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 text-red-800 text-sm px-4 py-3">
                @foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach
            </div>
        @endif

        <div class="flex flex-wrap gap-2">
            @foreach ($statuses as $key => $text)
                <a href="{{ route('administrator.plan-refunds.index', ['status' => $key]) }}"
                   class="px-3 py-1.5 rounded-lg text-sm font-semibold border {{ $filter === $key ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50' }}">
                    {{ $text }} ({{ $counts[$key] }})
                </a>
            @endforeach
        </div>

        <div class="bg-white border rounded-xl shadow-sm overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-400">
                    <tr>
                        <th class="px-4 py-3 text-left">Usuario</th>
                        <th class="px-4 py-3 text-left">Plan</th>
                        <th class="px-4 py-3 text-left">Monto</th>
                        <th class="px-4 py-3 text-left">Referencia</th>
                        <th class="px-4 py-3 text-left">Estado</th>
                        <th class="px-4 py-3 text-right"></th>
                    </tr>
                </thead>
                @forelse ($payments as $p)
                    <tbody x-data="{ open: false }" class="border-t">
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-900">{{ $p->user_name }}</p>
                                <p class="text-xs text-gray-500">{{ $p->email }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $p->plan }}</td>
                            <td class="px-4 py-3 text-gray-900 font-semibold">${{ number_format($p->total_in_cents / 100, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                <p class="font-mono">{{ $p->reference }}</p>
                                @if ($p->approved_at)<p>{{ \Illuminate\Support\Carbon::parse($p->approved_at)->timezone('America/Bogota')->format('d/m/Y H:i') }}</p>@endif
                            </td>
                            <td class="px-4 py-3">
                                @php [$txt, $cls] = $badge[$p->status] ?? [$p->status, 'bg-gray-100 text-gray-600 border-gray-200']; @endphp
                                <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-lg border {{ $cls }}">{{ $txt }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($p->status === 'approved')
                                    <button type="button" @click="open = !open" class="text-sm font-semibold text-red-600 hover:underline">Registrar reembolso</button>
                                @endif
                            </td>
                        </tr>
                        @if ($p->status === 'approved')
                            <tr x-show="open" x-cloak class="bg-gray-50">
                                <td colspan="6" class="px-4 py-4">
                                    <form method="POST" action="{{ route('administrator.plan-refunds.refund', $p->id) }}" class="grid gap-3 md:grid-cols-2">
                                        @csrf
                                        <div>
                                            <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Referencia de la devolución en Wompi</label>
                                            <input type="text" name="provider_reference" required maxlength="120" class="w-full rounded-lg border-gray-300 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Nota (opcional)</label>
                                            <input type="text" name="note" maxlength="300" class="w-full rounded-lg border-gray-300 text-sm">
                                        </div>
                                        <label class="md:col-span-2 flex items-start gap-2 text-sm text-gray-700">
                                            <input type="checkbox" name="confirm" value="1" required class="mt-0.5 rounded border-gray-300">
                                            Ya devolví el dinero en el panel de Wompi. Entiendo que esto retira el plan de {{ $p->user_name }} y no se puede deshacer.
                                        </label>
                                        <div class="md:col-span-2">
                                            <button type="submit" class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold hover:bg-red-700">Confirmar reembolso</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                @empty
                    <tbody><tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">No hay pagos de planes en este estado.</td></tr></tbody>
                @endforelse
            </table>
        </div>

        <div>{{ $payments->links() }}</div>
    </div>
</x-admin-layout>
