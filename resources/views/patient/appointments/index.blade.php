@php
// Definición de las migas de pan adaptadas al diseño del SaaS
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Mis Citas',
    ]
];

$now = now();
$hasStatusFilter = filled(request('status'));
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    {{-- Mensajes de Éxito o Alertas de Error del Servidor --}}
    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 shadow-sm rounded-r-2xl" role="alert">
            <div class="flex items-center">
                <span class="mr-2">✅</span>
                <p class="font-bold">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 shadow-sm rounded-r-2xl" role="alert">
            <div class="flex items-center">
                <span class="mr-2">⚠️</span>
                <p class="font-bold">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Errores de Validación (Formularios) --}}
    @if ($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 shadow-sm rounded-r-2xl" role="alert">
            <div class="flex items-center mb-2">
                <span class="mr-2">❌</span>
                <p class="font-bold">Por favor, corrige los siguientes errores:</p>
            </div>
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="py-8 px-4 max-w-5xl mx-auto">
        <!-- Encabezado de la Sección y Filtro por Estados -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h2 class="text-2xl font-black text-gray-800">Mis Citas</h2>
                <p class="text-gray-500 text-sm font-medium">Listado cronológico de tus consultas médicas</p>
            </div>
            
            <form action="{{ route('patient.appointments.index') }}" method="GET" class="w-full sm:w-auto">
                <select name="status" onchange="this.form.submit()" 
                    class="block w-full rounded-xl border-slate-200 shadow-sm font-bold text-sm text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 py-2.5 pl-4 pr-10 bg-white">
                    <option value="">Todos los estados</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>⏳ Pendiente</option>
                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>✅ Confirmada</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>🏁 Completada</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>❌ Cancelada</option>
                </select>
            </form>
        </div>
        <!-- ========================================================================= -->
        <!-- SECCIÓN 1: CITAS PRÓXIMAS (ACTUALES Y FUTURAS)                            -->
        <!-- ========================================================================= -->
        @if(!$hasStatusFilter || in_array(request('status'), ['pending', 'confirmed']))
            <div class="mb-10">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-4 ml-1">Próximas Consultas</h3>
                
                <ul class="space-y-4">
                    @forelse($upcomingAppointments as $appointment)
                        @php
                        switch ($appointment->status) {
                            case 'confirmed': $statusText = 'Confirmada'; break;
                            case 'pending': $statusText = 'Pendiente'; break;
                            case 'cancelled': $statusText = 'Cancelada'; break;
                            case 'completed': $statusText = 'Completada'; break;
                            default: $statusText = ucfirst($appointment->status); break;
                        }

                        $dateOnly = \Carbon\Carbon::parse($appointment->date)->toDateString(); 
                        $start = \Carbon\Carbon::parse($dateOnly . ' ' . $appointment->start_time, 'America/Bogota');
                        
                        $end = $start->copy()->addMinutes($appointment->duration);
                        $activationTime = $start->copy()->subMinutes(15);

                        $showMeetingButton = now('America/Bogota')->between($activationTime, $end) && ($appointment->status === 'confirmed');

                        $settings = $appointment->doctor->settings;
                        $allowPatientCancellation = $settings->allow_patient_cancellation ?? true;
                        $cancellationNoticeHours = $settings->cancellation_notice_hours ?? 24;
                        $remainingHours = now('America/Bogota')->diffInHours($start, false);

                        $canCancelVisually = $allowPatientCancellation && !$start->isPast() && ($remainingHours >= $cancellationNoticeHours) && !in_array($appointment->status, ['cancelled', 'completed']);
                        @endphp

                        <li class="bg-white border border-gray-200 rounded-2xl shadow-sm hover:border-indigo-300 transition-colors">
                            <div class="p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                
                                <!-- Lado Izquierdo: Fecha y Hora -->
                                <div class="flex items-center gap-4 min-w-[160px]">
                                    <div class="bg-indigo-50 text-indigo-700 rounded-xl p-3 text-center flex-shrink-0 min-w-[75px]">
                                        <span class="block text-[10px] uppercase font-black text-slate-400">
                                            {{ \Carbon\Carbon::parse($appointment->date)->locale('es')->translatedFormat('D') }}
                                        </span>
                                        <span class="block text-xl font-black text-gray-800 leading-none my-1">
                                            {{ \Carbon\Carbon::parse($appointment->date)->format('d') }}
                                        </span>
                                        <span class="block text-[10px] uppercase font-black text-indigo-600">
                                            {{ \Carbon\Carbon::parse($appointment->date)->locale('es')->translatedFormat('M') }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-gray-900">{{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}</p>
                                        <p class="text-xs font-bold text-gray-400 mt-0.5">{{ $appointment->duration }} min</p>
                                    </div>
                                </div>

                                <!-- Centro: Información Médica -->
                                <div class="flex-1">
                                    <div class="flex items-center flex-wrap gap-2">
                                        <div class="flex flex-col">
                                            <!-- Referencia Única de la Cita -->
                                            <span class="text-[9px] font-black text-indigo-500 uppercase tracking-wider font-mono bg-indigo-50 px-1.5 py-0.5 rounded-md w-fit mb-1">
                                                Ref: {{ $appointment->reference ?? 'REF-PENDIENTE' }}
                                            </span>
                                            <h4 class="text-base font-black text-gray-900">
                                                {{ $appointment->service->name }}
                                            </h4>
                                        </div>
                                        @if($appointment->service->type === 'virtual')
                                            <span class="bg-purple-50 text-purple-700 text-[9px] font-black px-2 py-0.5 rounded-lg border border-purple-200 uppercase tracking-wider">💻 Telemedicina</span>
                                        @endif
                                    </div>
                                    <div class="mt-2 text-sm text-gray-600 space-y-1.5">
                                        <span class="flex items-center font-bold text-gray-700">
                                            <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"></path></svg>
                                            Dr. {{ $appointment->doctor->user->name }}
                                        </span>
                                        <span class="flex items-center text-xs font-medium text-gray-500">
                                            <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"></path></svg>
                                            {{ $appointment->address ? ($appointment->address->name . " - " . $appointment->address->address) : 'Consulta Virtual desde el panel' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Lado Derecho: Estado y Acciones -->
                                <div class="flex flex-col items-start sm:items-end justify-between gap-3 min-w-[150px]">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-black border uppercase tracking-wider
                                        @if($appointment->status === 'confirmed') bg-green-50 text-green-700 border-green-200
                                        @else bg-amber-50 text-amber-700 border-amber-200 @endif">
                                        {{ $statusText }}
                                    </span>

                                    <div class="flex flex-row sm:flex-col gap-2 w-full">
                                        <!-- 🟢 BOTÓN DE WHATSAPP: Solo visible para citas confirmadas -->
                                        @if($appointment->status === 'confirmed' && $appointment->doctor->phone)
                                            @php
                                                // 1. Limpiamos el teléfono del médico (solo números)
                                                $cleanDoctorPhone = preg_replace('/[^0-9]/', '', $appointment->doctor->phone);
                                                
                                                // 2. Redactamos el mensaje automático para el chat
                                                $whatsappMessage = "Hola Dr. " . $appointment->doctor->user->name . ", le saluda el paciente " . auth()->user()->name . ". Le contacto respecto a mi consulta de " . $appointment->service->name . " programada para el día " . \Carbon\Carbon::parse($appointment->date)->format('d/m/Y') . " (Ref: " . $appointment->reference . ").";
                                                
                                                // 3. Construimos la URL final codificada de forma segura
                                                $whatsappUrl = "https://wa.me/" . $cleanDoctorPhone . "?text=" . urlencode($whatsappMessage);
                                            @endphp

                                            <a href="{{ $whatsappUrl }}" 
                                            target="_blank" 
                                            class="inline-flex justify-center items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black uppercase tracking-wider text-center w-full shadow-md shadow-emerald-100 transition-all transform hover:-translate-y-0.5">
                                                <!-- Icono SVG Oficial de WhatsApp -->
                                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                                                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.411 0 11.973 0c3.178.001 6.165 1.24 8.407 3.485 2.242 2.246 3.476 5.237 3.475 8.417-.004 6.621-5.352 11.97-11.913 11.97-1.981-.001-3.922-.493-5.647-1.432L0 24zm6.59-4.846c1.6.95 3.197 1.451 4.782 1.452 5.41-.003 9.813-4.385 9.816-9.773.001-2.61-1.011-5.064-2.852-6.908C16.495 2.08 14.04 1.066 11.433 1.066c-5.417 0-9.821 4.384-9.824 9.772-.001 2.01.523 3.977 1.517 5.73l-.994 3.634 3.731-.973zm13.114-6.493c-.312-.156-1.847-.91-2.133-1.013-.286-.105-.494-.156-.701.156-.207.312-.804 1.013-.986 1.22-.182.208-.364.234-.676.078-.312-.156-1.318-.485-2.51-1.548-.928-.827-1.554-1.849-1.736-2.16-.182-.312-.019-.48.137-.635.141-.139.312-.364.468-.546.156-.182.208-.312.312-.52.104-.207.052-.39-.026-.546-.078-.156-.701-1.687-.961-2.311-.253-.609-.511-.527-.701-.527-.182 0-.39-.013-.597-.013-.207 0-.546.078-.83.39-.286.312-1.091 1.065-1.091 2.597 0 1.533 1.115 3.013 1.271 3.221.156.208 2.194 3.349 5.314 4.699.742.32 1.322.512 1.774.656.745.237 1.423.203 1.958.123.596-.089 1.847-.753 2.107-1.443.26-.69.26-1.286.182-1.41-.078-.124-.286-.195-.597-.351z"/>
                                                </svg>
                                                Hablar con el Doctor
                                            </a>
                                        @endif

                                        @if($showMeetingButton && $appointment->meeting_link)
                                            <a href="{{ $appointment->meeting_link }}" 
                                            target="_blank" 
                                            class="inline-flex justify-center items-center gap-1.5 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-black uppercase tracking-wider text-center w-full shadow-md transition-all transform hover:-translate-y-0.5">
                                                Entrar a Consulta
                                            </a>
                                        @elseif($appointment->service->type === 'virtual' && $appointment->status === 'confirmed' && !$showMeetingButton)
                                            <span class="text-[10px] font-black text-purple-600 bg-purple-50 p-1.5 rounded-lg border border-purple-100 text-center block w-full uppercase tracking-wider">
                                                Link activo 15 min antes
                                            </span>
                                        @endif

                                        @if($canCancelVisually)
                                            <form action="{{ route('patient.appointments.cancel', $appointment->id) }}" method="POST" 
                                                onsubmit="return confirm('¿Estás seguro de que deseas cancelar esta consulta médica?');" class="w-full">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" 
                                                        class="w-full px-4 py-2 border border-slate-200 bg-white hover:bg-red-50 hover:text-red-600 hover:border-red-200 text-slate-500 rounded-xl text-xs font-bold transition-colors">
                                                    Cancelar Cita
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>


                            </div>
                        </li>
                    @empty
                        <div class="text-center py-10 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 p-6">
                            @if(request('status'))
                                <p class="text-slate-400 font-bold text-xs uppercase tracking-wider">
                                    No tienes consultas en estado <span class="text-indigo-600">"{{ request('status') == 'pending' ? 'Pendiente' : 'Confirmada' }}"</span> programadas.
                                </p>
                            @else
                                <p class="text-slate-400 font-bold text-xs uppercase tracking-wider">
                                    No tienes ninguna consulta médica programada para los próximos días.
                                </p>
                            @endif
                        </div>
                    @endforelse
                </ul>
            </div>
        @endif
        <!-- ========================================================================= -->
        <!-- SECCIÓN 2: HISTORIAL DE CONSULTAS (PASADAS / TERMINADAS)                  -->
        <!-- ========================================================================= -->
        @if(!$hasStatusFilter || in_array(request('status'), ['completed', 'cancelled']))
            <div>
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-4 ml-1">Historial de Consultas</h3>
                
                <ul class="space-y-4">
                    @forelse($pastAppointments as $appointment)
                        @php
                        switch ($appointment->status) {
                            case 'cancelled': $statusText = 'Cancelada'; break;
                            case 'completed': $statusText = 'Completada'; break;
                            default: $statusText = ucfirst($appointment->status); break;
                        }
                        @endphp

                        <li class="bg-slate-50/50 border border-slate-200 opacity-85 rounded-2xl shadow-none">
                            <div class="p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                
                                <!-- Lado Izquierdo: Fecha y Hora -->
                                <div class="flex items-center gap-4 min-w-[160px]">
                                    <div class="bg-slate-100 text-slate-600 rounded-xl p-3 text-center flex-shrink-0 min-w-[75px]">
                                        <span class="block text-[10px] uppercase font-black text-slate-400">
                                            {{ \Carbon\Carbon::parse($appointment->date)->locale('es')->translatedFormat('D') }}
                                        </span>
                                        <span class="block text-xl font-black text-slate-600 leading-none my-1">
                                            {{ \Carbon\Carbon::parse($appointment->date)->format('d') }}
                                        </span>
                                        <span class="block text-[10px] uppercase font-black text-slate-500">
                                            {{ \Carbon\Carbon::parse($appointment->date)->locale('es')->translatedFormat('M') }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-600">{{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}</p>
                                        <p class="text-xs font-medium text-slate-400 mt-0.5">{{ $appointment->duration }} min</p>
                                    </div>
                                </div>

                                <!-- Centro: Información Médica -->
                                <div class="flex-1">
                                    <div class="flex items-center flex-wrap gap-2">
                                        <div class="flex flex-col">
                                            <!-- Referencia Histórica -->
                                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider font-mono bg-slate-100 px-1.5 py-0.5 rounded-md w-fit mb-1">
                                                Ref: {{ $appointment->reference ?? 'REF-CERRADA' }}
                                            </span>
                                            <h4 class="text-base font-bold text-slate-700 line-through">
                                                {{ $appointment->service->name }}
                                            </h4>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-sm text-gray-500 space-y-1.5">
                                        <span class="flex items-center font-semibold text-slate-600">
                                            <svg class="w-4 h-4 mr-1.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"></path></svg>
                                            Dr. {{ $appointment->doctor->user->name }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Lado Derecho: Estado Histórico -->
                                <div class="flex flex-col items-start sm:items-end justify-center min-w-[150px]">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-black border uppercase tracking-wider
                                        @if($appointment->status === 'completed') bg-blue-50 text-blue-700 border-blue-200
                                        @else bg-red-50 text-red-700 border-red-200 @endif">
                                        {{ $statusText }}
                                    </span>
                                </div>

                            </div>
                        </li>
                    @empty
                        <div class="text-center py-10 bg-white rounded-2xl border-2 border-dashed border-slate-200 p-6">
                            <p class="text-slate-400 font-bold text-xs uppercase tracking-wider">No se encontraron registros en tu historial clínico.</p>
                        </div>
                    @endforelse
                </ul>

                <!-- Paginación de Historial Conservando Filtros GET -->
                <div class="mt-6">
                    {{ $pastAppointments->appends(request()->query())->links() }}
                </div>
            </div>
        @endif

    </div>
</x-admin-layout>
