<x-guest-layout>
    <div class="max-w-3xl mx-auto py-12 px-4 text-center">
        <!-- Icono de Éxito Animado -->
        <div class="mt-6 mb-8">
            <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-green-100 shadow-lg shadow-green-200">
                <svg class="h-12 w-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-4xl font-black text-gray-900">¡Cita Confirmada!</h2>
            <p class="text-gray-500 mt-2 text-lg">Tu reserva se ha realizado con éxito.</p>
        </div>

        <!-- Card de Detalles -->
        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden text-left mb-8">
            <div class="p-8">
                <div class="flex flex-col md:flex-row justify-between gap-6">
                    <!-- Info Doctor/Servicio -->
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs font-bold text-blue-600 uppercase tracking-widest">Especialista</p>
                            <p class="text-xl font-bold text-gray-800">{{ $appointment->doctor->user->name }}</p>
                            <p class="text-sm text-gray-500">{{ $appointment->service->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-blue-600 uppercase tracking-widest">Fecha y Hora</p>
                            <p class="text-gray-800 font-medium">
                                {{ \Carbon\Carbon::parse($appointment->date)->isoFormat('LL') }}
                            </p>
                            <p class="text-gray-800 font-medium">{{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}</p>
                        </div>
                    </div>

                    <!-- Info Ubicación/Modalidad -->
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs font-bold text-blue-600 uppercase tracking-widest">Modalidad</p>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $appointment->service->type === 'virtual' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ $appointment->service->type === 'physical' ? 'Presencial' : 'Virtual' }}
                            </span>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-blue-600 uppercase tracking-widest">Ubicación</p>
                            @if($appointment->service->type === 'virtual')
                                <p class="text-sm text-gray-800 font-medium">Enlace de video generado</p>
                                <p class="text-xs text-gray-500 italic">Recibirás el link por WhatsApp</p>
                            @else
                                <p class="text-sm text-gray-800 font-medium">{{ $appointment->address->name }}</p>
                                <p class="text-xs text-gray-500">{{ $appointment->address->address }}, {{ $appointment->address->city->name }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Sección de Acciones Virtuales -->
                @if($appointment->meeting_link)
                    <div class="my-3 text-blue-900">¡Recuerda conectarte 15 minutos antes del inicio de la videollamada para validar que el audio y video funcionen correctamente!</div>
                    <div class="mt-8 p-4 bg-purple-50 rounded-2xl border border-purple-100 flex items-center justify-between">
                        <p class="text-sm text-purple-800 font-medium">Tu link de telemedicina está listo:</p>
                        <a href="{{ $appointment->meeting_link }}" target="_blank" class="bg-purple-600 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-purple-700">Acceder ahora</a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="#" class="px-8 py-4 bg-gray-100 text-gray-700 font-bold rounded-2xl hover:bg-gray-200 transition">
                Ir a mis citas
            </a>
            
            <!-- Botón de WhatsApp automático -->
            @php
                $mensaje = urlencode("Hola, acabo de agendar una cita con el doctor {$appointment->doctor->user->name} para el día " . \Carbon\Carbon::parse($appointment->date)->format('d/m/Y') . " a las " . \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') . ".");
                $waUrl = "https://wa.me{$appointment->doctor->phone}?text={$mensaje}";
            @endphp
            
            <a href="{{ $waUrl }}" target="_blank" class="px-8 py-4 bg-green-500 text-white font-bold rounded-2xl hover:bg-green-600 shadow-lg shadow-green-200 transition flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                Notificar al Doctor
            </a>
        </div>
    </div>
</x-guest-layout>
