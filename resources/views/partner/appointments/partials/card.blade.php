@php
    //esta vista es para la version mobil de la tabla de citas, se muestra cada cita como una tarjeta individual con detalles y acciones rápidas.
    $ahora = now();
    $appointmentStart = \Carbon\Carbon::parse($app->date . ' ' . $app->start_time);
    
    // Usamos copy() para no alterar el objeto $appointmentStart original por referencia
    $canStart = $ahora->greaterThanOrEqualTo($appointmentStart->copy()->subMinutes(15));
    $isVirtual = $app->service->type === 'virtual';
    
    // Definimos el enlace correcto para el DOCTOR (Prioriza Zoom Start URL si existe)
    $doctorMeetingUrl = $app->zoom_start_url ?? $app->meeting_link;
@endphp

<div class="p-5 flex flex-col gap-4 border-b border-gray-100 last:border-0 active:bg-gray-50 transition-colors">
    
    <!-- Fila Superior: Hora y Estado -->
    <div class="flex justify-between items-start">
        <div class="flex flex-col">
            <span class="text-xl font-black text-gray-900 leading-none">
                {{ \Carbon\Carbon::parse($app->start_time)->format('g:i A') }}
            </span>
            <span class="text-[11px] font-bold text-indigo-500 mt-1 uppercase tracking-wider">
                {{ \Carbon\Carbon::parse($app->date)->translatedFormat('d M, Y') }} • {{ $app->duration }} MINUTOS
            </span>
        </div>
        
        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-tight {{ $app->status === 'confirmed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
            {{ $app->status === 'confirmed' ? 'Confirmado' : $app->status }}
        </span>
    </div>

    <!-- Información del Paciente -->
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm">
            {{ substr($app->patient->user->name, 0, 2) }}
        </div>
        <div class="flex flex-col">
            <h4 class="text-sm font-extrabold text-gray-800 leading-tight">
                {{ $app->patient->user->name }}
            </h4>
            <a href="{{ route('partner.patients.show', $app->patient->id) }}" class="text-xs text-indigo-600 font-medium mt-0.5">
                Ver historial médico →
            </a>
        </div>
    </div>

    <!-- Detalles del Servicio -->
    <div class="bg-gray-50 rounded-2xl p-3 flex items-center justify-between">
        <div class="flex flex-col">
            <span class="text-[10px] text-gray-500 font-bold uppercase leading-none mb-1">Servicio</span>
            <span class="text-xs font-semibold text-gray-700">{{ $app->service->name }}</span>
        </div>
        
        @if($isVirtual)
            <span class="bg-purple-600 text-white text-[10px] px-2 py-1 rounded-md font-black flex items-center gap-1">
                💻 VIRTUAL @if($app->zoom_meeting_id) (ZOOM) @endif
            </span>
        @else
            <span class="bg-blue-600 text-white text-[10px] px-2 py-1 rounded-md font-black">🏥 SEDE</span>
        @endif
    </div>

    <!-- Acciones Rápidas -->    
    <div class="flex gap-2 mt-1">
        <button onclick="openNoteModal({{ json_encode($app->notes ?? 'No hay notas para esta cita.') }})"
                type="button"
                class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl flex items-center justify-center gap-2 font-bold text-sm active:scale-95 transition-all">
            Notas
        </button>

        @if($isVirtual && $app->status === 'confirmed')
            @if($app->zoom_meeting_id)
                @if($canStart)
                    {{-- Botón activo cuando ya es hora de la consulta --}}
                    <a href="{{ $app->zoom_start_url }}" 
                    target="_blank" 
                    class="flex-1 bg-purple-600 text-white py-3 rounded-xl flex items-center justify-center gap-2 font-bold text-sm shadow-md active:scale-95 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z"/>
                        </svg>
                        Iniciar Cita
                    </a>
                @else
                    {{-- 👇 ESTADO INACTIVO: Evita que el contenedor se quede vacío antes de tiempo --}}
                    <button disabled 
                            class="flex-1 bg-gray-100 text-gray-400 py-3 rounded-xl flex items-center justify-center gap-2 font-bold text-xs border border-gray-200 cursor-not-allowed">
                        🔒 Zoom Listo (15m antes)
                    </button>
                @endif
            @else
                {{-- Botón manual versión móvil si falló el automático --}}
                <form action="{{ route('partner.appointments.generate_zoom', $app->id) }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit" 
                            class="w-full bg-amber-500 text-white py-3 rounded-xl flex items-center justify-center gap-2 font-bold text-sm shadow-md active:scale-95 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                        </svg>
                        Generar Zoom
                    </button>
                </form>
            @endif
        @endif

    </div>
</div>
