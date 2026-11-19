<x-guest-layout>
    <div class="max-w-5xl mx-auto px-6 py-12">
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">Procesar Pago</h1>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                <p class="text-gray-700 mb-4">
                    <strong>Tipo de examen:</strong>
                    @if($analysis->exam_type)
                        {{ ucfirst(str_replace('_', ' ', $analysis->exam_type)) }}
                    @else
                        No especificado
                    @endif
                </p>
                <p class="text-gray-700 mb-4">
                    <strong>Email:</strong> {{ $analysis->customer_email }}
                </p>
                @if (($fee ?? 0) > 0)
                    <p class="text-gray-700 mb-2">
                        <strong>Precio del análisis:</strong> ${{ number_format($price - $fee, 0, ',', '.') }} COP
                    </p>
                    <p class="text-gray-700 mb-2">
                        <strong>Comisión de pasarela:</strong> ${{ number_format($fee, 0, ',', '.') }} COP
                    </p>
                @endif
                <div class="border-t pt-4 mt-4">
                    <p class="text-2xl font-bold text-gray-900">
                        Total a pagar: ${{ number_format($price, 0, ',', '.') }} COP
                    </p>
                </div>
            </div>

            <a href="{{ $checkoutUrl }}"
               class="block w-full text-center bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-bold py-3 px-6 rounded-lg transition-all shadow-lg">
                Ir a Pagar con Wompi
            </a>

            <p class="text-gray-500 text-sm mt-8 text-center">
                Serás redirigido a la pasarela de pago de Wompi
            </p>
        </div>
    </div>
</x-guest-layout>
