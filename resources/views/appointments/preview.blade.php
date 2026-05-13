<x-guest-layout>
    <div class="max-w-xl mx-auto py-12 px-4 mt-6">
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
            <div class="bg-blue-600 p-6 text-center text-white">
                <h2 class="text-xl font-bold text-white/90">Resumen de tu reserva</h2>
            </div>
            
            <div class="p-8 space-y-6">
                <!-- Doctor -->
                <div class="flex justify-between border-b pb-4">
                    <span class="text-gray-500 font-medium">Doctor</span>
                    <span class="font-bold text-gray-800">{{ $appointment->doctor->user->name }}</span>
                </div>

                <!-- Lugar de la cita -->
                <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100">
                    <div class="flex items-start gap-4">
                        <div class="bg-blue-100 p-3 rounded-xl">
                            @if($appointment->service->type === 'virtual')
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            @else
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                            @endif
                        </div>

                        <div>
                            <h3 class="text-sm font-black text-gray-400 uppercase tracking-wider">Lugar de la cita</h3>
                            @if($appointment->service->type === 'virtual')
                                <p class="text-lg font-bold text-gray-800">Consulta Virtual</p>
                                <p class="text-blue-600 font-medium">Se enviará el enlace de conexión por correo</p>
                            @else
                                <p class="text-lg font-bold text-gray-800">{{ $appointment->address->name }}</p>
                                <p class="text-gray-600">
                                    {{ $appointment->address->city->name }}@if($appointment->address->address), {{ $appointment->address->address }}@endif
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Servicio -->
                <div class="flex justify-between border-b pb-4">
                    <span class="text-gray-500 font-medium">Servicio</span>
                    <span class="font-bold text-gray-800">{{ $appointment->service->name }}</span>
                </div>

                <!-- Fecha -->
                <div class="flex justify-between border-b pb-4">
                    <span class="text-gray-500 font-medium">Fecha</span>
                    <div class="text-end">
                        <!-- Fecha legible estructurada -->
                        <span class="font-bold text-gray-800 block">
                            {{ \Carbon\Carbon::parse($appointment->date)->translatedFormat('d \d\e F \d\e Y') }}
                        </span>
                        <!-- Tiempo restante dinámico en español (ej: "en 5 días", "en 2 semanas") -->
                        <span class="text-xs text-blue-600 font-bold block mt-0.5 bg-blue-50 px-2 py-0.5 rounded-md inline-block">
                            {{ \Carbon\Carbon::parse($appointment->date)->diffForHumans(null, false, false, 2) }}
                        </span>
                    </div>
                </div>

                <!-- Hora (Convertida a formato 12 horas AM/PM de forma nativa) -->
                <div class="flex justify-between border-b pb-4">
                    <span class="text-gray-500 font-medium">Hora</span>                    
                    <span class="font-bold text-gray-800">
                        {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }} a 
                        {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}
                    </span>
                </div>                

                <!-- PRECIO (Corregido: Extraído directamente del modelo Appointment) -->
                <div class="pt-4 text-center">
                    <p class="text-2xl font-black text-blue-600 mb-6">
                        ${{ number_format($appointment->price, 2) }}
                    </p>
                    
                    <div class="mt-10">
                        @if($appointment->doctor->settings?->accepts_online_payments)
                            {{-- Caso TRUE: Mostrar botón de pago --}}
                            <form action="{{ route('appointments.confirm', $appointment->id) }}" method="GET">                                
                                <button type="submit" class="w-full bg-blue-600 text-white py-4 rounded-2xl font-black shadow-lg hover:bg-blue-700 transition flex items-center justify-center gap-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    Pagar cita online
                                </button>
                            </form>
                        @else
                            {{-- Caso FALSE: Redirigir a confirmación final --}}
                            <form action="{{ route('appointments.confirm', $appointment->id) }}" method="GET">                                
                                <button type="submit" class="w-full bg-blue-600 text-white py-4 rounded-2xl font-black shadow-lg hover:bg-blue-700 transition">
                                    Confirmar y finalizar
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-guest-layout>
