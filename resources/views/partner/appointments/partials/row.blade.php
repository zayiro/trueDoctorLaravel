@php
    // Esta vista es para la versión desktop de la tabla de citas, se muestra cada cita como una tarjeta individual con detalles y acciones rápidas.
    $ahora = now();
    $appointmentStart = \Carbon\Carbon::parse($app->date . ' ' . $app->start_time);
    
    // Usamos copy() para restar los 15 minutos sin alterar el objeto de hora original
    $canStart = $ahora->greaterThanOrEqualTo($appointmentStart->copy()->subMinutes(15));
    $isVirtual = $app->service->type === 'virtual';
    
    // Definimos el enlace correcto para el DOCTOR (Prioriza zoom_start_url)
    $doctorMeetingUrl = $app->zoom_start_url ?? $app->meeting_link;
@endphp

<tr class="group transition-all duration-1000 {{ session('success') && str_contains(session('success'), $app->patient->user->name) ? 'bg-indigo-50 ring-2 ring-inset ring-indigo-200' : 'hover:bg-indigo-50/50' }}">
    <!-- Columna: Hora -->
    <td class="px-6 py-5">
        <div class="flex flex-col">
            <span class="text-base font-black text-gray-900 leading-tight">
                {{ \Carbon\Carbon::parse($app->start_time)->format('g:i A') }}
            </span>
            <p class="text-[10px] text-indigo-600 font-bold uppercase mt-0.5">
                {{ \Carbon\Carbon::parse($app->date)->translatedFormat('D d M') }}
            </p>
            <span class="inline-flex items-center text-[11px] font-medium text-gray-400 mt-1">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ $app->duration }} min
            </span>
        </div>
    </td>

    <!-- Columna: Paciente -->
    <td class="px-6 py-5">
        <div class="flex flex-col relative">
            <span class="text-sm font-bold text-gray-800 group-hover:text-indigo-700 transition-colors ml-3">
                {{ $app->patient->user->name }}
            </span>
            
            {{-- Indicador de Notas Existentes --}}
            @if(!empty($app->notes))
                <span class="absolute top-1 -left-3 flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                </span>
            @endif

            <a href="{{ route('partner.patients.show', $app->patient->id) }}" 
               target="_blank" 
               class="flex items-center gap-1 text-[11px] text-gray-500 hover:text-indigo-600 font-medium mt-0.5">
                ID: {{ $app->patient->identification }}
                <svg class="w-2.5 h-2.5 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        </div>
    </td>

    <!-- Columna: Servicio -->
    <td class="px-6 py-5">
        <div class="flex flex-col gap-1.5">
            <span class="text-xs font-semibold text-gray-700 leading-none">
                {{ $app->service->name }}
            </span>
            @if($isVirtual)
                <span class="w-fit flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-purple-100 text-purple-700 border border-purple-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-pulse mr-1.5"></span>
                    💻 Virtual @if($app->zoom_meeting_id) (ZOOM) @endif
                </span>
            @else
                <span class="w-fit flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-100">
                    🏥 Presencial
                </span>
            @endif
        </div>
    </td>

    <!-- Columna: Estado -->
    <td class="px-6 py-5">
        <div class="flex items-center">
            @if($app->status === 'confirmed')
                <span class="flex items-center text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-100">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2"></span>
                    Confirmada
                </span>
            @else
                <span class="flex items-center text-xs font-bold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full border border-amber-100">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-2"></span>
                    {{ ucfirst($app->status) }}
                </span>
            @endif
        </div>
    </td>

    <!-- Columna: Acciones -->
    <td class="px-6 py-5 text-right">
        <div class="flex justify-end items-center gap-2">
            <!-- Botón Ver Notas -->
            <button onclick="openNoteModal({{ json_encode($app->notes) }})"
                type="button"
                class="px-4 py-2 bg-amber-50 text-amber-700 hover:bg-amber-100 rounded-xl flex items-center justify-center gap-1.5 font-bold text-xs transition-all border border-amber-200/40">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/>
                </svg>
                Notas
            </button>

            @if ($app->status !== 'completed' && $app->status !== 'cancelled')
                <!-- BOTÓN DE VIDEOLLAMADA DEL DOCTOR (Condicionado al tiempo de apertura y modalidad) -->                                
                @if($isVirtual)
                    @if($app->zoom_meeting_id)
                        {{-- Si ya tiene Zoom real y está en el tiempo permitido, muestra iniciar --}}
                        @if($canStart)
                            <a href="{{ $app->zoom_start_url }}" target="_blank" 
                            class="inline-flex items-center gap-1 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider bg-indigo-600 hover:bg-indigo-700 text-white shadow-md transition-all transform hover:-translate-y-0.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z"/>
                                </svg>
                                Iniciar Consulta
                            </a>
                        @else
                            <span class="text-[10px] font-bold text-gray-400 bg-gray-50 px-2 py-1 rounded-md border border-gray-200">
                                Zoom Listo (Inicia 15m antes)
                            </span>
                        @endif
                    @else
                        {{-- SI FALLÓ EL AUTOMÁTICO: Botón manual para reintentar crear Zoom --}}
                        <form action="{{ route('partner.appointments.generate_zoom', $app->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-[11px] font-black uppercase tracking-wider bg-amber-500 hover:bg-amber-600 text-white shadow-sm transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                                </svg>
                                Generar Zoom
                            </button>
                        </form>
                    @endif
                @endif
            
                <!-- Menú desplegable adicional -->
                <button onclick="toggleDropdown(event, {{ $app->id }})" 
                    type="button"
                    class="dropdown-trigger inline-flex items-center justify-center w-9 h-9 rounded-xl bg-gray-50 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                    </svg>
                </button>
            @endif
        </div>
    </td>
</tr>
