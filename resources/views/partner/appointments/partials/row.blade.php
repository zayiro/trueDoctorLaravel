@php
    $ahora = now();
    $appointmentStart = \Carbon\Carbon::parse($app->date . ' ' . $app->start_time);
    $canStart = $ahora->greaterThanOrEqualTo($appointmentStart->subMinutes(15));
    $isVirtual = $app->service->type === 'virtual';
@endphp

<tr class="group transition-all duration-1000 {{ session('success') && str_contains(session('success'), $app->patient->user->name) ? 'bg-indigo-50 ring-2 ring-inset ring-indigo-200' : 'hover:bg-indigo-50/50' }}">
    <!-- Columna: Hora -->
    <td class="px-6 py-5">
        <div class="flex flex-col">
            <span class="text-base font-black text-gray-900 leading-tight">
                {{ \Carbon\Carbon::parse($app->start_time)->format('g:i A') }}
            </span>
            <p class="text-[10px] text-indigo-600 font-bold uppercase">{{ \Carbon\Carbon::parse($app->date)->translatedFormat('D d M') }}</p>
            <span class="inline-flex items-center text-[11px] font-medium text-gray-400 mt-1">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ $app->duration }} min
            </span>
        </div>
    </td>

    <!-- Columna: Paciente -->
    <td class="px-6 py-5">
        <div class="flex flex-col">
            <span class="text-sm font-bold text-gray-800 group-hover:text-indigo-700 transition-colors">
                {{ $app->patient->user->name }}
            </span>
            {{-- Indicador de Notas --}}
            @if(!empty($app->notes))
                <span class="flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                </span>
            @endif
            <a href="{{ route('partner.patients.show', $app->patient->id) }}" 
               target="_blank" 
               class="flex items-center gap-1 text-[11px] text-gray-500 hover:text-indigo-600 font-medium mt-0.5">
                ID: {{ $app->patient->identification }}
                <svg class="w-2.5 h-2.5 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
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
                    💻 Virtual
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
            <button onclick="openNoteModal({{ json_encode($app->notes ?? 'No hay notas para esta cita.') }})"
                type="button"
                class="flex-1 bg-amber-100 text-amber-700 py-3 rounded-xl flex items-center justify-center gap-2 font-bold text-sm active:scale-95 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                Notas
            </button>

            @if($isVirtual && $app->meeting_link)
                <a href="{{ $app->meeting_link }}" 
                   target="_blank" 
                   class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-purple-600 text-white hover:bg-purple-700 hover:shadow-lg hover:shadow-purple-200 transition-all"
                   title="Unirse a videollamada">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </a>
            @endif

            @if($canStart)
                <a href="#" 
                   class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 hover:shadow-lg hover:shadow-indigo-200 transition-all"
                   title="Iniciar atención">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
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
    </td>
</tr>
