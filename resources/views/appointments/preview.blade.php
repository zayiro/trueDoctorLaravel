@php
$appointmentDate = \Carbon\Carbon::parse($appointment->date)->format('Y-m-d');
@endphp
<x-guest-layout>
    <div class="max-w-4xl mx-auto py-12 px-4 mt-6">
        @if(session('error'))
            <div class="flex items-center p-4 mb-4 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm" role="alert">
                <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://w3.org" fill="currentColor" viewBox="0 0 20 20">
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
                <span class="text-[10px] font-black text-indigo-400 Regal uppercase tracking-[0.2em] mb-1 block">Paso de Verificación</span>
                <h2 class="text-2xl font-black text-white tracking-tight">Resumen de tu Orden Médica</h2>                
                <p class="text-sm text-slate-400 mt-1">Valida los datos de tu especialista antes de proceder a la confirmación de la agenda.</p>
            </div>
            
            <div class="p-8 space-y-5">
                <!-- Profesional de la Salud -->
                <div class="flex justify-between items-center border-b border-slate-100 pb-4">
                    <span class="text-slate-400 text-sm font-black uppercase tracking-wider">Especialista Asignado</span>
                    <span class="font-extrabold text-sm text-slate-800">{{ $appointment->doctor->gender === 'male' ? 'Dr. ' . ucfirst($appointment->doctor->user->name) : 'Dra. ' . ucfirst($appointment->doctor->user->name) }}</span>
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
                                <p class="text-purple-700 font-semibold text-sm bg-purple-50 px-2 py-0.5 rounded-md inline-block mt-1 border border-purple-100/50">El canal digital se despachará en tu perfil y correo electrónico.</p>
                            @else
                                <p class="text-base font-black text-slate-800">{{ $appointment->address->name }}</p>
                                <p class="text-sm text-slate-500 font-medium">
                                    {{ $appointment->address->city->name ?? '' }}@if($appointment->address->address), {{ $appointment->address->address }}@endif
                                </p>
                                
                                <!-- Muestra la clínica si el consultorio le pertenece a una institución -->
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
                    <span class="text-slate-400 text-xs font-black uppercase tracking-wider">Servicio Solicitado</span>
                    <span class="font-bold text-sm text-slate-700">{{ $appointment->service->name }}</span>
                </div>

                <!-- Fecha de la Agenda -->
                <div class="flex justify-between items-center border-b border-slate-100 pb-4">
                    <span class="text-slate-400 text-xs font-black uppercase tracking-wider">Fecha Programada</span>
                    <div class="text-end">
                        <span class="font-bold text-sm text-slate-800 block">
                            {{ ucfirst(\Carbon\Carbon::parse($appointmentDate)->translatedFormat('l, d \d\e F \d\e Y')) }}
                        </span>
                        <!-- Tiempo restante dinámico adaptativo -->
                        <span class="text-[10px] text-indigo-600 font-bold block mt-1 bg-indigo-50 px-2 py-0.5 rounded-md inline-block border border-indigo-100/50">
                            {{ \Carbon\Carbon::parse($appointmentDate, 'America/Bogota')->diffForHumans(\Carbon\Carbon::now('America/Bogota'), false, false, 2) }}
                        </span>
                    </div>
                </div>

                <!-- Hora de la Agenda -->
                <div class="flex justify-between items-center border-b border-slate-100 pb-4">
                    <span class="text-slate-400 text-xs font-black uppercase tracking-wider">Bloque Horario</span>                    
                    <span class="font-bold text-sm text-slate-800 bg-white border px-3 py-1 rounded-xl shadow-sm">
                        {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }} a 
                        {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}
                    </span>
                </div>                

                <!-- PRECIO FORMATEADO Y CONDICIONAL DE PASARELAS -->
                <div class="pt-4 text-center">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-1">Valor Total de la Consulta</span>
                    <p class="text-3xl font-black text-indigo-600 mb-6 tracking-tight">
                        ${{ number_format($appointment->price, 0, ',', '.') }}
                    </p>
                    
                    @php
                        // 🔥 REFACTORIZACIÓN CORE: Hereda las pasarelas según el origen corporativo de la sede
                        if ($appointment->clinic_id && $appointment->clinic) {
                            $acceptsOnlinePayments = (bool) ($appointment->clinic->settings->accepts_online_payments ?? false);
                        } else {
                            $acceptsOnlinePayments = (bool) ($appointment->doctor->settings->accepts_online_payments ?? false);
                        }
                    @endphp

                    <div class="mt-8 border-t border-slate-50 pt-6">
                        @if($acceptsOnlinePayments)
                            {{-- Caso TRUE: Requiere transaccionalidad virtual inmediata --}}
                            <form action="{{ route('appointments.confirm', $appointment->id) }}" method="GET">                                
                                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-4 rounded-2xl font-black shadow-lg shadow-emerald-100 transition-all flex items-center justify-center gap-2 text-sm uppercase tracking-wider">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                    Proceder al Pago Online
                                </button>
                            </form>
                        @else
                            {{-- Caso FALSE: Agendamiento directo o pago en el consultorio físico --}}
                            <form action="{{ route('appointments.success', $appointment) }}" method="GET">                                
                                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-4 rounded-2xl font-black shadow-lg shadow-indigo-100 transition-all text-sm uppercase tracking-wider">
                                    Confirmar y Finalizar Reserva
                                </button>
                            </form>

                            <!-- Enlace de escape por si los datos no son correctos -->
                            <div class="mt-6 text-center">
                                <form action="{{ route('appointments.cancel-flow') }}" method="POST" onsubmit="return confirm('¿Deseas cancelar este proceso y volver a buscar?');">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold text-slate-400 hover:text-red-500 underline transition-colors duration-150">
                                        Regresar y cambiar datos
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-guest-layout>
