<x-guest-layout>
    @php
        $isPending = $appointment->status === 'pending';
        $appointmentDate = \Carbon\Carbon::parse($appointment->date)->format('Y-m-d');
    @endphp

    <div class="max-w-4xl mx-auto py-12 px-4 text-center">
        
        <!-- 1. ICONO, TITULO Y SUBTITULO CONDICIONALES POR ESTADO -->
        <div class="mt-6 mb-8">
            @if($isPending)
                <!-- Icono de Espera Animado (Amber) -->
                <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-[2rem] bg-amber-50 shadow-lg shadow-amber-100/50 border border-amber-200/40 animate-pulse">
                    <svg class="h-10 w-10 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h2 class="mt-6 text-3xl font-black text-slate-800 tracking-tight">Reserva en Revisión</h2>
                <p class="text-slate-500 mt-2 text-base">Tu cita está en espera de validación por el centro médico o especialista.</p>
            @else
                <!-- Icono de Éxito Seguro (Emerald) -->
                <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-[2rem] bg-emerald-50 shadow-lg shadow-emerald-100/50 border border-emerald-200/40">
                    <svg class="h-10 w-10 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
                <h2 class="mt-6 text-3xl font-black text-slate-800 tracking-tight">¡Cita Agendada Exitosamente!</h2>
                <p class="text-slate-500 mt-2 text-base">Tu reserva médica se ha consolidado correctamente en la agenda.</p>
            @endif
        </div>

        <!-- Tarjeta de Detalles Corporativos y Co-propiedad -->
        <div class="bg-white rounded-[2.5rem] shadow-xl border border-slate-100 overflow-hidden text-left mb-8 relative">
            <div class="h-1.5 w-full {{ $isPending ? 'bg-amber-400' : 'bg-emerald-500' }} absolute top-0 left-0"></div>
            
            @if($isPending)
                <div class="bg-amber-50/60 border-b border-amber-100/60 px-8 py-4 flex items-start gap-3 pt-6">
                    <span class="text-base mt-0.5">⏳</span>
                    <p class="text-xs text-amber-800 leading-relaxed font-medium">
                        <strong>Revisión de Agenda Activa:</strong> El especialista valida manualmente sus franjas horarias. Te notificaremos automáticamente por correo electrónico tan pronto como se confirme tu bloque de atención.
                    </p>
                </div>
            @endif
            <div class="p-8 {{ $isPending ? 'pt-6' : 'pt-10' }}">
                <div class="flex flex-col md:flex-row justify-between gap-6">
                    
                    <!-- Información del Médico, Servicio y Centro Médico -->
                    <div class="space-y-4 flex-1">
                        <div>
                            <span class="text-[11px] font-black text-indigo-600 uppercase tracking-widest block mb-1">Prestador de Salud</span>
                            
                            {{-- 🔒 CONTROL MULTI-TENANT: Evitamos el fallo de 'property user on null' validando si existe el doctor --}}
                            <p class="text-xl font-black text-slate-800">
                                @if($appointment->doctor)
                                    {{ $appointment->doctor->gender === 'male' ? 'Dr. ' . ucfirst($appointment->doctor->user->name) : 'Dra. ' . ucfirst($appointment->doctor->user->name) }}
                                @else
                                    {{ $appointment->clinic->name ?? 'Centro Médico Institucional' }}
                                @endif
                            </p>
                            
                            <p class="text-xs font-bold text-slate-500 bg-slate-50 border px-2.5 py-1 rounded-lg inline-block mt-1.5">{{ $appointment->service?->name ?? 'Consulta Médica General' }}</p>
                            
                            <!-- Co-propiedad: Muestra la sede de la clínica si la cita es con especialista dentro de una institución -->
                            @if($appointment->clinic_id && $appointment->clinic && $appointment->doctor_id)
                                <div class="mt-2 block">
                                    <span class="inline-flex items-center gap-1 text-[11px] font-black text-indigo-700 uppercase bg-indigo-50 px-2.5 py-0.5 rounded-md border border-indigo-100/40 shadow-sm">
                                        🏢 Sede: {{ $appointment->clinic->name }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        <div>
                            <span class="text-[11px] font-black text-indigo-600 uppercase tracking-widest block mb-1">Fecha y Hora</span>
                            <p class="text-sm font-bold text-slate-700 capitalize">                                                                
                                {{ ucfirst(\Carbon\Carbon::parse($appointmentDate)->translatedFormat('l, d \d\e F \d\e Y')) }}
                            </p>
                            <p class="text-base font-black text-slate-800 mt-0.5">
                                {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}
                            </p>
                            <span class="text-[10px] text-indigo-600 font-bold block mt-1.5 bg-indigo-50 px-2.5 py-0.5 rounded-md inline-block border border-indigo-100/50">
                                {{ \Carbon\Carbon::parse($appointmentDate)->diffForHumans(null, false, false, 2) }}
                            </span>
                        </div>
                    </div>

                    <!-- Modalidad, Sala de Telemedicina o Consultorio Físico -->
                    <div class="space-y-4 w-full md:w-auto md:text-right flex flex-col md:items-end">
                        <div>
                            <span class="text-[11px] font-black text-indigo-600 uppercase tracking-widest block mb-1">Modalidad</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-bold {{ $appointment->service->type === 'virtual' ? 'bg-purple-100 text-purple-700 border border-purple-200/50' : 'bg-blue-100 text-blue-700 border border-blue-200/50' }}">
                                {{ $appointment->service->type === 'physical' ? '🏥 Presencial' : '💻 Telemedicina' }}
                            </span>
                        </div>
                        
                        <div>
                            <span class="text-[11px] font-black text-indigo-600 uppercase tracking-widest block mb-1">Ubicación de Asistencia</span>
                            @if($appointment->service->type === 'virtual')
                                @if($isPending)
                                    <p class="text-sm text-slate-700 font-bold">Enlace digital en espera</p>
                                    <p class="text-[11px] text-slate-400 font-medium italic mt-0.5">Se activará automáticamente al ser aprobada.</p>
                                @else
                                    <p class="text-sm text-slate-700 font-bold">Sala virtual de consulta lista</p>
                                    <p class="text-[11px] text-purple-600 font-semibold mt-0.5">Acceso habilitado abajo.</p>
                                @endif
                            @else
                                <p class="text-sm text-slate-800 font-black leading-tight">{{ $appointment->address->name }}</p>
                                <p class="text-[11px] text-slate-500 font-medium mt-0.5 leading-normal max-w-xs md:text-right">
                                    {{ $appointment->address->address }}@if($appointment->address->city), {{ $appointment->address->city->name }}@endif
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
                <!-- ALERTA DISPARADORA DE TELEMEDICINA ACTIVA -->
                @if(!$isPending && $appointment->meeting_link)
                    <div class="mt-6 p-5 bg-purple-50 rounded-2xl border border-purple-100/70 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="space-y-0.5">
                            <h4 class="font-extrabold text-purple-950 text-sm">Tu link de telemedicina está listo:</h4>
                            <p class="text-xs text-purple-800 leading-normal">Ingresa 5 minutos antes de la hora programada para verificar audio y cámara.</p>
                        </div>
                        <a href="{{ $appointment->meeting_link }}" target="_blank" class="w-full sm:w-auto bg-purple-600 hover:bg-purple-700 text-white font-black px-5 py-3 rounded-xl text-xs uppercase tracking-wider transition-all text-center shadow-md shadow-purple-100">
                            Acceder a la Sala
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- BOTONES FINALES DE SALIDA Y NOTIFICACIÓN TRANSACCIONAL -->
        <div class="flex flex-col sm:flex-row justify-center items-center gap-3 border-t border-slate-200/40 pt-6">
            <a href="{{ route('patient.appointments.index') }}" class="w-full sm:w-auto px-8 py-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-2xl text-xs uppercase tracking-wider transition text-center">
                Ir a mis citas médicas
            </a>
            
            @php
                // 🔒 CORREGIDO: Resolvemos el teléfono y nombre de forma dinámica por tipo de Proveedor
                $providerPhone = $appointment->doctor ? $appointment->doctor->phone : ($appointment->clinic->phone ?? '');
                $providerName = $appointment->doctor ? "Doctor(a) " . $appointment->doctor->user->name : ($appointment->clinic->name ?? 'Socio Médico');
                
                $cleanPhone = preg_replace('/[^0-9]/', '', $providerPhone);
                $formattedDate = \Carbon\Carbon::parse($appointmentDate)->translatedFormat('dddd, d \d\e F \d\e Y');
                $formattedTime = \Carbon\Carbon::parse($appointment->start_time)->format('g:i A');
                $whatsappHeader = $isPending ? "SOLICITUD DE CITA EN ESPERA DE APROBACIÓN" : "NUEVA CITA MÉDICA CONFIRMADA";

                $whatsappMessage = "Hola " . $providerName . ",\n\n"
                        . "====== " . $whatsappHeader . " ======\n\n"
                        . "Paciente: " . $appointment->patient->user->name . "\n"
                        . "Servicio: " . $appointment->service->name . "\n"
                        . "Fecha: " . ucfirst($formattedDate) . "\n"
                        . "Hora: " . $formattedTime . "\n"
                        . "Modalidad: " . ($appointment->service->type === 'virtual' ? 'Virtual (Telemedicina)' : 'Presencial en ' . $appointment->address->name) . "\n\n"
                        . ($isPending ? "Por favor, ingrese a su panel de administración para APROBAR o RECHAZAR esta reservación." : "La cita ha quedado debidamente agendada en el sistema.");

                $whatsappEndpoint = "https://wa.me{$cleanPhone}?text=" . urlencode($whatsappMessage);                
            @endphp
            
            <a href="{{ $whatsappEndpoint }}" target="_blank" class="w-full sm:w-auto px-8 py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-2xl text-xs uppercase tracking-wider shadow-lg shadow-emerald-100 transition flex items-center justify-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096.585 5.648 2.118 3.553 1.533 4.169 1.591 4.914 1.591.745 0 2.406-.867 2.747-1.673.34-.806.34-1.491.242-1.638-.099-.148-.348-.222-.645-.371z"/>
                    <path d="M12.004 2c-5.51 0-9.99 4.49-9.99 9.99 0 2.01.59 3.93 1.72 5.56L2 22l4.63-1.22c1.55.93 3.32 1.41 5.14 1.41 5.51 0 9.99-4.49 9.99-9.99S17.514 2 12.004 2zm0 18.28c-1.64 0-3.25-.44-4.66-1.27l-.33-.2-.28.07-2.75.72.74-2.69-.21-.34c-.92-1.47-1.4-3.17-1.4-4.93 0-4.94 4.02-8.96 8.96-8.96 4.94 0 8.96 4.02 8.96 8.96s-4.02 8.96-8.96 8.96z"/>
                </svg>
                <span>Enviar Notificación WhatsApp</span>
            </a>
        </div>
    </div>
</x-guest-layout>
