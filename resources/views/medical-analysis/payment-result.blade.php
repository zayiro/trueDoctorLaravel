<x-guest-layout>
    <div class="bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
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
                    
                    <a href="{{ route('medical-analysis.payment-gateway', $analysis->access_token) }}" 
                    class="w-full inline-flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 transition duration-150 shadow-lg">
                        Reintentar pago
                    </a>
                @endif

            </div>
        </div>

        <!-- Slider de Formas de Pago -->
<div class="mt-12 pt-8 border-t">
    <h3 class="text-2xl font-bold text-gray-900 mb-8 text-center">Formas de Pago Disponibles</h3>
    
    <div class="swiper payment-methods-slider">
        <div class="swiper-wrapper">
            <!-- Tarjeta de Crédito -->
            <div class="swiper-slide">
                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20 8H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm0 4h-16v2h16v-2z"/>
                    </svg>
                    <p class="font-semibold text-gray-900">Tarjeta de Crédito</p>
                </div>
            </div>

            <!-- Tarjeta de Débito -->
            <div class="swiper-slide">
                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20 8H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm0 4h-16v2h16v-2z"/>
                    </svg>
                    <p class="font-semibold text-gray-900">Tarjeta de Débito</p>
                </div>
            </div>

            <!-- Transferencia Bancaria -->
            <div class="swiper-slide">
                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/>
                    </svg>
                    <p class="font-semibold text-gray-900">Transferencia Bancaria</p>
                </div>
            </div>

            <!-- Nequi / Billetera Digital -->
            <div class="swiper-slide">
                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-orange-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/>
                    </svg>
                    <p class="font-semibold text-gray-900">Billetera Digital</p>
                </div>
            </div>

            <!-- Efectivo -->
            <div class="swiper-slide">
                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-yellow-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6z"/>
                    </svg>
                    <p class="font-semibold text-gray-900">Efectivo</p>
                </div>
            </div>
        </div>

        <!-- Navegación -->
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
    </div>
</div>
    </div>

    <!-- Swiper CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
    const swiper = new Swiper('.payment-methods-slider', {
        slidesPerView: 1,
        spaceBetween: 20,
        breakpoints: {
            640: {
                slidesPerView: 2,
            },
            1024: {
                slidesPerView: 4,
            },
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        loop: true,
    });
</script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof gtag === 'function') {
                const paymentStatus = @json($paymentStatus);
                const analysisId = @json($analysis->id ?? '');
                const analysisToken = @json($analysis->access_token ?? '');

                // 📊 EVENTO: Resultado de pago de análisis (independiente del estado)
                gtag('event', 'lab_analysis_payment_processed', {
                    'transaction_id': analysisId,
                    'payment_status': paymentStatus,
                    'payment_gateway': 'wompi',
                    'analysis_token': analysisToken,
                    'feature_type': 'lab_analysis'
                });

                // ✅ Si fue APPROVED → Análisis pagado exitosamente
                @if($paymentStatus === 'APPROVED')
                gtag('event', 'purchase', {
                    'transaction_id': analysisId,
                    'payment_status': 'approved',
                    'payment_gateway': 'wompi',
                    'items': [{
                        'item_id': analysisId,
                        'item_name': 'Lab Analysis Report',
                        'item_category': 'lab_analysis_premium'
                    }],
                    'currency': 'COP',
                    'feature_type': 'lab_analysis'
                });

                // 🎯 Trackear click en "Ver tu informe completo"
                const viewReportBtn = document.querySelector('a[href*="medical-analysis.show"]');
                if (viewReportBtn) {
                    viewReportBtn.addEventListener('click', function() {
                        if (typeof gtag === 'function') {
                            gtag('event', 'view_lab_analysis_report', {
                                'transaction_id': analysisId,
                                'payment_status': 'approved',
                                'feature_type': 'lab_analysis'
                            });
                        }
                    });
                }
                @endif

                // ⏳ Si está PENDING → Pago en verificación
                @if($paymentStatus === 'PENDING')
                gtag('event', 'lab_analysis_payment_pending', {
                    'transaction_id': analysisId,
                    'payment_gateway': 'wompi',
                    'feature_type': 'lab_analysis'
                });
                @endif

                // ❌ Si fue rechazado/fallido
                @if($paymentStatus !== 'APPROVED' && $paymentStatus !== 'PENDING')
                gtag('event', 'lab_analysis_payment_failed', {
                    'transaction_id': analysisId,
                    'payment_status': paymentStatus,
                    'payment_gateway': 'wompi',
                    'feature_type': 'lab_analysis'
                });

                // Trackear click en "Reintentar pago"
                const retryBtn = document.querySelector('a[href*="medical-analysis/result"]');
                if (retryBtn) {
                    retryBtn.addEventListener('click', function() {
                        if (typeof gtag === 'function') {
                            gtag('event', 'lab_analysis_payment_retry', {
                                'transaction_id': analysisId,
                                'previous_status': paymentStatus,
                                'feature_type': 'lab_analysis'
                            });
                        }
                    });
                }
                @endif
            }
        });
    </script>
</x-guest-layout>
