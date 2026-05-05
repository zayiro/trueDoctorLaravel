<x-guest-layout>
    <div class="max-w-xl mx-auto py-12 px-4 mt-6">
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
            <div class="bg-blue-600 p-6 text-center text-white">
                <h2 class="text-xl font-bold text-white/90">Resumen de tu reserva</h2>
            </div>
            
            <div class="p-8 space-y-6">
                <div class="flex justify-between border-b pb-4">
                    <span class="text-gray-500 font-medium">Doctor</span>
                    <span class="font-bold text-gray-800">{{ $appointment->doctor->user->name }}</span>
                </div>
                <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100">
                    <div class="flex items-start gap-4">
                        {{-- Icono de Mapa --}}
                        <div class="bg-blue-100 p-3 rounded-xl">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>

                        <div>
                            <h3 class="text-sm font-black text-gray-400 uppercase tracking-wider">Lugar de la cita</h3>
                            
                            @if($appointment->service->type === 'virtual')
                                <p class="text-lg font-bold text-gray-800">Consulta Virtual</p>
                                <p class="text-blue-600 font-medium">Se enviará el enlace de conexión por correo</p>
                            @else
                                <p class="text-lg font-bold text-gray-800">
                                    {{ $appointment->address->name }}
                                </p>
                                <p class="text-gray-600">
                                    {{ $appointment->address->city->name }},
                                    @if($appointment->address->address)
                                    {{ $appointment->address->address }}
                                    @endif
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex justify-between border-b pb-4">
                    <span class="text-gray-500 font-medium">Servicio</span>
                    <span class="font-bold text-gray-800">{{ $appointment->service->name }}</span>
                </div>
                <div class="flex justify-between border-b pb-4">
                    <span class="text-gray-500 font-medium">Fecha</span>
                    <span class="font-bold text-gray-800">{{ \Carbon\Carbon::parse($appointment->date)->translatedFormat('d \d\e F \d\e Y')  }} {{ \Carbon\Carbon::parse('2026-05-11')->diffForHumans() }}
</span>
                </div>
                <div class="flex justify-between border-b pb-4">
                    <span class="text-gray-500 font-medium">Hora</span>                    
                    <span class="font-bold text-gray-800">{{ $appointment->start_time }} a {{ $appointment->end_time }}</span>
                </div>                
                <div class="pt-4 text-center">
                    <p class="text-2xl font-black text-blue-600 mb-6">${{ number_format($appointment->service->price, 0) }}</p>
                    
                    <div class="mt-10">
                        @if($appointment->doctor->settings->accepts_online_payments)
                            {{-- Caso TRUE: Mostrar botón de pago --}}
                            <form action="#" method="POST">
                                @csrf
                                <button type="submit" class="w-full bg-blue-600 text-white py-4 rounded-2xl font-black shadow-lg hover:bg-blue-700 transition flex items-center justify-center gap-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                    Pagar cita online
                                </button>
                            </form>
                        @else
                            {{-- Caso FALSE: Redirigir a confirmación final --}}
                            <form action="{{ route('appointments.final-confirm', $appointment->id) }}" method="POST">
                                @csrf
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
