@php
    $appointmentDate = \Carbon\Carbon::parse($appointment->date)->format('Y-m-d');
    // Combinamos la fecha y la hora de inicio de la cita en un solo string
    $appointmentFullDateTime = \Carbon\Carbon::parse($appointmentDate . ' ' . $appointment->start_time, 'America/Bogota');
    $now = \Carbon\Carbon::now('America/Bogota');

    // 🧹 LIMPIEZA ANTIFRESH: Recuperamos los cupones pendientes (si existen) y los borramos de la sesión
    $savedCouponCode = session()->pull('pending_promo_code', '');
    $savedDiscountAmount = session()->pull('pending_discount_amount', 0);
@endphp

<x-guest-layout>
    <script>
        (function() {
            window.history.pushState(null, "", window.location.href);
            window.addEventListener('popstate', function(event) {
                window.history.pushState(null, "", window.location.href);
                alert("Para salvaguardar la integridad de tu cita médica, no puedes retroceder usando los controles del navegador. Utiliza el enlace de cancelación al pie de la página si deseas regresar.");
            });
        })();
    </script>
    <div class="max-w-5xl mx-auto py-12 px-4 mt-6">
        @if(session('error'))
            <div class="flex items-center p-4 mb-4 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm" role="alert">
                <svg class="flex-shrink-0 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <div class="ms-3 text-sm font-bold">
                    {{ session('error') }}
                </div>
            </div>
        @endif
        
        <div class="bg-white rounded-[2.5rem] shadow-xl overflow-hidden border border-slate-100 relative">
            <div class="h-2 w-full bg-indigo-600 absolute top-0 left-0"></div>
            
            <div class="bg-slate-900 p-8 text-center text-white pt-10">
                <span class="text-[10px] font-black text-indigo-400 uppercase tracking-[0.2em] mb-1 block">Paso de Verificación</span>
                <h2 class="text-2xl font-black text-white tracking-tight">Resumen de tu Orden Médica</h2>                
                <p class="text-sm text-slate-400 mt-1">Valida los datos de tu especialista antes de proceder a la confirmación de la agenda.</p>
            </div>
            
            <div class="p-8 space-y-5">
                <!-- Profesional de la Salud -->
                <div class="flex justify-between items-center border-b border-slate-100 pb-4">
                    <span class="text-slate-400 text-sm font-black uppercase tracking-wider">Especialista Asignado</span>
                    <span class="font-extrabold text-sm text-slate-800">
                        {{ $appointment->doctor->gender === 'male' ? 'Dr. ' . ucfirst($appointment->doctor->user->name) : 'Dra. ' . ucfirst($appointment->doctor->user->name) }}
                    </span>
                </div>
                <!-- Lugar de la consulta unificado (Soporte Clínicas Corporativas) -->
                <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100">
                    <div class="flex items-start gap-4">
                        <div class="bg-indigo-100 p-3 rounded-xl text-indigo-600 flex-shrink-0">
                            @if($appointment->service->type === 'virtual')
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            @else
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                            @endif
                        </div>

                        <div class="space-y-0.5">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Ubicación y Sede</h3>
                            @if($appointment->service->type === 'virtual')
                                <p class="text-base font-black text-slate-800">Consulta Virtual / Telemedicina</p>
                                <p class="text-purple-700 font-semibold text-sm bg-purple-50 px-2 py-0.5 rounded-md inline-block mt-1 border border-purple-100/50">¡Todo listo! El enlace para tu videoconsulta se enviará a tu correo. También puedes ingresar directamente desde tu perfil en la sección Mis Citas.</p>
                            @else
                                <p class="text-base font-black text-slate-800">{{ $appointment->address->name }}</p>
                                <p class="text-sm text-slate-500 font-medium">
                                    {{ $appointment->address->city->name ?? '' }}@if($appointment->address->address_line || $appointment->address->address), {{ $appointment->address->address_line ?? $appointment->address->address }}@endif
                                </p>
                                
                                @if($appointment->clinic_id && $appointment->clinic)
                                    <span class="inline-block text-[9px] font-black text-indigo-700 bg-indigo-50 px-2.5 py-0.5 rounded-md border border-indigo-100/40 mt-1.5 uppercase">
                                        🏢 Sede Institucional: {{ $appointment->clinic->name }}
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                <!-- Servicio Clínico -->
                <div class="flex justify-between items-center border-b border-slate-100 pb-4">
                    <span class="text-slate-400 text-xs font-black uppercase tracking-wider">Servicio</span>
                    <div class="text-sm text-slate-400 tracking-wider block mb-1">
                        <span class="font-bold text-slate-700 text-end">{{ $appointment->service->name }}</span>
                        <div class="text-end">{{ $appointment->service->type === 'virtual' ? 'Cita Virtual' : 'Cita Presencial' }}</div>
                    </div>
                </div>

                <!-- Fecha de la Agenda -->
                <div class="flex justify-between items-center border-b border-slate-100 pb-4">
                    <span class="text-slate-400 text-xs font-black uppercase tracking-wider">Fecha</span>
                    <div class="text-end">
                        <span class="font-bold text-sm text-slate-800 block">
                            {{ ucfirst(\Carbon\Carbon::parse($appointmentDate)->translatedFormat('l, d \d\e F \d\e Y')) }}
                        </span>
                        
                        <span class="text-[10px] text-indigo-600 font-bold block mt-1 bg-indigo-50 px-2 py-0.5 rounded-md inline-block border border-indigo-100/50">
                            @if($now->lessThan($appointmentFullDateTime))
                                {{ $appointmentFullDateTime->diffForHumans($now, [
                                    'syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW,
                                    'parts' => 2
                                ]) }}
                            @else
                                <span class="text-slate-400">El bloque horario ya inició</span>
                            @endif                            
                        </span>
                    </div>
                </div>

                <!-- Hora de la Agenda -->
                <div class="flex justify-between items-center border-b border-slate-100 pb-4">
                    <span class="text-slate-400 text-xs font-black uppercase tracking-wider">Horario</span>                    
                    <span class="font-bold text-sm text-slate-800 bg-white border px-3 py-1 rounded-xl shadow-sm">
                        {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }} a 
                        {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}
                    </span>
                </div>        
                @php
                    if ($appointment->clinic_id && $appointment->clinic) {
                        $acceptsOnlinePayments = (bool) ($appointment->clinic->settings->accepts_online_payments ?? false);
                    } else {
                        $acceptsOnlinePayments = (bool) ($appointment->doctor->settings->accepts_online_payments ?? false);
                    }
                @endphp
                
                <!-- Forma de pago -->                
                <div class="flex justify-between items-center border-b border-slate-100 pb-4">
                    <div class="text-sm text-start text-slate-400 tracking-wider block mb-1">
                        <span class="text-slate-400 text-xs font-black uppercase tracking-wider">Forma de Pago</span>
                        <div class="text-xs text-slate-900 font-light"></div>                        
                    </div>
                     @if ($virtualPaymentRequired)
                        <span class="text-sm font-bold text-slate-700">Pago en Línea</span>
                    @else
                        <span class="text-sm font-bold text-slate-700">{{ $acceptsOnlinePayments ? 'Pago en Línea' : 'Pago en Consulta' }}</span>
                    @endif                    
                </div>
                <div class="flex items-start sm:items-center bg-blue-50 border border-blue-100 rounded-2xl p-4 mb-4 text-slate-500" role="alert">
                    <svg class="w-4 h-4 me-2 shrink-0 mt-0.5 sm:mt-0" aria-hidden="true" xmlns="http://w3.org" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11h2v5m-2 0h4m-2.592-8.5h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    <p><span class="text-slate-500 font-medium me-1">La disponibilidad y el precio de la consulta médica no están garantizados hasta que finalice el pago.</span></p>
                </div>
                
                <!-- CONTENEDOR PRINCIPAL INTEGRADO CON ALPINE.JS -->
                <div x-data="{
                    couponCode: '',
                    discount: 0,
                    couponPercentage: 0,
                    errorMessage: '',
                    successMessage: '',
                    loading: false,
                    
                    subtotal: {{ $virtualPaymentRequired && $wompiData ? $wompiData['total'] : $appointment->price }},
                    isWompi: {{ ($virtualPaymentRequired && $wompiData) ? 'true' : 'false' }},
                    wompiBase: {{ $appointment->price }},
                    wompiCommission: {{ ($virtualPaymentRequired && $wompiData) ? $wompiData['commission_amount'] : 0 }},
                    appointmentId: @json($appointment->id),
                    wompiCheckoutUrl: '{{ $wompiData['checkout_url'] ?? '' }}',

                    formatCOP(value) {
                        return new Intl.NumberFormat('es-CO', {
                            style: 'currency',
                            currency: 'COP',
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 0
                        }).format(value);
                    },

                    get total() {
                        return Math.max(0, this.subtotal - this.discount);
                    },

                    get isFree() {
                        return this.total === 0;
                    },

                    async applyCoupon() {
                        if (!this.couponCode.trim()) return;
                        
                        this.loading = true;
                        this.errorMessage = '';
                        this.successMessage = '';

                        try {
                            let response = await fetch('{{ route('appointments.promo.validate') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({ code: this.couponCode, appointment_id: this.appointmentId, subtotal: this.subtotal })
                            });

                            let data = await response.json();
                            console.log(data);

                            // Cambiamos 'data.valid' por 'data.success' que es lo que envía tu Laravel
                            if (response.ok && data.success) {
                                this.discount = parseFloat(data.discount_amount);
                                // Convertimos 'reward' (10.00) a un entero limpio (10) para el porcentaje
                                this.couponPercentage = parseInt(data.reward); 
                                this.successMessage = `¡Cupón ${data.code} aplicado con éxito!`;

                                // Si se regeneró la pasarela de Wompi con el precio con descuento
                                if (data.wompiData) {
                                    this.isWompi = true;
                                    this.wompiCheckoutUrl = data.wompiData.checkout_url;

                                    console.log(data.wompiData.checkout_url);
                                    
                                    // Si tu backend recalcula comisiones dinámicas, actualízalas aquí
                                    if (data.wompiData.commission_amount) {
                                        this.wompiCommission = data.wompiData.commission_amount;
                                    }
                                }
                            } else {
                                this.discount = 0;
                                this.couponPercentage = 0;
                                this.errorMessage = data.message || 'Código inválido';
                            }
                        } catch (error) {
                            this.errorMessage = 'Error al procesar el cupón. Intenta de nuevo.';
                        } finally {
                            this.loading = false;
                        }
                    },

                    handleAction(event) {
                        if (this.isFree) {
                            event.preventDefault();
                            window.location.href = '{{ route('appointments.success', $appointment) }}';
                        }
                    }
                }">

                    <!-- TABLA DE VALORES Y DESGLOSE DINÁMICO -->
                    <div class="pt-4 text-center border-b border-slate-100 pb-6">
                        <!-- Desglose si usa pasarela Wompi -->
                        <template x-if="isWompi && !isFree">
                            <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 mb-4">
                                <div class="flex items-center justify-between text-md mb-1">
                                    <span class="text-slate-500 font-medium">Valor base</span>
                                    <span class="font-bold text-slate-700" x-text="formatCOP(wompiBase)"></span>
                                </div>
                                <div class="flex items-center justify-between text-md mb-1">
                                    <span class="text-slate-500 font-medium">Comisión plataforma</span>
                                    <span class="font-bold text-slate-700" x-text="formatCOP(wompiCommission)"></span>
                                </div>
                                <div class="flex items-center justify-between text-md border-t border-blue-200 pt-2 mt-2">
                                    <span class="font-black text-slate-800">Total a pagar</span>
                                    <span class="font-black text-blue-700 text-base">${{ number_format($wompiData['total'], 0, ',', '.') }}</span>
                                </div>
                                <div class="flex items-center justify-between text-md text-green-600 font-semibold mb-1" x-show="discount > 0" x-cloak>
                                    <span>Código promocional (<span x-text="couponPercentage"></span>%)</span>
                                    <span x-text="'-' + formatCOP(discount)"></span>                                    
                                </div>
                            </div>
                        </template>

                        <!-- Desglose estándar (Sin Wompi) -->
                        <template x-if="!isWompi && discount > 0 && !isFree">
                            <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 mb-4 text-left max-w-sm mx-auto mt-2">
                                <div class="flex items-center justify-between text-md mb-1">
                                    <span class="text-slate-500 font-medium">Subtotal</span>
                                    <span class="font-bold text-slate-700" x-text="formatCOP(subtotal)"></span>
                                </div>
                                <div class="flex items-center justify-between text-md text-green-600 font-semibold">
                                    <span>Código promocional (<span x-text="couponPercentage"></span>%)</span>
                                    <span x-text="'-' + formatCOP(discount)"></span>
                                </div>
                            </div>
                        </template>

                        <!-- Precio de cara al paciente -->
                        <div class="flex items-center justify-between text-md pt-2 mt-2" x-show="discount > 0" x-cloak>
                            <span class="text-indigo-500 font-bold">Pago Total con descuento</span>
                            <span class="text-3xl font-black text-indigo-600 tracking-tight" x-text="formatCOP(total) + ' COP'"></span>
                        </div>
                        
                    </div>
                    <!-- CAMPO DEL CÓDIGO PROMOCIONAL -->
                    <div class="mt-6 mx-auto text-left" x-show="discount === 0" x-transition x-cloak>
                        <label for="coupon" class="block text-[10px] font-black text-slate-600 uppercase tracking-wider mb-2">¿Tienes un código de descuento?</label>
                        <div class="flex space-x-2">
                            <input type="text" id="coupon" 
                                   x-model="couponCode"
                                   :disabled="discount > 0 || loading"
                                   placeholder="EJ: DOCTOR10" 
                                   class="flex-1 min-w-0 block w-full px-4 py-3 rounded-xl text-slate-900 placeholder-slate-400 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm uppercase font-bold disabled:bg-slate-50 disabled:text-slate-400">
                            
                            <button type="button" 
                                    @click="applyCoupon"
                                    :disabled="!couponCode.trim() || discount > 0 || loading"
                                    class="inline-flex items-center px-5 py-3 text-sm font-bold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none transition disabled:opacity-40 disabled:cursor-not-allowed">
                                <span x-show="!loading">Aplicar</span>
                                <span x-show="loading" x-cloak>...</span>
                            </button>
                        </div>

                        <!-- Mensajes de error/éxito unificados -->
                        <div x-show="errorMessage || successMessage" 
                            :class="{
                                'bg-red-50 border-red-100 text-red-700': errorMessage,
                                'bg-emerald-50 border-emerald-100 text-emerald-700': successMessage
                            }" 
                            class="mt-3 p-4 rounded-xl border text-xs font-bold transition-all duration-150" 
                            x-cloak>
                            <p x-text="errorMessage ? errorMessage : successMessage"></p>
                        </div>
                    </div>

                    <!-- FLUJOS DE ACCIÓN FINALES -->
                    <div class="mt-8 border-t border-slate-50 pt-6">
                        
                        <!-- Condición 1: Cita gratis (100% de descuento) -->
                        <div x-show="isFree" x-cloak>
                            <form action="{{ route('appointments.success', $appointment) }}" method="GET">                                
                                <button type="submit" @click="handleAction($event)" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-4 rounded-2xl font-black shadow-lg shadow-emerald-100 transition-all text-sm uppercase tracking-wider flex items-center justify-center gap-2">
                                    <span>Confirmar Cita Gratis</span>
                                </button>
                            </form>
                        </div>

                        <!-- Condición 2: Flujo comercial normal con pasarelas activas -->
                        <div x-show="!isFree">
                            @if($acceptsOnlinePayments)
                                <form action="{{ route('appointments.confirm', $appointment->id) }}" method="GET">                                
                                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-4 rounded-2xl font-black shadow-lg shadow-emerald-100 transition-all flex items-center justify-center gap-2 text-sm uppercase tracking-wider focus:outline-none">
                                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>
                                        </svg>
                                        Proceder al Pago Online
                                    </button>
                                </form>
                            @else
                                @if($virtualPaymentRequired && $wompiData)
                                    <a :href="wompiCheckoutUrl"
                                        class="w-full text-white py-4 rounded-2xl font-black shadow-lg shadow-blue-100 transition-all text-sm uppercase tracking-wider flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.75z"/>
                                        </svg>
                                        <span>Pagar cita virtual</span>
                                    </a>                            
                                @else
                                    <form action="{{ route('appointments.success', $appointment) }}" method="GET">                                
                                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-4 rounded-2xl font-black shadow-lg shadow-indigo-100 transition-all text-sm uppercase tracking-wider focus:outline-none flex items-center justify-center gap-2">
                                            <span>Confirmar y Finalizar Reserva</span>
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>

                        <!-- Enlace de escape unificado -->
                        <div class="mt-6 text-center">
                            <form action="{{ route('appointments.cancel-flow') }}" method="POST" onsubmit="return confirm('¿De verdad deseas cancelar este proceso de reserva y volver al home global?');">
                                @csrf
                                <input type="hidden" name="id" value="{{ $appointment->id }}">
                                <button type="submit" class="text-sm font-semibold text-slate-400 hover:text-red-500 underline transition-colors duration-150 focus:outline-none cursor-pointer">
                                    Regresar y cambiar datos
                                </button>
                            </form>
                        </div>

                    </div>
                </div> <!-- Cierre de x-data -->

                <div class="w-full py-6 px-4">
                    <div class="max-w-5xl mx-auto">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <!-- Item 1: Opciones de pago seguro -->
                            <div class="flex flex-col items-center text-center">
                                <div class="mb-4">
                                    <svg class="w-12 h-12 text-green-600 mx-auto" xmlns="http://w3.org" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                        <path d="M1 10h22"></path>
                                    </svg>
                                </div>
                                <p class="text-gray-600 text-sm leading-relaxed">Opciones de pago seguro</p>
                            </div>

                            <!-- Item 2: Privacidad segura -->
                            <div class="flex flex-col items-center text-center">
                                <div class="mb-4">
                                    <svg class="w-12 h-12 text-green-600 mx-auto" xmlns="http://w3.org" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                    </svg>
                                </div>
                                <p class="text-gray-600 text-sm leading-relaxed">Privacidad segura</p>
                            </div>

                            <!-- Item 3: Protección de compra -->
                            <div class="flex flex-col items-center text-center">
                                <div class="mb-4">
                                    <svg class="w-12 h-12 text-green-600 mx-auto" xmlns="http://w3.org" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </div>
                                <p class="text-gray-600 text-sm leading-relaxed">Protección de compra</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPT DE GOOGLE ANALYTICS DINÁMICO -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof gtag === 'function') {
                const isVirtual = @json($appointment->service->type === 'virtual');
                const appointmentId = @json($appointment->id);
                
                let finalPrice = @json($virtualPaymentRequired && $wompiData ? $wompiData['total'] : $appointment->price);
                
                const alpineElement = document.querySelector('[x-data]');
                if (alpineElement && alpineElement.__x && alpineElement.__x.$data) {
                    finalPrice = alpineElement.__x.$data.total;
                }

                gtag('event', 'add_shipping_info', {
                    'currency': 'COP',
                    'value': finalPrice,
                    'appointment_type': isVirtual ? 'virtual' : 'presencial',
                    'appointment_id': appointmentId
                });
            }
        });
    </script>
</x-guest-layout>
