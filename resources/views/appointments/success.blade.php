@php
    $isPending = $appointment->status === 'pending';
    $appointmentDate = \Carbon\Carbon::parse($appointment->date)->format('Y-m-d');
    // Combinamos la fecha y la hora de inicio de la cita en un solo string
    $appointmentFullDateTime = \Carbon\Carbon::parse($appointmentDate . ' ' . $appointment->start_time, 'America/Bogota');
    $now = \Carbon\Carbon::now('America/Bogota');
@endphp

<x-guest-layout>    
    <div class="max-w-4xl mx-auto py-12 px-4 text-center">
        
        <!-- 1. ICONO, TITULO Y SUBTITULO CONDICIONALES POR ESTADO -->
        <div class="mt-6 mb-8">
            @if($isPending)
                <!-- Icono de Espera Animado (Amber) -->
                <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-[2rem] bg-amber-50 shadow-lg shadow-amber-100/50 border border-amber-200/40 animate-pulse">
                    {{-- Heroicon: Clock --}}
                    <svg class="h-10 w-10 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h2 class="mt-6 text-3xl font-black text-slate-800 tracking-tight">Reserva en Revisión</h2>
                <p class="text-slate-500 mt-2 text-base font-medium">Tu cita está en espera de validación por el centro médico o especialista.</p>
            @else
                <!-- Icono de Éxito Seguro (Emerald) -->
                <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-[2rem] bg-emerald-50 shadow-lg shadow-emerald-100/50 border border-emerald-200/40">
                    {{-- Heroicon: CheckCircle --}}
                    <svg class="h-10 w-10 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
                <h2 class="mt-6 text-3xl font-black text-slate-800 tracking-tight">¡Cita Agendada Exitosamente!</h2>
                <p class="text-slate-500 mt-2 text-base font-medium">¡Todo listo! Tu cita médica quedó agendada correctamente en nuestro sistema.</p>
            @endif
        </div>

        <!-- Tarjeta de Detalles Corporativos y Co-propiedad (Multi-tenant) -->
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
                        <div class="font-mono text-base font-black text-slate-800 tracking-medium uppercase selection:bg-indigo-100">
                            <span class="text-[11px] font-black text-indigo-600 uppercase tracking-widest block mb-1">Referencia de Cita</span>
                            {{ $appointment->reference ?? 'REF-PENDIENTE' }}
                        </div>
                        
                        <div>
                            <span class="text-[11px] font-black text-indigo-600 uppercase tracking-widest block mb-1">Prestador de Salud</span>
                            
                            {{-- 🔒 CONTROL MULTI-TENANT: Validamos la existencia relacional para prevenir fallos en cascada --}}
                            <p class="text-xl font-black text-slate-800 tracking-tight">
                                @if($appointment->doctor)
                                    {{ $appointment->doctor->gender === 'male' ? 'Dr. ' : 'Dra. ' }}{{ ucfirst($appointment->doctor->user->name) }}
                                @else
                                    {{ $appointment->clinic->name ?? 'Centro Médico Institucional' }}
                                @endif
                            </p>
                            
                            <p class="text-xs font-bold text-slate-500 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-lg inline-block mt-1.5 uppercase tracking-wide">
                                {{ $appointment->service?->name ?? 'Consulta Médica General' }}
                            </p>
                            
                            <!-- Co-propiedad: Sede institucional vinculada si aplica -->
                            @if($appointment->clinic_id && $appointment->clinic && $appointment->doctor_id)
                                <div class="mt-2 block">
                                    <span class="inline-flex items-center gap-1 text-[11px] font-black text-indigo-700 uppercase bg-indigo-50 px-2.5 py-0.5 rounded-md border border-indigo-100/40 shadow-2xs">
                                        🏢 Sede Sincronizada: {{ $appointment->clinic->name }}
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
                                @if($now->lessThan($appointmentFullDateTime))
                                    {{-- Muestra el tiempo restante exacto en formato humano --}}
                                    {{ $appointmentFullDateTime->diffForHumans($now, [
                                        'syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW,
                                        'parts' => 2
                                    ]) }}
                                @else
                                    <span class="text-slate-400 font-medium">Bloque horario en desarrollo</span>
                                @endif
                            </span>
                        </div>
                    </div>
                    <!-- Modalidad, Sala de Telemedicina o Consultorio Físico (Multi-tenant) -->
                    <div class="space-y-4 w-full md:w-auto md:text-right flex flex-col md:items-end">
                        <div>
                            <span class="text-[11px] font-black text-indigo-600 uppercase tracking-widest block mb-1">Modalidad de Consulta</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-bold {{ $appointment->service->type === 'virtual' ? 'bg-purple-100 text-purple-700 border border-purple-200/50' : 'bg-blue-100 text-blue-700 border border-blue-200/50' }}">
                                {{ $appointment->service->type === 'physical' ? '🏥 Presencial' : '💻 Telemedicina' }}
                            </span>
                        </div>
                        
                        <div>
                            <span class="text-[11px] font-black text-indigo-600 uppercase tracking-widest block mb-1">Ubicación de Asistencia</span>
                            @if($appointment->service->type === 'virtual')
                                @if($isPending)
                                    <p class="text-sm text-slate-700 font-bold">Enlace digital en espera</p>
                                    <p class="text-[11px] text-slate-400 font-medium italic mt-0.5">Se activará automáticamente al ser aprobada por el centro médico.</p>
                                @else
                                    <p class="text-sm text-slate-700 font-bold">Sala virtual de consulta lista</p>
                                    <p class="text-[11px] text-purple-600 font-semibold mt-0.5">Acceso directo habilitado abajo.</p>
                                @endif
                            @else
                                <p class="text-sm text-slate-800 font-black leading-tight">{{ $appointment->address->name }}</p>
                                <p class="text-[11px] text-slate-500 font-medium mt-0.5 leading-normal max-w-xs md:text-right">
                                    {{ $appointment->address->address }}@if($appointment->address->city), {{ $appointment->address->city->name }}@endif
                                </p>
                            @endif
                        </div>
                    </div>
                </div> <!-- Cierre de flex-col md:flex-row -->

                <!-- ALERTA DISPARADORA DE TELEMEDICINA ACTIVA (CON ENLACE INTEGRADO) -->
                @if(!$isPending && $appointment->meeting_link)
                    <div class="mt-6 p-5 bg-purple-50 rounded-2xl border border-purple-100/70 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 animate-fade-in">
                        <div class="space-y-0.5">
                            <h4 class="font-extrabold text-purple-950 text-sm">Tu link de telemedicina está listo:</h4>
                            <p class="text-xs text-purple-800 leading-normal">Ingresa 5 minutos antes de la hora programada para verificar audio y cámara.</p>
                        </div>
                        <a href="{{ $appointment->meeting_link }}" target="_blank" class="w-full sm:w-auto bg-purple-600 hover:bg-purple-700 text-white font-black px-5 py-3 rounded-xl text-xs uppercase tracking-wider transition-all text-center shadow-md shadow-purple-100 focus:outline-none">
                            Acceder a la Sala
                        </a>
                    </div>
                @endif
            </div> <!-- Cierre del div .p-8 interno de la tarjeta -->
        </div> <!-- Cierre de la tarjeta maestro (.bg-white.rounded-[2.5rem]) -->

        <!-- REMINDER INTELIGENTE DE ANTECEDENTES CLÍNICOS (MÁQUINA DE ESTADOS ALPINE) -->
        <div x-data="{ showReminder: localStorage.getItem('hide_history_reminder') !== 'true' }"
            x-show="showReminder"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            class="mt-6 rounded-[2rem] border border-blue-100 bg-white p-6 shadow-md relative text-left"
            style="display: none;">
            
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm shadow-blue-100">
                    {{-- Heroicon: DocumentText --}}
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>

                <div class="flex-1 space-y-1.5 pr-6">
                    <h4 class="text-base font-black text-slate-800 tracking-tight">
                        ¡Ayúdanos a preparar tu consulta médica!
                    </h4>
                    <p class="text-xs leading-relaxed text-slate-500 font-medium">
                        Para que el profesional de la salud pueda estudiar tu caso con anticipación y optimizar el tiempo de la cita, por favor asegúrate de actualizar tus <span class="font-bold text-slate-700">antecedentes, alergias y datos de historial clínico</span> en tu perfil de paciente.
                    </p>
                    
                    <div class="pt-2">
                        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1 text-xs font-black uppercase tracking-wider text-blue-600 hover:text-blue-700 group transition-colors focus:outline-none">
                            <span>Completar mi historial clínico</span>
                            <svg class="h-3.5 w-3.5 transform transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                    </div>
                </div>
                <!-- Botón de cierre superior derecho (Máquina de estados de Alpine.js) -->
                <button @click="showReminder = false; localStorage.setItem('hide_history_reminder', 'true')" 
                        type="button" 
                        class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 transition-colors focus:outline-none"
                        aria-label="Cerrar recordatorio">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div> <!-- Cierre de flex items-start -->
        </div> <!-- Cierre de la alerta del reminder de historial clínico -->


        <!-- BOTONES FINALES DE SALIDA Y NOTIFICACIÓN TRANSACCIONAL POR WHATSAPP -->
        <div class="flex flex-col sm:flex-row justify-center items-center gap-3 border-t border-slate-200/40 pt-6">
            <!-- Botón de retorno al listado de citas del panel del paciente -->
            <a href="{{ route('patient.appointments.index') }}" class="w-full sm:w-auto px-8 py-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-2xl text-xs uppercase tracking-wider transition text-center focus:outline-none">
                Ir a mis citas médicas
            </a>
            
            @php
                // 🔒 BLINDAJE MULTI-TENANT: Resolvemos los teléfonos de forma dinámica para evitar colisiones N+1
                $providerPhone = $appointment->doctor ? $appointment->doctor->phone : ($appointment->clinic->phone ?? '');
                $providerName = $appointment->doctor ? "Doctor(a) " . $appointment->doctor->user->name : ($appointment->clinic->name ?? 'Socio Médico');
                
                // Sanitizar la cadena de la base de datos dejando únicamente números reales
                $cleanPhone = preg_replace('/[^0-9]/', '', $providerPhone);
                
                $formattedDate = \Carbon\Carbon::parse($appointmentDate)->locale('es')->translatedFormat('l, d \d\e F \d\e Y');
                $formattedTime = \Carbon\Carbon::parse($appointment->start_time)->format('g:i A');
                
                // Encabezados condicionados por el estado de revisión de la base de datos
                $whatsappHeader = $isPending ? "SOLICITUD DE CITA EN ESPERA DE APROBACIÓN" : "NUEVA CITA MÉDICA CONFIRMADA";

                // Construcción del mapa relacional del texto urlencode
                $whatsappMessage = "Hola " . $providerName . ",\n\n"
                        . "====== " . $whatsappHeader . " ======\n\n"
                        . "Referencia de Control: " . $appointment->reference . "\n"
                        . "Paciente: " . $appointment->patient->user->name . "\n"
                        . "Servicio / Especialidad: " . $appointment->service->name . "\n"
                        . "Fecha: " . ucfirst($formattedDate) . "\n"
                        . "Hora Pactada: " . $formattedTime . "\n"
                        . "Modalidad: " . ($appointment->service->type === 'virtual' ? 'Virtual (Telemedicina)' : 'Presencial en ' . $appointment->address->name) . "\n\n"
                        . ($isPending ? "Por favor, ingrese a su panel de administración para APROBAR o RECHAZAR esta reservación lo antes posible." : "La cita ha quedado debidamente asentada e inscrita en el sistema digital.");

                // Enlace final hacia la API nativa de WhatsApp
                $whatsappEndpoint = "https://wa.me/{$cleanPhone}?text=" . urlencode($whatsappMessage);                
            @endphp
            
            <!-- Botón premium de mensajería asíncrona -->
            <a href="{{ $whatsappEndpoint }}" target="_blank" class="w-full sm:w-auto px-8 py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-2xl text-xs uppercase tracking-wider shadow-lg shadow-emerald-100/50 transition flex items-center justify-center gap-2 focus:outline-none">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096.585 5.648 2.118 3.553 1.533 4.169 1.591 4.914 1.591.745 0 2.406-.867 2.747-1.673.34-.806.34-1.491.242-1.638-.099-.148-.348-.222-.645-.371z"/>
                    <path d="M12.004 2c-5.51 0-9.99 4.49-9.99 9.99 0 2.01.59 3.93 1.72 5.56L2 22l4.63-1.22c1.55.93 3.32 1.41 5.14 1.41 5.51 0 9.99-4.49 9.99-9.99S17.514 2 12.004 2zm0 18.28c-1.64 0-3.25-.44-4.66-1.27l-.33-.2-.28.07-2.75.72.74-2.69-.21-.34c-.92-1.47-1.4-3.17-1.4-4.93 0-4.94 4.02-8.96 8.96-8.96 4.94 0 8.96 4.02 8.96 8.96s-4.02 8.96-8.96 8.96z"/>
                </svg>
                <span>Notificar por WhatsApp</span>
            </a>
        </div>
    </div>
</x-guest-layout>
