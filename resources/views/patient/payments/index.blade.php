@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Pagos y Facturación',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="py-8 px-4 space-y-6">
        
        {{-- Encabezado --}}
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Pagos y Facturación</h1>
            <p class="text-sm text-gray-500">Historial de pagos de tus consultas médicas, referencias Wompi y comprobantes descargables.</p>
        </div>

        {{-- Tarjetas de Resumen --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white border rounded-xl p-5 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase">Total Pagado</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">${{ number_format($totalPaid, 0, ',', '.') }} COP</p>
                <span class="text-xs text-green-600 font-medium">Consultas confirmadas y pagadas</span>
            </div>

            <div class="bg-white border rounded-xl p-5 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase">Pagos Pendientes</p>
                <p class="text-2xl font-bold text-amber-600 mt-1">{{ $pendingCount }}</p>
                <span class="text-xs text-gray-500">Citas por completar pago</span>
            </div>

            <div class="bg-white border rounded-xl p-5 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase">Procesador Oficial</p>
                <p class="text-lg font-bold text-blue-600 mt-1">Wompi Colombia</p>
                <span class="text-xs text-gray-500">PSE, Tarjetas de Crédito y Débito</span>
            </div>
        </div>

        {{-- Filtro por payment_status --}}
        <div class="bg-white border rounded-xl p-4 shadow-sm flex flex-wrap items-center justify-between gap-4">
            <form method="GET" action="{{ route('patient.payments.index') }}" class="flex items-center gap-3">
                <select name="payment_status" onchange="this.form.submit()" class="border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todos los estados</option>
                    <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Pagados</option>
                    <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Pendientes</option>
                    <option value="failed" {{ request('payment_status') == 'failed' ? 'selected' : '' }}>Fallidos / Rechazados</option>
                    <option value="cancelled" {{ request('payment_status') == 'cancelled' ? 'selected' : '' }}>Cancelados</option>
                </select>
                @if(request('payment_status'))
                    <a href="{{ route('patient.payments.index') }}" class="text-xs text-blue-600 hover:underline font-semibold">Limpiar filtro</a>
                @endif
            </form>
        </div>

        {{-- Tabla de Pagos --}}
        <div class="bg-white border rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b text-xs font-bold text-gray-500 uppercase">
                            <th class="py-3.5 px-4">Referencia</th>
                            <th class="py-3.5 px-4">Servicio / Especialista</th>
                            <th class="py-3.5 px-4">Fecha de Pago</th>
                            <th class="py-3.5 px-4">Total</th>
                            <th class="py-3.5 px-4">Estado</th>
                            <th class="py-3.5 px-4 text-right">Comprobante</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse($appointments as $appointment)
                            @php
                                $netAmount = max(0, $appointment->price - $appointment->discount_amount);
                            @endphp
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-4 px-4">
                                    <p class="font-bold text-gray-900">{{ $appointment->reference }}</p>
                                    @if($appointment->wompi_reference)
                                        <p class="text-xs text-gray-400">Wompi: {{ $appointment->wompi_reference }}</p>
                                    @endif
                                </td>
                                <td class="py-4 px-4">
                                    <p class="font-semibold text-gray-800">{{ $appointment->service->name ?? 'Consulta Médica' }}</p>
                                    <p class="text-xs text-gray-500">
                                        Dr(a). {{ $appointment->doctor->name ?? 'Especialista' }} · 
                                        Cita: {{ \Carbon\Carbon::parse($appointment->date)->format('d/m/Y') }}
                                    </p>
                                </td>
                                <td class="py-4 px-4 text-xs text-gray-600">
                                    @if($appointment->paid_at)
                                        {{ \Carbon\Carbon::parse($appointment->paid_at)->format('d/m/Y h:i A') }}
                                    @else
                                        <span class="text-gray-400">Sin registrar</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4">
                                    <p class="font-bold text-gray-900">${{ number_format($netAmount, 0, ',', '.') }} COP</p>
                                    @if($appointment->discount_amount > 0)
                                        <p class="text-xs text-green-600">Descuento: -${{ number_format($appointment->discount_amount, 0, ',', '.') }}</p>
                                    @endif
                                </td>
                                <td class="py-4 px-4">
                                    @php
                                        $payStatus = $appointment->payment_status instanceof \BackedEnum
                                            ? $appointment->payment_status->value
                                            : (string) $appointment->payment_status;

                                        $apptStatus = $appointment->status instanceof \BackedEnum
                                            ? $appointment->status->value
                                            : (string) $appointment->status;
                                    @endphp

                                    {{-- En la columna de Estado --}}
                                    @switch($payStatus)
                                        @case('paid')
                                            <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-green-100 text-green-700">Pagado</span>
                                            @break
                                        @case('pending')
                                            <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-amber-100 text-amber-700">Pendiente</span>
                                            @break
                                        @case('failed')
                                            <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-red-100 text-red-700">Rechazado</span>
                                            @break
                                        @case('cancelled')
                                            <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-gray-100 text-gray-600">Cancelado</span>
                                            @break
                                    @endswitch
                                </td>
                                <td class="py-4 px-4 text-right">
                                    {{-- En la columna de Comprobante --}}
                                    @if($payStatus === 'paid')
                                        <a href="{{ route('patient.payments.receipt', $appointment->id) }}" 
                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg text-xs font-bold transition">
                                            Ver / Descargar
                                        </a>
                                    @elseif($payStatus === 'pending' && $apptStatus !== 'cancelled')
                                        <a href="{{ route('appointments.preview', ['id' => $appointment->id]) }}"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-amber-50 text-amber-700 hover:bg-amber-100 rounded-lg text-xs font-bold transition">
                                            Completar pago
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-gray-500">
                                    Aún no tienes movimientos de pago registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($appointments->hasPages())
                <div class="p-4 border-t">
                    {{ $appointments->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
