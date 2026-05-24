@php
    // Esta vista es para la versión móvil de la tabla de citas, se muestra cada cita como una tarjeta individual adaptativa.
    $ahora = now();
    $appointmentStart = \Carbon\Carbon::parse($app->date . ' ' . $app->start_time);
    
    // Usamos copy() para no alterar el objeto $appointmentStart original por referencia
    $canStart = $ahora->greaterThanOrEqualTo($appointmentStart->copy()->subMinutes(15));
    $isVirtual = $app->service->type === 'virtual';
    
    // Definimos el enlace correcto para el DOCTOR (Prioriza Zoom Start URL si existe)
    $doctorMeetingUrl = $app->zoom_start_url ?? $app->meeting_link;
@endphp

<div class="p-5 flex flex-col gap-4 border-b border-gray-100 last:border-0 active:bg-gray-50/50 transition-colors bg-white">
    
    <!-- Fila Superior: Hora y Estado -->
    <div class="flex justify-between items-start">
        <div class="flex flex-col">
            <span class="text-xl font-black text-gray-900 leading-none">
                {{ \Carbon\Carbon::parse($app->start_time)->format('g:i A') }}
            </span>
            <span class="text-[11px] font-bold text-indigo-500 mt-1.5 uppercase tracking-wider">
                {{ \Carbon\Carbon::parse($app->date)->translatedFormat('d M, Y') }} • {{ $app->duration }} MINUTOS
            </span>
        </div>
        
        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-tight {{ $app->status === 'confirmed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
            {{ $app->status === 'confirmed' ? 'Confirmado' : ucfirst($app->status) }}
        </span>
    </div>

    <!-- Información del Paciente -->
    <div class="flex items-center gap-3 bg-slate-50/50 p-3 rounded-2xl border border-slate-100/50">
        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-black text-sm uppercase flex-shrink-0">
            {{ substr($app->patient->user->name, 0, 2) }}
        </div>
        <div class="flex flex-col">
            <h4 class="text-sm font-extrabold text-gray-800 leading-tight">
                {{ $app->patient->user->name }}
            </h4>
            <span class="text-[11px] text-gray-400 font-medium mt-0.5 block">ID: {{ $app->patient->identification }}</span>
            <a href="{{ route('partner.patients.show', $app->patient->id) }}" class="text-xs text-indigo-600 font-bold mt-1 flex items-center gap-0.5 hover:text-indigo-800">
                Ver historial médico →
            </a>
        </div>
    </div>

    <!-- 🔥 EXCLUSIVO CLÍNICAS: Identificación del especialista a cargo desde el celular -->
    @if(auth()->user()->role === 'clinic')
        <div class="flex items-center gap-2 bg-indigo-50/60 p-3 rounded-2xl border border-indigo-100/50">
            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <div class="flex flex-col">
                <span class="text-[9px] text-indigo-500 font-black uppercase tracking-wider leading-none">Especialista asignado</span>
                <span class="text-xs font-bold text-indigo-900 mt-0.5">Dr/a. {{ $app->doctor->user->name ?? 'Sin asignar' }}</span>
            </div>
        </div>
    @endif

    <!-- Detalles del Servicio -->
    <div class="bg-gray-50 rounded-2xl p-3 flex items-center justify-between border border-gray-100/40">
        <div class="flex flex-col">
            <span class="text-[10px] text-gray-500 font-bold uppercase leading-none mb-1">Servicio</span>
            <span class="text-xs font-semibold text-gray-700">{{ $app->service->name }}</span>
        </div>
        
        @if($isVirtual)
            <span class="bg-purple-100 text-purple-700 border border-purple-200 text-[10px] px-2 py-1 rounded-md font-black flex items-center gap-1 uppercase tracking-wide">
                💻 Virtual @if($app->zoom_meeting_id) (ZOOM) @endif
            </span>
        @else
            <span class="bg-blue-100 text-blue-700 border border-blue-200 text-[10px] px-2 py-1 rounded-md font-black uppercase tracking-wide">🏥 Presencial</span>
        @endif
    </div>
    <!-- Acciones Rápidas y Gestión de Estado Móvil -->    
    <div class="flex flex-col gap-2 mt-2">
        <div class="flex gap-2">
            <!-- Botón Ver Notas -->
            <button onclick="openNoteModal({{ json_encode($app->notes ?? 'No hay notas para esta cita.') }})"
                    type="button"
                    class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl flex items-center justify-center gap-2 font-bold text-xs uppercase tracking-wider transition-all">
                Notas
            </button>

            <!-- Flujo de Telemedicina Online -->
            @if($isVirtual && $app->status === 'confirmed')
                @if($app->zoom_meeting_id)
                    @if($canStart)
                        <a href="{{ $doctorMeetingUrl }}" 
                           target="_blank" 
                           class="flex-1 bg-purple-600 text-white py-3 rounded-xl flex items-center justify-center gap-2 font-bold text-xs uppercase tracking-wider shadow-md transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z"/>
                            </svg>
                            Iniciar Cita
                        </a>
                    @else
                        <button disabled 
                                class="flex-1 bg-slate-100 text-slate-400 py-3 rounded-xl flex items-center justify-center gap-2 font-bold text-[10px] border border-slate-200 cursor-not-allowed">
                            🔒 Zoom Listo (15m antes)
                        </button>
                    @endif
                @else
                    <span class="flex-1 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center text-[10px] font-bold border border-purple-100/60 p-3">
                        Consulta Virtual
                    </span>
                @endif
            @endif
        </div>

        <!-- BOTONES DE CAMBIO DE ESTADO TÁCTILES -->
        @if ($app->status !== 'completed' && $app->status !== 'cancelled')
            <div class="flex gap-2 border-t border-slate-100/60 pt-2.5">
                <!-- Formulario Completar Cita -->
                <form action="{{ route('partner.appointments.complete', $app) }}" method="POST" class="flex-1">
                    @csrf
                    @method('PATCH')
                    <button type="submit" onclick="return confirm('¿Deseas marcar esta cita como completada?')" 
                            class="w-full bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200/50 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-colors flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Completar
                    </button>
                </form>

                <!-- Formulario Cancelar Cita -->
                <form action="{{ route('partner.appointments.cancel', $app) }}" method="POST" class="flex-1">
                    @csrf
                    @method('PATCH')
                    <button type="submit" onclick="return confirm('¿Estás seguro de que deseas cancelar esta cita médica?')" 
                            class="w-full bg-red-50 text-red-700 hover:bg-red-100 border border-red-200/50 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-colors flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        Cancelar
                    </button>
                </form>
            </div>
        @else
            <div class="w-full text-center bg-slate-50 text-slate-400 py-2.5 rounded-xl border border-slate-200/50 text-xs italic font-medium">
                Esta consulta médica ha finalizado
            </div>
        @endif
    </div>
</div>
