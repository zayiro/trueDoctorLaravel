<x-guest-layout>
    @php
        // Evaluamos si la cita entró en estado pendiente o confirmada
        $isPending = $appointment->status === 'pending';
    @endphp

    <div class="max-w-3xl mx-auto py-12 px-4 text-center">
        <!-- 1. ICONO, TITULO Y SUBTITULO CONDICIONALES -->
        <div class="mt-6 mb-8">
            @if($isPending)
                <!-- Icono de Espera Animado (Amarillo) -->
                <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-yellow-100 shadow-lg shadow-yellow-200 animate-pulse">
                    <svg class="h-12 w-12 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h2 class="mt-6 text-4xl font-black text-gray-900">Reserva Solicitada</h2>
                <p class="text-gray-500 mt-2 text-lg">Tu cita está en espera de aprobación por el especialista.</p>
            @else
                <!-- Icono de Éxito Original (Verde) -->
                <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-green-100 shadow-lg shadow-green-200">
                    <svg class="h-12 w-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="mt-6 text-4xl font-black text-gray-900">¡Cita Confirmada!</h2>
                <p class="text-gray-500 mt-2 text-lg">Tu reserva se ha realizado con éxito.</p>
            @endif
        </div>

        <!-- Card de Detalles -->
        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden text-left mb-8">
            
            <!-- 👇 BANNER INFORMATIVO COMPLEMENTARIO SI ESTÁ PENDIENTE -->
            @if($isPending)
                <div class="bg-yellow-50/60 border-b border-yellow-100 px-8 py-4 flex items-start gap-3">
                    <span class="text-base mt-0.5">⏳</span>
                    <p class="text-xs text-yellow-800 leading-relaxed font-medium">
                        <strong>Revisión de Agenda Activa:</strong> Este especialista valida manualmente sus horarios. Te enviaremos un correo electrónico automático tan pronto como el doctor confirme o modifique tu espacio de atención.
                    </p>
                </div>
            @endif

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
                                {{ ucfirst(\Carbon\Carbon::parse($appointment->date)->isoFormat('dddd, D [de] MMMM [de] YYYY') ) }}
                            </p>
                            <p class="text-gray-800 font-medium">{{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}</p>
                            <p class="text-xs text-blue-600 font-black block mt-1 bg-blue-50 px-2 py-0.5 rounded-md inline-block">
                                {{ \Carbon\Carbon::parse($appointment->date)->diffForHumans(null, false, false, 2) }}
                            </p>
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
                                <!-- 👇 AJUSTE VIRTUAL PENDIENTE VS CONFIRMADO -->
                                @if($isPending)
                                    <p class="text-sm text-gray-800 font-medium">Link de telemedicina en espera</p>
                                    <p class="text-xs text-gray-500 italic">Se generará una vez sea aprobada por el médico</p>
                                @else
                                    <p class="text-sm text-gray-800 font-medium">Enlace de video generado</p>
                                    <p class="text-xs text-gray-500 italic">Recibirás el link por WhatsApp</p>
                                @endif
                            @else
                                <p class="text-sm text-gray-800 font-medium">{{ $appointment->address->name }}</p>
                                <p class="text-xs text-gray-500">{{ $appointment->address->address }}, {{ $appointment->address->city->name }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Sección de Acciones Virtuales Oculta si está pendiente -->
                @if(!$isPending && $appointment->meeting_link)
                    <div class="my-3 text-blue-900">Por favor, ingresa al enlace 5 minutos antes de la hora programada para realizar las pruebas técnicas de audio y cámara.</div>
                    <div class="mt-8 p-4 bg-purple-50 rounded-2xl border border-purple-100 flex items-center justify-between">
                        <p class="text-sm text-purple-800 font-medium">Tu link de telemedicina está listo:</p>
                        <a href="{{ $appointment->meeting_link }}" target="_blank" class="bg-purple-600 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-purple-700">Acceder ahora</a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="{{ route('patient.appointments.index') }}" class="px-8 py-4 bg-gray-100 text-gray-700 font-bold rounded-2xl hover:bg-gray-200 transition">
                Ir a mis citas
            </a>
            
            <!-- Botón de WhatsApp dinámico según el estado -->
            @php
                $phoneClean = preg_replace('/[^0-9]/', '', $appointment->doctor->phone);
                $dateText = \Carbon\Carbon::parse($appointment->date)->isoFormat('dddd, D [de] MMMM [de] YYYY');
                $timeText = \Carbon\Carbon::parse($appointment->start_time)->format('g:i A');

                // Cambiamos el texto de la cabecera según el estado real
                $statusHeader = $isPending ? "SOLICITUD DE CITA EN ESPERA DE APROBACIÓN" : "NUEVA CITA MÉDICA CONFIRMADA";

                $message = "Hola Doctor(a) " . $appointment->doctor->user->name . ",\n\n"
                        . "====== " . $statusHeader . " ======\n\n"
                        . "Paciente: " . $appointment->patient->user->name . "\n"
                        . "Servicio: " . $appointment->service->name . "\n"
                        . "Fecha: " . \Illuminate\Support\Str::ucfirst($dateText) . "\n"
                        . "Hora: " . $timeText . "\n"
                        . "Modalidad: " . ($appointment->service->type === 'virtual' ? 'Virtual (Telemedicina)' : 'Presencial en ' . $appointment->address->name) . "\n\n"
                        . ($isPending ? "Por favor, ingrese a su panel de administración para APROBAR o RECHAZAR esta reservación." : "La cita ha quedado debidamente agendada en el sistema.");

                $whatsappUrl = "https://wa.me/+57{$phoneClean}?text=" . urlencode($message);                
            @endphp
            
            <a href="{{ $whatsappUrl }}" target="_blank" class="px-8 py-4 bg-green-500 text-white font-bold rounded-2xl hover:bg-green-600 shadow-lg shadow-green-200 transition flex items-center justify-center gap-2">
                <!-- Icono SVG de WhatsApp -->
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096.585 5.648 2.118 3.553 1.533 4.169 1.591 4.914 1.591.745 0 2.406-.867 2.747-1.673.34-.806.34-1.491.242-1.638-.099-.148-.348-.222-.645-.371z"/>
                    <path d="M12.004 2c-5.51 0-9.99 4.49-9.99 9.99 0 2.01.59 3.93 1.72 5.56L2 22l4.63-1.22c1.55.93 3.32 1.41 5.14 1.41 5.51 0 9.99-4.49 9.99-9.99S17.514 2 12.004 2zm0 18.28c-1.64 0-3.25-.44-4.66-1.27l-.33-.2-.28.07-2.75.72.74-2.69-.21-.34c-.92-1.47-1.4-3.17-1.4-4.93 0-4.94 4.02-8.96 8.96-8.96 4.94 0 8.96 4.02 8.96 8.96s-4.02 8.96-8.96 8.96z"/>
                </svg>
                <span>{{ $isPending ? 'Notificar solicitud por WhatsApp' : 'Notificar confirmación por WhatsApp' }}</span>
            </a>
        </div>
    </div>
</x-guest-layout>
