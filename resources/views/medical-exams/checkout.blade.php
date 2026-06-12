@php
$version = time();
@endphp
<x-guest-layout>
    <div class="max-w-5xl mx-auto py-12 px-4 mt-6">
        <div class="w-full bg-white rounded-3xl border border-slate-200/80 shadow-2xl shadow-slate-100 p-6 sm:p-8 space-y-6">
            <!-- Encabezado -->
            <div class="text-center space-y-2">
                <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">Paso Final</span>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Confirmar Pedido</h1>
                <p class="text-xs text-slate-400">Estás a un paso de interpretar tus resultados clínicos.</p>
            </div>

            <!-- Resumen de Facturación -->
            <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 space-y-3">
                <div class="flex justify-between text-sm font-semibold">
                    <span class="text-slate-400">Servicio</span>
                    <span class="text-slate-800">Lectura de Examen por IA</span>
                </div>
                <div class="flex justify-between text-sm font-semibold">
                    <span class="text-slate-400">Motivo declarado</span>
                    <span class="text-slate-700 capitalize">{{ $analysis->reason_type }}</span>
                </div>
                <div class="border-t border-slate-200/60 my-2 pt-2 flex justify-between items-baseline">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total a Pagar</span>
                    <span class="text-2xl font-black text-slate-900 tabular-nums">${{ number_format($analysis->price, 2) }}</span>
                </div>
            </div>

            <!-- Formulario Seguro Interno -->
            <form action="{{ route('exams.id_pago', $analysis->id) }}" method="POST" class="space-y-4">
                @csrf
                
                <div class="container mx-auto px-4 py-8">
                    <!-- Contenedor Grid: Una columna en móviles, dos columnas en pantallas medianas o superiores -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto">
                        
                        <!-- Tarjeta Bancolombia -->
                        <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden flex flex-col items-center p-6 text-center">
                            <!-- Imagen superior -->
                            <div class="w-full flex items-center justify-center mb-4">
                                <img src="{{ asset('images/bancolombia-qr.jpg?v=$version') }}" alt="QR Bancolombia" class="h-full object-contain">
                            </div>
                            <!-- Nombre -->
                            <h3 class="text-xl font-bold text-gray-800 mb-2">Bancolombia</h3>
                            <!-- Descripción -->
                            <p class="text-sm text-gray-600 leading-relaxed mt-auto">
                                Realiza transferencias directas a nuestra cuenta de ahorros. El procesamiento es inmediato y sin comisiones adicionales.
                            </p>
                            <div class="text-sm font-semibold text-gray-600 leading-relaxed mt-auto">Cuenta ahorros 825-908153-11</div>
                        </div>

                        <!-- Tarjeta Nequi -->
                        <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden flex flex-col items-center p-6 text-center">
                            <!-- Imagen superior -->
                            <div class="w-full flex items-center justify-center mb-4">
                                <img src="{{ asset('images/nequi-qr.jpg?v=$version') }}" alt="QR Nequi" class="h-full object-contain">
                            </div>
                            <!-- Nombre -->
                            <h3 class="text-xl font-bold text-gray-800 mb-2">Nequi</h3>
                            <!-- Descripción -->
                            <p class="text-sm text-gray-600 leading-relaxed mt-auto">
                                Paga de forma rápida escaneando nuestro código QR o enviando a nuestro número celular desde tu aplicación móvil.
                            </p>
                            <div class="text-sm font-semibold text-gray-600 leading-relaxed mt-auto">3026433874</div>
                        </div>

                    </div>

                    <div class="mt-4 text-base font-bold text-gray-800">Reporta tu pago a nuestra línea de WhatsApp (+57) 302 643 3874</div>
                    <div class="text-sm font-semibold text-gray-500">Para habilitar la descarga del resultado.</div>
                </div>

                @if ($analysis->payment_status === 'paid')
                <!-- Botón de Pago -->
                <button type="submit" class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold text-base rounded-xl shadow-lg shadow-emerald-100/50 hover:shadow-emerald-200 active:scale-[0.98] transition-all duration-200 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                    </svg>
                    Analizar mi examen ahora
                </button>
                @endif
            </form>

        </div>
    </div>
</x-guest-layout>