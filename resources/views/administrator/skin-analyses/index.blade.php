@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('administrator.dashboard'),
    ],
    [
        'name' => 'Análisis de Piel',
    ]
];

$statusLabels = [
    'pending_payment' => ['Pago pendiente', 'bg-amber-100 text-amber-800'],
    'payment_failed'  => ['Pago fallido', 'bg-red-100 text-red-800'],
    'processing'      => ['Procesando', 'bg-blue-100 text-blue-800'],
    'completed'       => ['Completado', 'bg-emerald-100 text-emerald-800'],
    'error'           => ['Error', 'bg-rose-100 text-rose-800'],
    'expired'         => ['Expirado', 'bg-gray-100 text-gray-600'],
];

$urgencyLabels = [
    'baja'  => ['Urgencia baja', 'text-emerald-600'],
    'media' => ['Urgencia media', 'text-amber-600'],
    'alta'  => ['Urgencia alta', 'text-red-600'],
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="container mx-auto px-4 py-8">
        @if (session('success'))
            <div class="p-4 mb-3 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 shadow-sm">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 mb-3 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200 shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Análisis de Piel</h2>
                <p class="text-sm text-gray-500">Historial de análisis. Las fotos se borran cuando el informe queda listo.</p>
            </div>

            <form method="GET" action="{{ route('administrator.skin-analyses.index') }}" class="flex flex-wrap gap-2">
                <select name="status" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500">
                    <option value="">Todos los estados</option>
                    @foreach ($statusLabels as $value => [$label, $style])
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Buscar por email..."
                    class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 w-64"
                >
                <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-700">
                    Filtrar
                </button>
            </form>
        </div>

        <div class="space-y-4">
            @forelse($analyses as $analysis)
                @php
                    [$statusLabel, $statusStyle] = $statusLabels[$analysis->status] ?? [$analysis->status, 'bg-gray-100 text-gray-600'];
                    $urgency = null; // Privacidad: el administrador no ve el nivel de urgencia (dato de salud).
                @endphp

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-5 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1.5">
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $statusStyle }}">{{ $statusLabel }}</span>
                            @if ($urgency)
                                <span class="text-xs font-bold {{ $urgency[1] }}">{{ $urgency[0] }}</span>
                            @endif
                            <span class="hidden sm:inline text-gray-300">•</span>
                            <span class="text-xs text-gray-400">{{ $analysis->created_at->format('d/m/Y H:i') }}</span>
                        </div>

                        <h3 class="text-base sm:text-lg font-bold text-gray-900 truncate mb-2">
                            Análisis de piel #{{ $analysis->id }}
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-sm text-gray-600">
                            <div class="truncate">
                                Paciente: <span class="font-medium text-gray-800">{{ $analysis->customer_email }}</span>
                            </div>
                            <div>
                                ID: <span class="font-medium text-gray-800">{{ $analysis->id }}</span>
                            </div>
                            <div>
                                Total: <span class="font-medium text-gray-800">${{ number_format($analysis->total_amount, 0, ',', '.') }}</span>
                                @if ($analysis->promo_code)
                                    <span class="text-xs text-gray-400">· código {{ $analysis->promo_code }}</span>
                                @endif
                            </div>
                            <div>
                                Idioma: <span class="font-medium text-gray-800 uppercase">{{ $analysis->analysis_language }}</span>
                            </div>
                            {{-- Token oculto: el administrador no necesita (ni debe tener) acceso directo al informe del paciente. --}}
                            @if ($analysis->failure_reason)
                                <div class="sm:col-span-2 text-xs text-rose-600 truncate">Motivo del error: {{ $analysis->failure_reason }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 lg:justify-end whitespace-nowrap pt-3 lg:pt-0 border-t border-gray-100 lg:border-t-0">
                        @if ($analysis->status === 'completed')
                            <form action="{{ route('administrator.skin-analyses.resend', $analysis) }}" method="POST" onsubmit="return confirm('¿Reenviar el informe a {{ $analysis->customer_email }}?')" class="w-full sm:w-auto">
                                @csrf
                                <button type="submit" class="w-full sm:w-auto text-sm bg-indigo-600 text-white font-semibold px-4 py-3 border border-transparent rounded-lg shadow-sm hover:bg-indigo-700 transition text-center justify-center flex items-center">
                                    Reenviar
                                </button>
                            </form>
                        @elseif ($analysis->status === 'error')
                            <form action="{{ route('administrator.skin-analyses.retry', $analysis) }}" method="POST" onsubmit="return confirm('¿Reintentar este análisis? Se volverá a procesar con las fotos guardadas y el informe se enviará a {{ $analysis->customer_email }}.')" class="w-full sm:w-auto">
                                @csrf
                                <button type="submit" class="w-full sm:w-auto text-sm bg-amber-500 text-white font-semibold px-4 py-3 border border-transparent rounded-lg shadow-sm hover:bg-amber-600 transition text-center justify-center flex items-center">
                                    Reintentar
                                </button>
                            </form>
                        @endif

                        <form action="{{ route('administrator.skin-analyses.destroy', $analysis) }}" method="POST" onsubmit="return confirm('¿Estás completamente seguro de que deseas eliminar este análisis? Esta acción no se puede deshacer.')" class="w-full sm:w-auto">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full sm:w-auto text-sm bg-rose-50 text-rose-600 font-semibold px-4 py-3 border border-rose-100 rounded-lg shadow-sm hover:bg-rose-100 hover:text-rose-700 transition text-center justify-center flex items-center">
                                Eliminar
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl border p-8 text-center text-gray-500">
                    No hay análisis de piel registrados.
                </div>
            @endforelse

            <div class="mt-4">
                {{ $analyses->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>
