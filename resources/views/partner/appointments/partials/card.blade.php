@php
    $ahora = now();
    $appointmentStart = \Carbon\Carbon::parse($app->date . ' ' . $app->start_time);
    $canStart = $ahora->greaterThanOrEqualTo($appointmentStart->subMinutes(15));
    $isVirtual = $app->service->type === 'virtual'; // <-- Definirla aquí también
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
            <span class="bg-purple-600 text-white text-[10px] px-2 py-1 rounded-md font-black">💻 VIRTUAL</span>
        @else
            <span class="bg-blue-600 text-white text-[10px] px-2 py-1 rounded-md font-black">🏥 SEDE</span>
        @endif
    </div>

    <!-- Acciones Rápidas (Botones grandes para móvil) -->
    <div class="flex gap-2 mt-1">
        <button onclick="openNoteModal({{ json_encode($app->notes ?? 'No hay notas para esta cita.') }})"
                type="button"
                class="flex-1 bg-amber-100 text-amber-700 py-3 rounded-xl flex items-center justify-center gap-2 font-bold text-sm active:scale-95 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
            Notas
        </button>
        
        @if($isVirtual && $app->meeting_link)
            <a href="{{ $app->meeting_link }}" 
               target="_blank" 
               class="flex-1 bg-purple-600 text-white py-3 rounded-xl flex items-center justify-center gap-2 font-bold text-sm shadow-lg shadow-purple-100 active:scale-95 transition-transform">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                Entrar a Link
            </a>
        @endif

        @if($canStart)
            <a href="#" 
               class="flex-1 bg-indigo-600 text-white py-3 rounded-xl flex items-center justify-center gap-2 font-bold text-sm shadow-lg shadow-indigo-100 active:scale-95 transition-transform">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Iniciar Cita
            </a>
        @endif
        
        <button onclick="toggleDropdown(event, {{ $app->id }})" 
            type="button"
            class="dropdown-trigger inline-flex items-center justify-center w-9 h-9 rounded-xl bg-gray-50 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
            </svg>
        </button>
    </div>
</div>
