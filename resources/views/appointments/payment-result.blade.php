<x-guest-layout>
    <div class="max-w-4xl mx-auto py-12 px-4 mt-6">
        <div class="bg-white rounded-[2.5rem] shadow-xl overflow-hidden border border-slate-100 relative p-5">

            @if($status === 'approved')
                {{-- Este caso ya redirige a success, pero por si acaso --}}
                <div class="flex flex-col items-center gap-4 text-center">
                    <div class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-black text-slate-800">¡Pago exitoso!</h1>
                </div>

            @else
                {{-- DECLINED / PENDING --}}
                <div x-data="{ retrying: false }" class="flex flex-col gap-6">

                    {{-- Ícono y título --}}
                    <div class="flex flex-col items-center text-center gap-3">
                        <div class="w-20 h-20 rounded-full {{ $status === 'declined' ? 'bg-red-100' : 'bg-yellow-100' }} flex items-center justify-center">
                            @if($status === 'declined')
                                <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            @else
                                <svg class="w-10 h-10 text-yellow-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/>
                                </svg>
                            @endif
                        </div>
                        <h1 class="text-2xl font-black text-slate-800">
                            {{ $status === 'declined' ? 'Pago no completado' : 'Pago pendiente' }}
                        </h1>
                        <p class="text-slate-500 text-sm leading-relaxed">
                            {{ $transaction['status_message'] ?? 'La transacción no fue procesada.' }}
                            <strong class="text-slate-700 block mt-1">Tu reserva sigue guardada por 2 horas.</strong>
                        </p>
                    </div>

                    {{-- Resumen de la cita --}}
                    <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 space-y-3">
                        <p class="text-xs font-black text-slate-400 uppercase tracking-wider">Tu cita reservada</p>

                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-800">
                                    Dr(a). {{ $appointment->doctor->user->name ?? 'Especialista' }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ $appointment->service->name ?? 'Consulta virtual' }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="bg-white rounded-xl p-3 border border-slate-100">
                                <p class="text-slate-400 font-medium">Fecha</p>
                                <p class="font-bold text-slate-700">
                                    {{ \Carbon\Carbon::parse($appointment->date)->format('d/m/Y') }}
                                </p>
                            </div>
                            <div class="bg-white rounded-xl p-3 border border-slate-100">
                                <p class="text-slate-400 font-medium">Hora</p>
                                <p class="font-bold text-slate-700">
                                    {{ \Carbon\Carbon::parse($appointment->start_time)->format('H:i') }}
                                </p>
                            </div>
                            <div class="bg-white rounded-xl p-3 border border-slate-100 col-span-2">
                                <p class="text-slate-400 font-medium">Total a pagar</p>
                                <p class="font-black text-blue-700">
                                    ${{ number_format(($appointment->price + $appointment->commission_amount), 0, ',', '.') }} COP
                                </p>
                            </div>
                        </div>

                        {{-- Countdown 2 horas --}}
                        <div class="bg-amber-50 border border-amber-100 rounded-xl p-3 flex items-center gap-2"
                            x-data="{
                                seconds: {{ (int) max(0, now()->diffInSeconds($appointment->updated_at->addHours(2))) }},
                                get timeLeft() {
                                    if (this.seconds <= 0) return '00:00:00';
                                    let h = Math.floor(this.seconds / 3600);
                                    let m = Math.floor((this.seconds % 3600) / 60);
                                    let s = this.seconds % 60;
                                    return `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
                                },
                                start() {
                                    setInterval(() => { if (this.seconds > 0) this.seconds--; }, 1000);
                                }
                            }"
                            x-init="start()">
                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                            <p class="text-xs text-amber-700 font-medium">
                                Reserva disponible por: <span class="font-black" x-text="timeLeft"></span>
                            </p>
                        </div>
                    </div>

                    {{-- Botones --}}
                    <div class="flex flex-col gap-3">
                        {{-- Reintentar pago --}}
                        <a href="{{ route('appointments.preview', $appointment->id) }}"
                            @click="retrying = true"
                            :class="retrying ? 'opacity-70 pointer-events-none' : ''"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-4 rounded-2xl text-sm uppercase tracking-wider transition flex items-center justify-center gap-2">
                            <svg x-show="retrying" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24" x-cloak>
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <svg x-show="!retrying" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                            </svg>
                            <span x-text="retrying ? 'Cargando...' : 'Reintentar pago'">Reintentar pago</span>
                        </a>

                        {{-- Contactar soporte --}}
                        <a href="https://wa.me/573026433874?text=Hola,%20tuve%20un%20problema%20con%20mi%20pago%20para%20la%20cita%20ref:%20{{ $appointment->reference }}"
                            target="_blank"
                            class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 rounded-2xl text-sm transition flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                <path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.116 1.526 5.845L.057 23.571l5.887-1.543A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 0 1-5.006-1.372l-.359-.214-3.724.976.995-3.632-.234-.373A9.818 9.818 0 1 1 12 21.818z"/>
                            </svg>
                            Hablar con soporte
                        </a>

                        <a href="{{ route('home') }}" class="text-center text-xs text-slate-400 hover:text-slate-600 transition font-medium py-2">
                            Volver al inicio
                        </a>
                    </div>

                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof gtag === 'function') {
                const status = @json($status);
                const appointmentId = @json($appointment->id);
                const appointmentPrice = @json($appointment->price);
                const appointmentCommission = @json($appointment->commission_amount ?? 0);
                const totalPrice = appointmentPrice + appointmentCommission;
                const doctorId = @json($appointment->doctor_id);
                const doctorName = @json($appointment->doctor->user->name ?? '');
                
                // 🎯 EVENTO: Resultado de pago (independiente del estado)
                gtag('event', 'payment_processed', {
                    'transaction_id': appointmentId,
                    'payment_status': status,
                    'value': totalPrice,
                    'currency': 'COP',
                    'payment_gateway': 'wompi',
                    'items': [{
                        'item_id': doctorId,
                        'item_name': doctorName,
                        'item_category': 'appointment_virtual'
                    }]
                });

                // ✅ Si fue APPROVED → Purchase completado
                @if($status === 'approved')
                gtag('event', 'purchase', {
                    'transaction_id': appointmentId,
                    'value': totalPrice,
                    'currency': 'COP',
                    'payment_method': 'wompi',
                    'payment_status': 'approved',
                    'items': [{
                        'item_id': doctorId,
                        'item_name': doctorName,
                        'item_category': 'appointment_virtual'
                    }],
                    'appointment_date': @json($appointment->date),
                    'appointment_time': @json($appointment->start_time)
                });
                @endif

                // ❌ Si fue DECLINED → Fallo de pago
                @if($status === 'declined')
                gtag('event', 'payment_failed', {
                    'transaction_id': appointmentId,
                    'value': totalPrice,
                    'currency': 'COP',
                    'payment_gateway': 'wompi',
                    'failure_reason': @json($transaction['status_message'] ?? 'Payment declined'),
                    'appointment_id': appointmentId
                });

                // Trackear click en "Reintentar pago"
                const retryBtn = document.querySelector('a[href*="appointments.preview"]');
                if (retryBtn) {
                    retryBtn.addEventListener('click', function() {
                        if (typeof gtag === 'function') {
                            gtag('event', 'payment_retry_clicked', {
                                'transaction_id': appointmentId,
                                'previous_status': 'declined'
                            });
                        }
                    });
                }
                @endif

                // ⏳ Si está PENDING → Revisión de pago
                @if($status === 'pending')
                gtag('event', 'payment_pending', {
                    'transaction_id': appointmentId,
                    'value': totalPrice,
                    'currency': 'COP',
                    'payment_gateway': 'wompi',
                    'appointment_id': appointmentId
                });
                @endif

                // 💬 Trackear click en "Hablar con soporte"
                const supportBtn = document.querySelector('a[href*="wa.me"]');
                if (supportBtn) {
                    supportBtn.addEventListener('click', function() {
                        if (typeof gtag === 'function') {
                            gtag('event', 'contact_support', {
                                'transaction_id': appointmentId,
                                'payment_status': status,
                                'support_channel': 'whatsapp'
                            });
                        }
                    });
                }
            }
        });
    </script>
</x-guest-layout>