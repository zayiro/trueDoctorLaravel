<x-guest-layout>
    <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 text-center">
                
                <!-- CASO 1: PAGO APROBADO -->
                @if($paymentStatus === 'APPROVED')
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-emerald-100 mb-4">
                        <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">¡Pago Exitoso!</h2>
                    <p class="text-sm text-gray-600 mb-6">
                        Hemos procesado tu pago correctamente. Se ha enviado un comprobante a tu correo electrónico.
                    </p>
                    
                    <!-- Botón para ir a la vista de visualización completa (show) -->
                    <a href="{{ route('medical-analysis.show', $analysis->access_token) }}" 
                       class="w-full inline-flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 transition duration-150">
                        Ver tu informe completo
                    </a>

                <!-- CASO 2: PAGO PENDIENTE (PSE o Corresponsal Bancario) -->
                @elseif($paymentStatus === 'PENDING')
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-amber-100 mb-4">
                        <svg class="h-6 w-6 text-amber-600 class="animate-pulse"" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Pago en Verificación</h2>
                    <p class="text-sm text-gray-600 mb-6">
                        Tu entidad bancaria está procesando la transacción. Esto puede tomar unos minutos.
                    </p>
                    <a href="{{ route('home') }}" 
                       class="w-full inline-flex justify-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition duration-150">
                        Volver al Inicio
                    </a>

                <!-- CASO 3: RECHAZADO, FALLIDO O ERROR -->
                @else
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-rose-100 mb-4">
                        <svg class="h-6 w-6 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Transacción Declinada</h2>
                    <p class="text-sm text-gray-600 mb-6">
                        La pasarela de pago no pudo completar el cobro. Ningún cargo ha sido efectuado.
                    </p>
                    
                    <!-- Botón para reintentar el pago enviándolo de vuelta a la vista anterior donde estaba tu botón original -->
                    <a href="{{ url('/medical-analysis/result/' . $analysis->access_token) }}" 
                       class="w-full inline-flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 transition duration-150">
                        Reintentar pago
                    </a>
                @endif

            </div>
        </div>
    </div>
</x-guest-layout>
