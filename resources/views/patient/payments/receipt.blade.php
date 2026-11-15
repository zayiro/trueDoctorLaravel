<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Pago - {{ $appointment->reference }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
        }
    </style>
</head>

<body class="bg-gray-100 py-10 px-4">
    <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-sm border p-8">
        
        {{-- Barra de acciones (No se imprime) --}}
        <div class="no-print flex justify-between items-center pb-6 mb-6 border-b">
            <a href="{{ route('patient.payments.index') }}" class="text-sm font-semibold text-gray-500 hover:text-gray-800">
                ← Volver a Pagos
            </a>
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition">
                Imprimir / Guardar PDF
            </button>
        </div>

        {{-- Encabezado del Comprobante --}}
        <div class="flex justify-between items-start border-b pb-6">
            <div>
                <h1 class="text-2xl font-bold text-blue-600">OpenDoctorOnline</h1>
                <p class="text-xs text-gray-500 mt-1">Plataforma de Telemedicina y Citas Médicas</p>
                <p class="text-xs text-gray-500">Santiago de Cali, Colombia · opendoctor.online</p>
            </div>
            <div class="text-right">
                <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full uppercase">
                    Pago Aprobado
                </span>
                <p class="text-sm font-bold text-gray-900 mt-2">Ref: {{ $appointment->reference }}</p>
                @if($appointment->wompi_reference)
                    <p class="text-xs text-gray-400">Wompi: {{ $appointment->wompi_reference }}</p>
                @endif
            </div>
        </div>

        {{-- Datos del Paciente y Fecha --}}
        <div class="grid grid-cols-2 gap-6 py-6 border-b text-sm">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase mb-1">Paciente</p>
                <p class="font-bold text-gray-900">{{ auth()->user()->name }}</p>
                <p class="text-gray-600">{{ auth()->user()->email }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs font-bold text-gray-400 uppercase mb-1">Fecha de Pago</p>
                <p class="font-semibold text-gray-800">
                    {{ $appointment->paid_at ? \Carbon\Carbon::parse($appointment->paid_at)->format('d/m/Y h:i A') : $appointment->updated_at->format('d/m/Y h:i A') }}
                </p>
                <p class="text-xs text-gray-500 mt-1">Canal: {{ ucfirst($appointment->channel) }}</p>
            </div>
        </div>

        {{-- Detalle del Servicio --}}
        <div class="py-6">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b text-xs font-bold text-gray-400 uppercase">
                        <th class="pb-3">Descripción del Servicio</th>
                        <th class="pb-3 text-right">Valor</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr>
                        <td class="py-4">
                            <p class="font-bold text-gray-900">{{ $appointment->service->name ?? 'Consulta Médica en Línea' }}</p>
                            <p class="text-xs text-gray-500">
                                Especialista: Dr(a). {{ $appointment->doctor->name ?? 'Médico Certificado' }}
                            </p>
                            <p class="text-xs text-gray-500">
                                Fecha de cita: {{ \Carbon\Carbon::parse($appointment->date)->format('d/m/Y') }} ({{ \Carbon\Carbon::parse($appointment->start_time)->format('h:i A') }})
                            </p>
                        </td>
                        <td class="py-4 text-right font-semibold text-gray-900">
                            ${{ number_format($appointment->price, 0, ',', '.') }} COP
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Totales --}}
        @php
            $netTotal = max(0, $appointment->price - $appointment->discount_amount);
        @endphp
        <div class="bg-gray-50 rounded-xl p-5 space-y-2 text-sm">
            <div class="flex justify-between text-gray-600">
                <span>Subtotal</span>
                <span>${{ number_format($appointment->price, 0, ',', '.') }} COP</span>
            </div>
            @if($appointment->discount_amount > 0)
                <div class="flex justify-between text-green-600 font-medium">
                    <span>Descuento aplicado {{ $appointment->promo_code ? "({$appointment->promo_code})" : '' }}</span>
                    <span>-${{ number_format($appointment->discount_amount, 0, ',', '.') }} COP</span>
                </div>
            @endif
            <div class="flex justify-between text-base font-bold text-gray-900 border-t pt-2">
                <span>Total Pagado</span>
                <span>${{ number_format($netTotal, 0, ',', '.') }} COP</span>
            </div>
        </div>

        <p class="text-center text-xs text-gray-400 mt-8">
            Este documento es un comprobante informativo de pago procesado a través de <a href="https://opendoctor.online" target="_blank">OpenDoctorOnline</a>.
        </p>
    </div>
</body>
</html>
