<x-guest-layout>
    <div class="max-w-5xl mx-auto px-6 py-12">
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">Procesar Pago</h1>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                <p class="text-gray-700 mb-4">
                    <strong>Tipo de examen:</strong> {{ ucfirst($analysis->exam_type) }}
                </p>
                <p class="text-gray-700 mb-4">
                    <strong>Email:</strong> {{ $analysis->customer_email }}
                </p>
                <div class="border-t pt-4 mt-4">
                    <p class="text-2xl font-bold text-gray-900">
                        Total a pagar: ${{ number_format($price, 0, ',', '.') }} COP
                    </p>
                </div>
            </div>

            <form id="wompi-form" action="https://checkout.wompi.co/p/" method="GET">
                <input type="hidden" name="public-key" value="{{ $wompi['public_key'] }}">
                <input type="hidden" name="currency" value="{{ $wompi['currency'] }}">
                <input type="hidden" name="amount-in-cents" value="{{ $wompi['amount_in_cents'] }}">
                <input type="hidden" name="reference" value="{{ $wompi['reference'] }}">
                <input type="hidden" name="signature:integrity" value="{{ $wompi['signature_integrity'] }}">
                <input type="hidden" name="redirect-url" value="{{ $wompi['redirect_url'] }}">
                <input type="hidden" name="customer-data:email" value="{{ $analysis->customer_email }}" />
                
                <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-bold py-3 px-6 rounded-lg transition-all shadow-lg">
                    Ir a Pagar con Wompi
                </button>
            </form>

            <p class="text-gray-500 text-sm mt-8 text-center">
                Serás redirigido a la pasarela de pago de Wompi
            </p>
        </div>
    </div>
</x-guest-layout>