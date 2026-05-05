<x-guest-layout>
    <div class="max-w-5xl mx-auto py-12 px-4 mt-6">
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
            <div class="md:flex">
                {{-- Resumen del Plan --}}
                <div class="md:w-1/2 bg-blue-600 p-10 text-white">
                    <h2 class="text-3xl font-black mb-6">Resumen de tu suscripción</h2>
                    <div class="space-y-6">
                        <div class="flex justify-between items-center border-b border-blue-500 pb-4">
                            <span>Plan seleccionado:</span>
                            <span class="font-bold text-xl">{{ $plan->name }}</span>
                        </div>
                        <div class="space-y-2">
                            <p class="text-sm opacity-80 italic">Incluye:</p>
                            <ul class="text-sm space-y-2">
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                                    {{ $plan->max_addresses }} Sedes físicas
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                                    {{ $plan->max_services }} Servicios globales
                                </li>
                            </ul>
                        </div>
                        <div class="pt-10">
                            <p class="text-sm opacity-80">Total a pagar hoy:</p>
                            <p class="text-5xl font-black">${{ number_format($plan->price, 0) }} <span class="text-lg font-normal">COP</span></p>
                        </div>
                    </div>
                </div>

                {{-- Opciones de Pago --}}
                <div class="md:w-1/2 p-10 bg-white">
                    <h3 class="text-xl font-bold text-gray-800 mb-6">Finalizar pago</h3>
                    
                    <p class="text-gray-600 text-sm mb-8">
                        Al confirmar, serás redirigido a nuestra pasarela de pagos segura para completar la transacción.
                    </p>

                    <div class="space-y-4">
                        {{-- Botón de Pago con Pasarela (Ej. Wompi/MercadoPago) --}}
                        {{-- route('plans.checkout', $plan->id) --}}
                        <form action="#" method="POST">
                            @csrf
                            <button type="submit" class="w-full bg-green-600 text-white py-4 rounded-2xl font-black shadow-lg hover:bg-green-700 transition flex items-center justify-center gap-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                Pagar ahora de forma segura
                            </button>
                        </form>

                        <p class="text-center text-xs text-gray-400">
                            🔒 Tus datos están protegidos con encriptación SSL.
                        </p>
                    </div>

                    <div class="mt-12 pt-6 border-t border-gray-100">
                        <a href="{{ route('plans.index') }}" class="text-sm text-gray-500 hover:text-blue-600 font-bold flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Cambiar de plan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
