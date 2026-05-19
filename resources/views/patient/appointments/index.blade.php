@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Mis citas',
    ]
];

$now = now();
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
        <!-- Encabezado y Filtro -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Mis Citas</h2>
                <p class="text-gray-500 text-sm">Listado cronológico de tus consultas médicas</p>
            </div>
            
            <form action="{{ route('patient.appointments.index') }}" method="GET">
                <select name="status" onchange="this.form.submit()" 
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Todos los estados</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>⏳ Pendiente</option>
                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>✅ Confirmada</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>🏁 Completada</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>❌ Cancelada</option>
                </select>
            </form>
        </div>

        <!-- Lista de Citas -->
        <ul class="space-y-4">
            @forelse($appointments as $appointment)
                @php
                switch ($appointment->status) {
                    case 'confirmed': $statusText = 'Confirmada'; break;
                    case 'pending': $statusText = 'Pendiente'; break;
                    case 'cancelled': $statusText = 'Cancelada'; break;
                    case 'completed': $statusText = 'Completada'; break;
                    default: $statusText = ucfirst($appointment->status); break;
                }

                // 1. Creamos el objeto Carbon con la fecha y hora exacta de inicio de la cita
                $start = \Carbon\Carbon::parse($appointment->date . ' ' . $appointment->start_time);

                // 2. Calculamos el final de la cita sumando la duración
                $end = $start->copy()->addMinutes($appointment->duration);

                // 3. Calculamos el momento exacto en que se debe encender el botón (15 minutos antes)
                $activationTime = $start->copy()->subMinutes(15);

                // 4. El botón de acceso estará activo únicamente si "AHORA" está entre la hora de activación y el fin de la cita
                // Y si la cita se encuentra confirmada formalmente.
                $showMeetingButton = now()->between($activationTime, $end) && ($appointment->status === 'confirmed');

                // VALIDACIÓN DE CANCELACIÓN VISUAL
                $settings = $appointment->doctor->settings;
                $allowPatientCancellation = $settings->allow_patient_cancellation ?? true;
                $cancellationNoticeHours = $settings->cancellation_notice_hours ?? 24;

                $remainingHours = now()->diffInHours($start, false);

                $canCancelVisually = $allowPatientCancellation && !$start->isPast() && ($remainingHours >= $cancellationNoticeHours) && !in_array($appointment->status, ['cancelled', 'completed']);
                @endphp

                <li class="bg-white border border-gray-200 rounded-lg shadow-sm hover:border-indigo-300 transition-colors">
                    <div class="p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        
                        <!-- Lado Izquierdo: Fecha y Hora -->
                        <div class="flex items-center gap-4 min-w-[150px]">
                            <div class="bg-indigo-50 text-indigo-700 rounded-lg p-3 text-center flex-shrink-0 min-w-[70px]">
                                <span class="block text-[10px] uppercase font-bold text-gray-500">
                                    {{ \Carbon\Carbon::parse($appointment->date)->translatedFormat('D') }}
                                </span>
                                <span class="block text-xl font-black text-gray-800 leading-none my-0.5">
                                    {{ \Carbon\Carbon::parse($appointment->date)->format('d') }}
                                </span>
                                <span class="block text-[10px] uppercase font-bold text-indigo-600">
                                    {{ \Carbon\Carbon::parse($appointment->date)->translatedFormat('M') }}
                                </span>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900">{{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}</p>
                                <p class="text-xs text-gray-500">{{ $appointment->duration }} min</p>
                            </div>
                        </div>

                        <!-- Centro: Información Médica -->
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h4 class="text-lg font-bold text-gray-900">
                                    {{ $appointment->service->name }}
                                </h4>
                                @if($appointment->service->type === 'virtual')
                                    <span class="bg-purple-100 text-purple-700 text-[10px] font-bold px-2 py-0.5 rounded border border-purple-200 uppercase">💻 Telemedicina</span>
                                @endif
                            </div>
                            <div class="gap-x-4 gap-y-1 mt-1 text-sm text-gray-600 space-y-1">
                                <span class="flex items-center font-semibold text-gray-700">
                                    <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"></path></svg>
                                    Dr. {{ $appointment->doctor->user->name }}
                                </span>
                                <span class="flex items-center text-xs text-gray-500">
                                    <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"></path></svg>
                                    {{ $appointment->address ? ($appointment->address->name .", ". $appointment->address->address) : 'Consulta Virtual desde el panel' }}
                                </span>
                            </div>
                        </div>

                        <!-- Lado Derecho: Estado y Acciones -->
                        <div class="flex flex-col items-start sm:items-end justify-between gap-3 min-w-[140px]">
                            {{-- Renderizado Dinámico de Badges de Estado --}}
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border
                                @if($appointment->status === 'confirmed') bg-green-50 text-green-700 border-green-200
                                @elseif($appointment->status === 'pending') bg-amber-50 text-amber-700 border-amber-200
                                @elseif($appointment->status === 'cancelled') bg-red-50 text-red-700 border-red-200
                                @else bg-gray-50 text-gray-700 border-gray-200 @endif">
                                {{ $statusText }}
                            </span>

                            <div class="flex flex-row sm:flex-col gap-2 w-full">
                                {{-- BOTÓN DE INGRESO PARA EL PACIENTE --}}
                                @if($showMeetingButton && $appointment->meeting_link)
                                    <a href="{{ $appointment->meeting_link }}" 
                                       target="_blank" 
                                       class="inline-flex justify-center items-center gap-1.5 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-black uppercase tracking-wider text-center w-full shadow-md transition-all transform hover:-translate-y-0.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z"/>
                                        </svg>
                                        Entrar a Consulta
                                    </a>
                                @elseif($appointment->service->type === 'virtual' && $appointment->status === 'confirmed' && !$showMeetingButton)
                                    <span class="text-[10px] font-bold text-gray-400 block sm:text-right w-full">
                                        Acceso disponible 15 min antes
                                    </span>
                                @endif

                                {{-- FORMULARIO O BOTÓN DE CANCELACIÓN --}}
                                @if($canCancelVisually)
                                    <form action="{{ route('patient.appointments.cancel', $appointment->id) }}" method="POST" 
                                          onsubmit="return confirm('¿Estás seguro de que deseas cancelar esta consulta médica?');" class="w-full">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" 
                                                class="w-full px-4 py-2 border border-gray-200 bg-white hover:bg-red-50 hover:text-red-600 hover:border-red-200 text-gray-500 rounded-xl text-xs font-bold transition-colors">
                                            Cancelar Cita
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                    </div>
                </li>
            @empty
                <div class="text-center py-16 bg-white rounded-xl border-2 border-dashed border-gray-200">
                    <p class="text-gray-400 font-medium text-sm">No tienes citas registradas con el estado seleccionado.</p>
                </div>
            @endforelse
        </ul>
    </div>
</x-admin-layout>
