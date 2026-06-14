@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Control de Citas']
];

$statusBadges = [
    'pending'   => 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900',
    'confirmed' => 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900',
    'cancelled' => 'bg-rose-50 text-rose-700 border-rose-100 dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-900',
    'completed' => 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-900 dark:text-slate-400 dark:border-slate-800'
];

$statusLabels = [
    'pending'   => 'Por Confirmar',
    'confirmed' => 'Confirmada',
    'cancelled' => 'Cancelada',
    'completed' => 'Finalizada'
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    {{-- 🔒 ESTADO REACTIVO UNIFICADO: Controla de forma segura los modales y la cita activa en memoria ram --}}
    <div class="max-w-6xl mx-auto py-8 px-4" 
         x-data="{ 
            loading: false, 
            cancellationModal: false, 
            rescheduleModal: false, 
            changeDoctorModal: false, 
            activeAppointmentId: null,
            activeAppointmentRef: '',
            activeCurrentDoctor: ''
         }">
        
        <!-- ENCABEZADO INSTITUCIONAL -->
        <div class="mb-6 px-2">
            <h4 class="text-xs font-black uppercase text-slate-400 tracking-wider">Gestión Operativa</h4>
            <p class="text-xl font-bold text-slate-800 dark:text-white">Agenda Centralizada de Citas</p>
        </div>

        <!-- BARRA DE FILTRADO AVANZADO (DISEÑO PREMIUM EN LÍNEA) -->
        <div class="bg-white border rounded-[2rem] p-5 shadow-sm border-slate-100 mb-6 dark:bg-gray-800 dark:border-gray-700">
            <form action="{{ route('partner.clinic.appointments.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-1">Filtrar por Médico</label>
                    <select name="doctor_id" class="w-full rounded-xl border-slate-200 py-2.5 px-3 text-xs bg-slate-50 text-slate-600 focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">Todos los especialistas</option>
                        @foreach($staffDoctors as $doc)
                            <option value="{{ $doc->id }}" {{ request('doctor_id') == $doc->id ? 'selected' : '' }}>
                                {{-- 🛡️ BLINDAJE: Fallback si gender no está en la consulta cruda del controlador --}}
                                @if(isset($doc->gender))
                                    {{ $doc->gender === 'female' ? 'Dra. ' : 'Dr. ' }}
                                @endif
                                {{ ucfirst($doc->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-1">Filtrar por Sede</label>
                    <select name="address_id" class="w-full rounded-xl border-slate-200 py-2.5 px-3 text-xs bg-slate-50 text-slate-600 focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">Todas las sedes</option>
                        @foreach($clinicAddresses as $addr)
                            <option value="{{ $addr->id }}" {{ request('address_id') == $addr->id ? 'selected' : '' }}>{{ $addr->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-1">Estado</label>
                    <select name="status" class="w-full rounded-xl border-slate-200 py-2.5 px-3 text-xs bg-slate-50 text-slate-600 focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">Todos los estados</option>
                        @foreach($statusLabels as $key => $label)
                            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full">
                    <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-black py-3 px-4 rounded-xl text-xs uppercase tracking-wider shadow-sm transition-all flex items-center justify-center gap-1.5 dark:bg-indigo-600 dark:hover:bg-indigo-700">
                        <!-- Heroicons SVG Nativo: Magnifying-Glass -->
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.604 10.604z"></path>
                        </svg>
                        Aplicar Filtros
                    </button>
                </div>
            </form>
        </div>
        <!-- CUADRO DE AGENDAS EN CONSOLA (DISEÑO PREMIUM CORPORATIVO) -->
        <div class="space-y-4">
            @forelse($appointments as $app)
                <div class="bg-white border rounded-[2rem] p-5 shadow-sm border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4 transition-all hover:shadow-md dark:bg-gray-800 dark:border-gray-700">
                    
                    <!-- Información Base y Paciente -->
                    <div class="flex items-start gap-4 min-w-0 flex-1">
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col items-center justify-center text-center flex-shrink-0 min-w-[70px] dark:bg-gray-700 dark:border-gray-600">
                            <span class="text-[10px] font-black uppercase text-slate-400 leading-none dark:text-slate-300">
                                {{ \Carbon\Carbon::parse($app->date)->translatedFormat('M') }}
                            </span>
                            <span class="text-xl font-black text-slate-800 mt-1 leading-none dark:text-white">
                                {{ \Carbon\Carbon::parse($app->date)->format('d') }}
                            </span>
                        </div>

                        <div class="min-w-0 space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-[10px] font-mono bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-bold uppercase tracking-wider dark:bg-gray-700 dark:text-gray-300">
                                    Ref: {{ $app->reference }}
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase border {{ $statusBadges[$app->status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $statusLabels[$app->status] ?? $app->status }}
                                </span>
                                
                                {{-- Indicador de Modalidad con SVG Nativo --}}
                                @if($app->service?->type === 'virtual')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-purple-50 text-purple-700 border border-purple-100 dark:bg-purple-950/30 dark:text-purple-400 dark:border-purple-900">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://w3.org"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25z"></path></svg>
                                        Telemedicina
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-blue-50 text-blue-700 border border-blue-100 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-900">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://w3.org"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"></path></svg>
                                        Presencial
                                    </span>
                                @endif
                            </div>

                            {{-- 🛡️ BLINDAJE EN CASCADA ANTIFALLOS --}}
                            <h4 class="text-base font-extrabold text-slate-800 truncate leading-snug dark:text-white">
                                Paciente: {{ ucfirst($app->patient->user->name ?? ($app->patient->name ?? $app->notes)) }}
                            </h4>
                            <p class="text-[11px] text-slate-500 font-medium dark:text-slate-400">
                                Médico: <span class="font-bold text-slate-700 dark:text-slate-200">{{ $app->doctor->user->name ?? 'No asignado' }}</span> • 
                                Servicio: <span class="text-indigo-600 font-bold dark:text-indigo-400">{{ $app->service->name ?? 'General' }}</span>
                            </p>
                        </div>
                    </div>
                    <!-- Datos de Tiempo, Sede y Acciones de Gestión -->
                    <div class="flex flex-wrap items-center lg:justify-end gap-4 border-t lg:border-t-0 border-slate-50 pt-3 lg:pt-0 dark:border-gray-700">
                        <div class="text-left lg:text-right space-y-0.5">
                            <span class="text-xs font-black bg-indigo-50 text-indigo-700 px-3 py-1 rounded-xl block w-max lg:ml-auto dark:bg-indigo-950/50 dark:text-indigo-400">
                                {{ \Carbon\Carbon::parse($app->start_time)->format('g:i A') }} ({{ $app->duration }} min)
                            </span>
                            <span class="text-[11px] text-slate-400 font-bold block truncate max-w-[200px] dark:text-slate-500" title="{{ $app->address->name ?? 'Virtual' }}">
                                Sede: {{ $app->address->name ?? 'Consulta Virtual' }}
                            </span>
                        </div>

                        {{-- BOTONERA OPERATIVA CORPORATIVA --}}
                        <div class="flex items-center gap-1.5 ml-auto lg:ml-0">
                            <!-- Enlace del doctor para Zoom Anfitrión (SVG Nativo) -->
                            @if($app->service?->type === 'virtual' && $app->status === 'confirmed' && $app->zoom_start_url)
                                <a href="{{ $app->zoom_start_url }}" target="_blank" class="inline-flex items-center justify-center p-2.5 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white border border-blue-100 rounded-xl transition-all shadow-2xs dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-900" title="Iniciar Videoconsulta (Zoom)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://w3.org"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25z"></path></svg>
                                </a>
                            @endif

                            @if(in_array($app->status, ['pending', 'confirmed']))
                                <!-- Reagendar Fecha / Hora (Abre Modal Alpine) -->
                                <button type="button" 
                                        @click="activeAppointmentId = '{{ $app->id }}'; activeAppointmentRef = '{{ $app->reference }}'; rescheduleModal = true" 
                                        class="inline-flex items-center justify-center p-2.5 bg-amber-50 text-amber-700 hover:bg-amber-600 hover:text-white border border-amber-100 rounded-xl transition-all shadow-2xs cursor-pointer dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900" 
                                        title="Reagendar Cita">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://w3.org"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008z"></path></svg>
                                </button>

                                <!-- Cambiar de Médico (Abre Modal Alpine) -->
                                <button type="button" 
                                        @click="activeAppointmentId = '{{ $app->id }}'; activeAppointmentRef = '{{ $app->reference }}'; activeCurrentDoctor = '{{ $app->doctor->user->name ?? '' }}'; changeDoctorModal = true" 
                                        class="inline-flex items-center justify-center p-2.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white border border-indigo-100 rounded-xl transition-all shadow-2xs cursor-pointer dark:bg-indigo-950/30 dark:text-indigo-400 dark:border-indigo-900" 
                                        title="Cambiar Especialista (Reasignar)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://w3.org"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0zM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.766z"></path></svg>
                                </button>

                                <!-- Cancelar Cita -->
                                <button type="button" @click="activeAppointmentId = '{{ $app->id }}'; cancellationModal = true" class="inline-flex items-center justify-center px-3 py-2.5 bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white border border-rose-100 rounded-xl text-xs font-black uppercase tracking-wider transition-all shadow-2xs cursor-pointer dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-900">
                                    Cancelar Cita
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white border border-dashed border-slate-200 rounded-[2rem] p-12 text-center text-slate-400 dark:bg-gray-800 dark:border-gray-700 dark:text-slate-500">
                    <p class="text-sm font-medium">No se encontraron citas agendadas bajo los parámetros de filtrado seleccionados.</p>
                </div>
            @endforelse

            <!-- PAGINACIÓN NATIVA DEL SAAS -->
            @if($appointments->isNotEmpty())
                <div class="pt-2">
                    {{ $appointments->links() }}
                </div>
            @endif
        </div>
        <!-- ========================================================================= -->
        <!-- 🛡️ MODAL 1: CANCELACIÓN CONTROLADA -->
        <!-- ========================================================================= -->
        <div x-show="cancellationModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto px-4" x-cloak>
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="cancellationModal = false"></div>

            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-2xl max-w-lg w-full p-6 md:p-8 z-10 dark:bg-gray-800 dark:border-gray-700 animate-scale-up" @click.away="cancellationModal = false">
                <div class="flex items-center gap-3 mb-4 border-b border-slate-50 dark:border-gray-700 pb-3">
                    <div class="p-2 bg-rose-100 text-rose-700 rounded-xl dark:bg-rose-950/50 dark:text-rose-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://w3.org"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"></path></svg>
                    </div>
                    <h3 class="text-lg font-black text-slate-800 dark:text-white">Motivo de Cancelación</h3>
                </div>

                <form :action="`/clinic/appointments/${activeAppointmentId}/cancel`" method="POST" @submit="loading = true" class="space-y-4">
                    @csrf
                    <p class="text-xs text-slate-500 font-medium leading-relaxed dark:text-slate-400">
                        Ingresa la razón por la cual se dará de baja la consulta médica de la institución. Este texto liberará la disponibilidad física/virtual del especialista.
                    </p>

                    <div>
                        <textarea name="cancellation_notes" required rows="3" placeholder="Ej: Especialista presenta incapacidad médica / Solicitud directa del paciente..." class="w-full rounded-2xl border-slate-200 p-4 text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 shadow-inner text-slate-800 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-50 dark:border-gray-700">
                        <button type="button" @click="cancellationModal = false" class="px-5 py-3 border border-slate-200 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-50 dark:border-gray-600 dark:text-slate-400 dark:hover:bg-gray-700 uppercase tracking-wider">
                            Cerrar
                        </button>
                        <button type="submit" :disabled="loading" class="bg-rose-600 hover:bg-rose-700 text-white font-black px-6 py-3 rounded-xl text-xs uppercase tracking-wider shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                            <svg x-show="loading" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Confirmar Cancelación
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!-- ========================================================================= -->
        <!-- 🗓️ MODAL 2: REAGENDAMIENTO DE FECHA Y HORA -->
        <!-- ========================================================================= -->
        <div x-show="rescheduleModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto px-4" x-cloak>
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="rescheduleModal = false"></div>

            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-2xl max-w-lg w-full p-6 md:p-8 z-10 dark:bg-gray-800 dark:border-gray-700 animate-scale-up" @click.away="rescheduleModal = false">
                <div class="flex items-center gap-3 mb-4 border-b border-slate-50 dark:border-gray-700 pb-3">
                    <div class="p-2 bg-amber-100 text-amber-700 rounded-xl dark:bg-amber-950/50 dark:text-amber-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://w3.org"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-800 dark:text-white">Reagendar Consulta</h3>
                        <p class="text-[11px] text-slate-400 font-bold">Modificando Cita Ref: <span x-text="activeAppointmentRef" class="text-indigo-600"></span></p>
                    </div>
                </div>

                <form :action="`/clinic/appointments/${activeAppointmentId}/reschedule`" method="POST" @submit="loading = true" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Nueva Fecha</label>
                            <input type="date" name="new_date" required min="{{ date('Y-m-d') }}" class="w-full rounded-xl border-slate-200 text-xs p-3 focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Nueva Hora de Inicio</label>
                            <input type="time" name="new_start_time" required class="w-full rounded-xl border-slate-200 text-xs p-3 focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-50 dark:border-gray-700">
                        <button type="button" @click="rescheduleModal = false" class="px-5 py-3 border border-slate-200 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-50 dark:border-gray-600 dark:text-slate-400 dark:hover:bg-gray-700 uppercase tracking-wider">
                            Cerrar
                        </button>
                        <button type="submit" :disabled="loading" class="bg-amber-600 hover:bg-amber-700 text-white font-black px-6 py-3 rounded-xl text-xs uppercase tracking-wider shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                            <svg x-show="loading" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!-- ========================================================================= -->
        <!-- 👥 MODAL 3: REASIGNACIÓN DE MÉDICO DEL STAFF CORPORATIVO -->
        <!-- ========================================================================= -->
        <div x-show="changeDoctorModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto px-4" x-cloak>
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="changeDoctorModal = false"></div>

            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-2xl max-w-lg w-full p-6 md:p-8 z-10 dark:bg-gray-800 dark:border-gray-700 animate-scale-up" @click.away="changeDoctorModal = false">
                <div class="flex items-center gap-3 mb-4 border-b border-slate-50 dark:border-gray-700 pb-3">
                    <div class="p-2 bg-indigo-100 text-indigo-700 rounded-xl dark:bg-indigo-950/50 dark:text-indigo-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://w3.org"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0zM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.766z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-800 dark:text-white">Reasignar Especialista</h3>
                        <p class="text-[11px] text-slate-400 font-bold">Médico actual asignado: <span x-text="activeCurrentDoctor" class="text-indigo-600 font-black"></span></p>
                    </div>
                </div>

                <form :action="`/clinic/appointments/${activeAppointmentId}/change-doctor`" method="POST" @submit="loading = true" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Seleccionar Nuevo Profesional Disponible</label>
                        <select name="new_doctor_id" required class="w-full rounded-xl border-slate-200 py-3 px-3 text-xs bg-slate-50 text-slate-700 focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">-- Elija un médico de la nómina aprobada --</option>
                            @foreach($staffDoctors as $doc)
                                <option value="{{ $doc->id }}">{{ ucfirst($doc->name) }}</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400 font-medium mt-1 leading-normal">
                            * Nota: El sistema validará automáticamente en el backend que el nuevo especialista posea la misma especialidad del servicio contratado y grillas de horarios libres vigentes.
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-50 dark:border-gray-700">
                        <button type="button" @click="changeDoctorModal = false" class="px-5 py-3 border border-slate-200 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-50 dark:border-gray-600 dark:text-slate-400 dark:hover:bg-gray-700 uppercase tracking-wider">
                            Cerrar
                        </button>
                        <button type="submit" :disabled="loading" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black px-6 py-3 rounded-xl text-xs uppercase tracking-wider shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                            <svg x-show="loading" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Reasignar Turno
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div> {{-- Cierre del x-data principal --}}
</x-admin-layout>
