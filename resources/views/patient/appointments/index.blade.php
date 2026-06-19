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

    <div class="py-8 px-4 max-w-7xl mx-auto">
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
                        switch ($appointment->status_label) {
                            case 'confirmed': $statusText = 'Confirmada'; break;
                            case 'pending': $statusText = 'Pendiente'; break;
                            case 'cancelled': $statusText = 'Cancelada'; break;
                            case 'completed': $statusText = 'Completada'; break;
                            default: $statusText = ucfirst($appointment->status_label); break;
                        }

                        $dateOnly = \Carbon\Carbon::parse($appointment->date)->toDateString(); 
                        $start = \Carbon\Carbon::parse($dateOnly . ' ' . $appointment->start_time, 'America/Bogota');
                        
                        $end = $start->copy()->addMinutes($appointment->duration);
                        $activationTime = $start->copy()->subMinutes(15);

                        $showMeetingButton = now('America/Bogota')->between($activationTime, $end) && ($appointment->status_label === 'confirmed');

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
                                        @else
                                            <span class="bg-emerald-50 text-emerald-700 text-[9px] font-black px-2 py-0.5 rounded-lg border border-emerald-200 uppercase tracking-wider">📍 Presencial</span>
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
                                    <div class="mt-2 text-sm text-gray-600 space-y-1.5">
                                        <!-- 🟢 BOTÓN DE WHATSAPP: Solo visible para citas confirmadas -->
                                        @if($appointment->status_label === 'confirmed' && $appointment->doctor->phone)
                                            @php
                                                $cleanDoctorPhone = preg_replace('/[^0-9]/', '', $appointment->doctor->phone);
                                                $whatsappMessage = "Hola Dr(a). " . $appointment->doctor->user->name . ", le saluda el paciente " . auth()->user()->name . ". Le contacto respecto a mi consulta de " . $appointment->service->name . " programada para el día " . \Carbon\Carbon::parse($appointment->date)->format('d/m/Y') . " (Ref: " . $appointment->reference . ").";
                                                $whatsappUrl = "https://wa.me/" . $cleanDoctorPhone . "?text=" . urlencode($whatsappMessage);
                                            @endphp

                                            <a href="{{ $whatsappUrl }}" 
                                                target="_blank" 
                                                rel="noopener noreferrer" 
                                                class="inline-flex items-center gap-2 px-4 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                                                <!-- Icono estilo Heroicons (SVG Nativo) -->
                                                <svg xmlns="http://w3.org" 
                                                    fill="currentColor" 
                                                    viewBox="0 0 24 24" 
                                                    class="w-5 h-5">
                                                    <path d="M12.004 2c-5.51 0-9.993 4.483-9.993 9.993 0 1.763.461 3.42 1.262 4.873L2 22l5.304-1.392a9.922 9.922 0 0 0 4.699 1.183c5.51 0 9.994-4.483 9.994-9.993C21.997 6.483 17.514 2 12.004 2zm5.221 14.195c-.227.64-.1.115-.902.937-.738.756-1.688.855-2.853.336-2.585-1.15-4.417-3.618-5.385-4.935-.37-.503-1.026-1.511-.968-2.316.05-.688.423-1.011.664-1.242.215-.207.48-.3.69-.3.21 0 .42.01.6.1.25.13.56.66.68.91.13.27.14.57.02.82-.12.25-.26.4-.41.58-.15.17-.32.36-.14.68.39.69.96 1.34 1.63 1.9 1.11.93 2.02 1.36 2.65 1.55.45.13.84.03 1.13-.24.33-.3.99-.95 1.22-1.32.22-.36.5-.28.82-.16.32.12 2.05 1.01 2.14 1.06.1.05.23.11.28.2.09.16.03.74-.2 1.37z"/>
                                                </svg>

                                                <span>Hablar con el doctor</span>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                                <!-- Lado Derecho: Estado, Acciones y Reagendamiento (Aislado con Alpine.js) -->
                                <div class="flex flex-col items-start sm:items-end justify-between gap-3 min-w-[150px]"
                                     x-data="{ 
                                        openReschedule: false, 
                                        selectedDate: '', 
                                        slots: [], 
                                        loadingSlots: false,
                                        fetchSlots() {
                                            if (!this.selectedDate) return;
                                            this.loadingSlots = true;
                                            this.slots = [];
                                            
                                            // Consumimos tu ruta unificada e indexada enviando los parámetros exactos
                                            fetch(`/slots?date=${this.selectedDate}&doctor_id={{ $appointment->doctor_id }}&address_id={{ $appointment->address_id }}`)
                                                .then(res => res.json())
                                                .then(data => {
                                                    this.slots = data;
                                                    this.loadingSlots = false;
                                                })
                                                .catch(err => {
                                                    console.error('Error cargando agendas:', err);
                                                    this.loadingSlots = false;
                                                });
                                        }
                                     }">
                                    
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-black border uppercase tracking-wider
                                        @if($appointment->status_label === 'confirmed') bg-green-50 text-green-700 border-green-200
                                        @else bg-amber-50 text-amber-700 border-amber-200 @endif">
                                        {{ $statusText }}
                                    </span>

                                    <div class="flex flex-row sm:flex-col gap-2 w-full">                                        
                                        <!-- 🟣 BOTÓN DE TELEMEDICINA EN VIVO -->
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

                                        <!-- 🔄 BOTÓN AGREGADO: REAGENDAR CITA -->
                                        @if ($appointment->reschedule_count >= $maxReschedules)
                                            <span class="flex items-center text-sm font-medium text-gray-500">Número máximo de reagendamientos permitidos.</span>                                            
                                        @else
                                            @if(in_array($appointment->status_label, ['pending', 'confirmed']) && ($remainingHours >= $cancellationNoticeHours))
                                                <button @click="openReschedule = true" 
                                                        type="button" 
                                                        class="inline-flex justify-center items-center gap-1.5 px-4 py-2 mt-3 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-xl text-xs font-black uppercase tracking-wider text-center w-full border border-indigo-100 transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"></path>
                                                    </svg>
                                                    Reagendar Cita
                                                </button>
                                            @endif
                                        @endif

                                        <!-- ❌ BOTÓN DE CANCELACIÓN EXISTENTE -->
                                        @if($canCancelVisually)
                                            <form action="{{ route('patient.appointments.cancel', $appointment->id) }}" method="POST" 
                                                onsubmit="return confirm('¿Estás seguro de que deseas cancelar esta consulta médica?');" class="w-full">
                                                @csrf
                                                <button type="submit" 
                                                        class="w-full px-4 py-2 border border-slate-200 bg-white hover:bg-red-50 hover:text-red-600 hover:border-red-200 text-slate-500 rounded-xl text-xs font-bold transition-colors uppercase tracking-wider">
                                                    Cancelar Cita
                                                </button>
                                            </form>
                                        @endif
                                    </div>

                                    <!-- 👇 COMPONENTE MODAL CORREGIDO: El clic solo actúa en el fondo gris -->
                                    <div x-show="openReschedule" 
                                         @click.self="openReschedule = false; selectedDate = ''; slots = []"
                                         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs"
                                         x-transition:enter="transition ease-out duration-200"
                                         x-transition:enter-start="opacity-0"
                                         x-transition:enter-end="opacity-100"
                                         x-transition:leave="transition ease-in duration-150"
                                         x-transition:leave-start="opacity-100"
                                         x-transition:leave-end="opacity-0"
                                         x-cloak>
                                        
                                        <!-- Tarjeta Física Libre de cierres accidentales -->
                                        <div class="bg-white w-full max-w-md p-6 rounded-2xl shadow-xl border border-slate-100 mx-4 text-left">
                                            
                                            <!-- Cabecera -->
                                            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                                <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Reprogramar Consulta</h3>
                                                <button @click="openReschedule = false; selectedDate = ''; slots = []" type="button" class="text-slate-400 hover:text-slate-600 transition">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Formulario con eventos internos aislados -->
                                            <form action="{{ route('patient.appointments.reschedule', $appointment->id) }}" 
                                                method="POST" 
                                                @click.stop
                                                class="mt-4 space-y-4">
                                                @csrf
                                                @method('PUT')

                                                <p class="text-xs text-slate-600 leading-relaxed font-medium">
                                                    Estás modificando el horario de tu cita de <span class="font-bold text-indigo-600">{{ $appointment->service->name }}</span> con el <span class="font-bold text-slate-800">Dr. {{ $appointment->doctor->user->name }}</span>.
                                                </p>

                                                <!-- 1. Selección de Fecha -->
                                                <div>
                                                    <label class="block text-[10px] font-black text-slate-700 uppercase tracking-wider mb-1">Nueva Fecha Deseada</label>
                                                    <input type="date" 
                                                           name="new_date" 
                                                           min="{{ date('Y-m-d') }}"
                                                           x-model="selectedDate"
                                                           @change="fetchSlots()"
                                                           required
                                                           class="w-full text-xs font-bold text-slate-700 border-slate-200 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5 px-3 bg-white">
                                                </div>

                                                <!-- 2. Selección de Horas Disponibles -->
                                                <div>
                                                    <label class="block text-[10px] font-black text-slate-700 uppercase tracking-wider mb-1">Nueva Hora Disponible</label>
                                                    
                                                    <!-- Estado: Cargando slots -->
                                                    <div x-show="loadingSlots" class="text-xs font-bold text-indigo-600 py-2.5 px-1 flex items-center gap-2">
                                                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Consultando disponibilidad médica...
                                                    </div>

                                                    <!-- Selector de Horarios en tiempo real -->
                                                    <select name="new_start_time" 
                                                            x-show="!loadingSlots && slots.length > 0"
                                                            required
                                                            class="w-full text-xs font-bold text-slate-700 border-slate-200 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5 px-3 bg-white">
                                                        <option value="">Selecciona un turno libre...</option>
                                                        <template x-for="slot in slots" :key="slot.time">
                                                            <!-- El x-text muestra "6:20 AM", el :value inyecta "06:20:00" exactos para tu BD -->
                                                            <option :value="(() => {
                                                                        let [time, modifier] = slot.time.split(' ');
                                                                        let [hours, minutes] = time.split(':');
                                                                        if (hours === '12') hours = '00';
                                                                        if (modifier === 'PM') hours = parseInt(hours, 10) + 12;
                                                                        return `${hours.toString().padStart(2, '0')}:${minutes}:00`;
                                                                    })()" 
                                                                    x-text="slot.time"></option>
                                                        </template>
                                                    </select>

                                                    <!-- Estado: Sin disponibilidad -->
                                                    <div x-show="!loadingSlots && selectedDate && slots.length === 0" 
                                                         class="text-xs font-bold text-red-600 bg-red-50 border border-red-100 p-2.5 rounded-xl" x-cloak>
                                                        ⚠️ No hay horarios disponibles. Intenta con otra fecha.
                                                    </div>
                                                </div>

                                                <!-- Caja Informativa de Reglas -->
                                                <div class="p-3 bg-indigo-50 border border-indigo-100 rounded-xl flex gap-2 items-start">
                                                    <p class="text-[11px] text-indigo-900 font-bold leading-relaxed">
                                                        La re-programación está sujeta a las políticas del centro médico (Aviso: {{ $cancellationNoticeHours }} horas antes).
                                                    </p>
                                                </div>

                                                <!-- Botones de Control -->
                                                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                                                    <button @click="openReschedule = false; selectedDate = ''; slots = []" 
                                                            type="button" 
                                                            class="px-3 py-2 text-xs font-black text-slate-500 bg-slate-50 hover:bg-slate-100 rounded-xl transition tracking-wider uppercase">
                                                        Cancelar
                                                    </button>
                                                    <button type="submit" 
                                                            x-bind:disabled="slots.length === 0"
                                                            class="px-4 py-2 text-xs font-black text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition shadow-sm tracking-wider uppercase disabled:opacity-50 disabled:cursor-not-allowed">
                                                        Confirmar Cambio
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </li>
                    @empty
                        <div class="text-center py-10 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 p-6">
                            <p class="text-slate-400 font-bold text-xs uppercase tracking-wider">No tienes consultas médicas próximas programadas.</p>
                        </div>
                    @endforelse
                </ul>
            </div>
        @endif
        <!-- ========================================================================= -->
        <!-- SECCIÓN 2: HISTORIAL DE CONSULTAS (PASADAS / TERMINADAS)                  -->
        <!-- ========================================================================= -->
        @if(!$hasStatusFilter || in_array(request('status'), ['completed', 'cancelled']))
            <div class="mt-6">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-4 ml-1">Historial de Consultas</h3>
                
                <ul class="space-y-4">
                    @forelse($pastAppointments as $appointment)
                        @php                        
                        switch ($appointment->status) {
                            case 'confirmed': $statusText = 'Confirmada'; break;
                            case 'pending': $statusText = 'Pendiente'; break;
                            case 'cancelled': $statusText = 'Cancelada'; break;
                            case 'completed': $statusText = 'Completada'; break;
                            default: $statusText = ucfirst($appointment->status_label); break;
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
                            <p class="text-slate-400 font-bold text-xs uppercase tracking-wider">No se encontrarón registros en tu historial clínico.</p>
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
