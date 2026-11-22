@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Control de Citas']
];

$statusBadges = [
    'pending'   => 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900',
    'confirmed' => 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900',
    'cancelled' => 'bg-rose-50 text-rose-700 border-rose-100 dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-900',
    'completed' => 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-900 dark:text-slate-400 dark:border-slate-800',
    'no_show'   => 'bg-orange-50 text-orange-700 border-orange-100 dark:bg-orange-950/30 dark:text-orange-400 dark:border-orange-900'
];

$statusLabels = [
    'pending'   => 'Por Confirmar',
    'confirmed' => 'Confirmada',
    'cancelled' => 'Cancelada',
    'completed' => 'Finalizada',
    'no_show'   => 'No asistió'
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
            activeCurrentDoctor: '',
            doctorOptions: [],
            doctorInfo: null,
            doctorsLoading: false,
            doctorsError: '',
            selectedDoctor: '',
            openChangeDoctor(id) {
                this.activeAppointmentId = id;
                this.doctorOptions = [];
                this.doctorInfo = null;
                this.doctorsError = '';
                this.selectedDoctor = '';
                this.doctorsLoading = true;
                this.changeDoctorModal = true;
                fetch('/clinic/appointments/' + id + '/doctor-options', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                    .then(r => r.json().then(j => ({ ok: r.ok, j: j })))
                    .then(res => {
                        if (!res.ok) { this.doctorsError = res.j.message || 'No pudimos cargar los médicos. Intenta de nuevo.'; }
                        else { this.doctorInfo = res.j; this.doctorOptions = res.j.doctors || []; }
                    })
                    .catch(() => { this.doctorsError = 'No pudimos cargar los médicos. Intenta de nuevo.'; })
                    .finally(() => { this.doctorsLoading = false; });
            }
         }">
        
        <!-- ENCABEZADO INSTITUCIONAL -->
        <div class="mb-6 px-2">
            <h4 class="text-xs font-black uppercase text-slate-400 tracking-wider">Gestión Operativa</h4>
            <p class="text-xl font-bold text-slate-800 dark:text-white">Agenda Centralizada de Citas</p>
        </div>

        {{-- Buscador por referencia de cita (solo citas de la clínica) --}}
        <div class="mb-6">
            <x-appointment-search-form label="Buscar cita por referencia" />
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
                @php
                    // status tiene cast a enum: se usa su valor string (un enum no sirve como llave de arreglo ni en in_array)
                    $st = $app->status instanceof \BackedEnum ? $app->status->value : (string) $app->status;
                @endphp
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
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase border {{ $statusBadges[$st] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $statusLabels[$st] ?? $st }}
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
                            @if($app->service?->type === 'virtual' && $st === 'confirmed' && $app->zoom_start_url)
                                <a href="{{ $app->zoom_start_url }}" target="_blank" class="inline-flex items-center justify-center p-2.5 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white border border-blue-100 rounded-xl transition-all shadow-2xs dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-900" title="Iniciar Videoconsulta (Zoom)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://w3.org"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25z"></path></svg>
                                </a>
                            @endif

                            @if(in_array($st, ['pending', 'confirmed'], true))
                                <!-- Reagendar: pantalla de turnos reales del especialista (misma que usa el médico) -->
                                <a href="{{ route('partner.appointments.reschedule', $app->id) }}"
                                   class="inline-flex items-center justify-center p-2.5 bg-amber-50 text-amber-700 hover:bg-amber-600 hover:text-white border border-amber-100 rounded-xl transition-all shadow-2xs dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900"
                                   title="Reagendar Cita">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"></path></svg>
                                </a>

                                <!-- Cambiar médico: reasignar la cita a otro médico de la clínica -->
                                <button type="button" @click="openChangeDoctor({{ $app->id }})" title="Cambiar médico"
                                        class="inline-flex items-center justify-center p-2.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white border border-indigo-100 rounded-xl transition-all shadow-2xs cursor-pointer dark:bg-indigo-950/30 dark:text-indigo-400 dark:border-indigo-900">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"></path></svg>
                                </button>

                                <!-- No asistió: cita confirmada que ya empezó -->
                                @if(app(\App\Services\AppointmentStateManager::class)->validationError($app, 'no_show') === null)
                                    <form action="{{ route('appointments.updateStatus', $app->id) }}" method="POST" class="inline"
                                          onsubmit="return confirm('¿Confirmas que el paciente no se presentó? No se puede deshacer. Si la cita fue pagada en línea, se liquidará igual al médico.');">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="no_show">
                                        <button type="submit" title="El paciente no asistió" class="inline-flex items-center justify-center px-3 py-2.5 bg-orange-50 text-orange-700 hover:bg-orange-600 hover:text-white border border-orange-100 rounded-xl transition-all shadow-2xs text-xs font-bold">
                                            No asistió
                                        </button>
                                    </form>
                                @endif

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
        <!-- 👨‍⚕️ MODAL 2: CAMBIAR MÉDICO -->
        <!-- ========================================================================= -->
        <div x-show="changeDoctorModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto px-4" x-cloak>
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="changeDoctorModal = false"></div>

            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-2xl max-w-lg w-full p-6 md:p-8 z-10 dark:bg-gray-800 dark:border-gray-700 animate-scale-up" @click.away="changeDoctorModal = false">
                <div class="flex items-center gap-3 mb-4 border-b border-slate-50 dark:border-gray-700 pb-3">
                    <div class="p-2 bg-indigo-100 text-indigo-700 rounded-xl dark:bg-indigo-950/50 dark:text-indigo-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"></path></svg>
                    </div>
                    <h3 class="text-lg font-black text-slate-800 dark:text-white">Cambiar médico</h3>
                </div>

                <template x-if="doctorsLoading">
                    <p class="text-sm text-slate-500 py-6 text-center dark:text-slate-400">Buscando médicos disponibles…</p>
                </template>

                <template x-if="!doctorsLoading && doctorsError">
                    <p class="text-sm text-rose-600 py-4" x-text="doctorsError"></p>
                </template>

                <template x-if="!doctorsLoading && !doctorsError && doctorInfo">
                    <form :action="`/clinic/appointments/${activeAppointmentId}/change-doctor`" method="POST" @submit="loading = true" class="space-y-4">
                        @csrf
                        <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4 text-xs text-slate-600 space-y-1 dark:bg-gray-700/40 dark:border-gray-700 dark:text-slate-300">
                            <p><span class="font-black uppercase tracking-wider text-slate-400">Cita</span> <span class="font-bold" x-text="doctorInfo.reference"></span></p>
                            <p><span class="font-black uppercase tracking-wider text-slate-400">Fecha</span> <span class="font-bold" x-text="doctorInfo.date_label + ' · ' + doctorInfo.time_label"></span></p>
                            <p><span class="font-black uppercase tracking-wider text-slate-400">Médico actual</span> <span class="font-bold" x-text="doctorInfo.current_doctor"></span></p>
                        </div>

                        <template x-if="!doctorInfo.can_change">
                            <p class="text-sm text-amber-700 bg-amber-50 border border-amber-100 rounded-2xl p-4" x-text="doctorInfo.message"></p>
                        </template>

                        <template x-if="doctorInfo.can_change">
                            <div class="space-y-3">
                                <p class="text-xs text-slate-500 font-medium leading-relaxed dark:text-slate-400">
                                    La fecha, la hora, la sede y el servicio no cambian. Solo pueden tomar la cita los médicos que tienen libre ese mismo turno. Se avisará por correo al paciente y a los dos médicos.
                                </p>

                                <template x-if="doctorOptions.length === 0">
                                    <p class="text-sm text-slate-500 bg-slate-50 rounded-2xl p-4 dark:bg-gray-700/40 dark:text-slate-300">No hay otros médicos activos en la clínica.</p>
                                </template>

                                <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                                    <template x-for="d in doctorOptions" :key="d.id">
                                        <label class="flex items-start gap-3 rounded-2xl border p-3 transition-all"
                                               :class="d.available ? 'border-slate-200 hover:border-indigo-400 cursor-pointer dark:border-gray-600' : 'border-slate-100 bg-slate-50 opacity-60 cursor-not-allowed dark:border-gray-700 dark:bg-gray-700/30'">
                                            <input type="radio" name="doctor_id" :value="d.id" x-model="selectedDoctor" :disabled="!d.available" class="mt-1 text-indigo-600 focus:ring-indigo-500">
                                            <span class="flex-1">
                                                <span class="block text-sm font-bold text-slate-800 dark:text-white" x-text="d.name"></span>
                                                <span class="block text-xs" :class="d.available ? 'text-emerald-600' : 'text-slate-400'" x-text="d.available ? 'Disponible a esa hora' : d.reason"></span>
                                            </span>
                                        </label>
                                    </template>
                                </div>

                                <div>
                                    <textarea name="reason" rows="2" maxlength="255" placeholder="Motivo del cambio (opcional). Se incluirá en el correo." class="w-full rounded-2xl border-slate-200 p-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner text-slate-800 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                                </div>
                            </div>
                        </template>

                        <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-50 dark:border-gray-700">
                            <button type="button" @click="changeDoctorModal = false" class="px-5 py-3 border border-slate-200 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-50 dark:border-gray-600 dark:text-slate-400 dark:hover:bg-gray-700 uppercase tracking-wider">
                                Cerrar
                            </button>
                            <button type="submit" x-show="doctorInfo.can_change" :disabled="loading || !selectedDoctor" class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-black px-6 py-3 rounded-xl text-xs uppercase tracking-wider shadow-md transition-all cursor-pointer">
                                Confirmar cambio
                            </button>
                        </div>
                    </form>
                </template>

                <template x-if="!doctorsLoading && doctorsError">
                    <div class="flex justify-end pt-2">
                        <button type="button" @click="changeDoctorModal = false" class="px-5 py-3 border border-slate-200 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-50 uppercase tracking-wider dark:border-gray-600 dark:text-slate-400">Cerrar</button>
                    </div>
                </template>
            </div>
        </div>
    </div> {{-- Cierre del x-data principal --}}
</x-admin-layout>
