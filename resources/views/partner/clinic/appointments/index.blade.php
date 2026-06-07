@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Control de Citas']
];

$statusBadges = [
    'pending'   => 'bg-amber-50 text-amber-700 border-amber-100',
    'confirmed' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
    'cancelled' => 'bg-rose-50 text-rose-700 border-rose-100',
    'completed' => 'bg-slate-50 text-slate-700 border-slate-200'
];

$statusLabels = [
    'pending'   => 'Por Confirmar',
    'confirmed' => 'Confirmada',
    'cancelled' => 'Cancelada',
    'completed' => 'Finalizada'
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-6xl mx-auto py-8 px-4" x-data="{ loading: false, cancellationModal: false, activeAppointmentId: null }">
        
        <!-- ENCABEZADO INSTITUCIONAL -->
        <div class="mb-6 px-2">
            <h4 class="text-xs font-black uppercase text-slate-400 tracking-wider">Gestión Operativa</h4>
            <p class="text-xl font-bold text-slate-800">Agenda Centralizada de Citas</p>
        </div>

        <!-- BARRA DE FILTRADO AVANZADO (DISEÑO PREMIUM EN LÍNEA) -->
        <div class="bg-white border rounded-[2rem] p-5 shadow-sm border-slate-100 mb-6">
            <form action="{{ route('partner.clinic.appointments.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-1">Filtrar por Médico</label>
                    <select name="doctor_id" class="w-full rounded-xl border-slate-200 py-2.5 px-3 text-xs bg-slate-50 text-slate-600 focus:ring-2 focus:ring-indigo-500">
                        <option value="">Todos los especialistas</option>
                        @foreach($staffDoctors as $doc)
                            <option value="{{ $doc->id }}" {{ request('doctor_id') == $doc->id ? 'selected' : '' }}>
                                {{ $doc->gender === 'female' ? 'Dra. ' : 'Dr. ' }}{{ ucfirst($doc->user->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-1">Filtrar por Sede</label>
                    <select name="address_id" class="w-full rounded-xl border-slate-200 py-2.5 px-3 text-xs bg-slate-50 text-slate-600 focus:ring-2 focus:ring-indigo-500">
                        <option value="">Todas las sedes</option>
                        @foreach($clinicAddresses as $addr)
                            <option value="{{ $addr->id }}" {{ request('address_id') == $addr->id ? 'selected' : '' }}>{{ $addr->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-1">Estado</label>
                    <select name="status" class="w-full rounded-xl border-slate-200 py-2.5 px-3 text-xs bg-slate-50 text-slate-600 focus:ring-2 focus:ring-indigo-500">
                        <option value="">Todos los estados</option>
                        @foreach($statusLabels as $key => $label)
                            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-black py-3 px-4 rounded-xl text-xs uppercase tracking-wider shadow-sm transition-all flex items-center justify-center gap-1">
                    <x-heroicon-o-magnifying-glass class="w-4 h-4" /> Aplicar Filtros
                </button>
            </form>
        </div>
        <!-- CUADRO DE AGENDAS EN CONSOLA -->
        <div class="space-y-4">
            @forelse($appointments as $app)
                <div class="bg-white border rounded-[2rem] p-5 shadow-sm border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4 transition-all hover:shadow-md">
                    
                    <!-- Información Base y Paciente -->
                    <div class="flex items-start gap-4 min-w-0 flex-1">
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col items-center justify-center text-center flex-shrink-0 min-w-[70px]">
                            <span class="text-[10px] font-black uppercase text-slate-400 leading-none">{{ \Carbon\Carbon::parse($app->date)->translatedFormat('M') }}</span>
                            <span class="text-xl font-black text-slate-800 mt-1 leading-none">{{ \Carbon\Carbon::parse($app->date)->format('d') }}</span>
                        </div>

                        <div class="min-w-0 space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-[10px] font-mono bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-bold uppercase tracking-wider">Ref: {{ $app->reference }}</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase border {{ $statusBadges[$app->status] }}">
                                    {{ $statusLabels[$app->status] }}
                                </span>
                            </div>
                            <h4 class="text-base font-extrabold text-slate-800 truncate leading-snug">
                                Paciente: {{ ucfirst($app->patient->user->name ?? $app->notes) }}
                            </h4>
                            <p class="text-[11px] text-slate-500 font-medium">
                                Médico: <span class="font-bold text-slate-700">{{ $app->doctor->user->name }}</span> • Servicio: <span class="text-indigo-600 font-bold">{{ $app->service->name }}</span>
                            </p>
                        </div>
                    </div>
                    <!-- Datos de Tiempo, Sede y Acciones de Gestión -->
                    <div class="flex flex-wrap items-center md:justify-end gap-4 border-t md:border-t-0 border-slate-50 pt-3 md:pt-0">
                        <div class="text-left md:text-right space-y-0.5">
                            <span class="text-xs font-black bg-indigo-50 text-indigo-700 px-3 py-1 rounded-xl block w-max md:ml-auto">
                                {{ \Carbon\Carbon::parse($app->start_time)->format('g:i A') }} ({{ $app->duration }} min)
                            </span>
                            <span class="text-[11px] text-slate-400 font-bold block truncate max-w-[200px]" title="{{ $app->address->name ?? 'Telemedicina' }}">
                                Sede: {{ $app->address->name ?? 'Consulta Virtual' }}
                            </span>
                        </div>

                        <div class="flex items-center gap-1.5 ml-auto md:ml-0">
                            <!-- Si la cita es virtual y está confirmada, expone enlace del doctor de tu esquema de Zoom -->
                            @if($app->service->type === 'virtual' && $app->status === 'confirmed' && $app->zoom_start_url)
                                <a href="{{ $app->zoom_start_url }}" target="_blank" class="inline-flex items-center justify-center p-2.5 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white border border-blue-100 rounded-xl transition-all shadow-2xs" title="Iniciar como Anfitrión (Zoom)">
                                    <x-heroicon-o-video-camera class="w-4 h-4" />
                                </a>
                            @endif

                            <!-- Botón Cancelar Interceptado por Modal Alpine -->
                            @if(in_array($app->status, ['pending', 'confirmed']))
                                <button type="button" @click="activeAppointmentId = '{{ $app->id }}'; cancellationModal = true" class="inline-flex items-center justify-center px-3 py-2.5 bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white border border-rose-100 rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-2xs cursor-pointer">
                                    Cancelar Cita
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white border border-dashed border-slate-200 rounded-[2rem] p-12 text-center text-slate-400">
                    <p class="text-sm font-medium">No se encontraron citas agendadas bajo los parámetros de filtrado seleccionados.</p>
                </div>
            @endforelse

            <!-- PAGINACIÓN NATIVA DEL SAAS -->
            <div class="pt-2">
                {{ $appointments->links() }}
            </div>
        </div>
        <!-- COMPONENTE MODAL DE CANCELACIÓN CONTROLADA -->
        <div x-show="cancellationModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto px-4" x-cloak>
            <!-- Fondo Oscuro de Contraste -->
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="cancellationModal = false"></div>

            <!-- Contenedor de la Ventana Modal -->
            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-2xl max-w-lg w-full p-6 md:p-8 z-10 animate-scale-up" @click.away="cancellationModal = false">
                <div class="flex items-center gap-3 mb-4 border-b border-slate-50 pb-3">
                    <div class="p-2 bg-rose-100 text-rose-700 rounded-xl">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                    </div>
                    <h3 class="text-lg font-black text-slate-800">Motivo de Cancelación</h3>
                </div>

                <!-- Formulario dinámico adaptativo -->
                <form :action="`/clinic/appointments/${activeAppointmentId}/cancel`" method="POST" @submit="loading = true" class="space-y-4">
                    @csrf
                    <p class="text-xs text-slate-500 font-medium leading-relaxed">
                        Ingresa la razón por la cual se dará de baja la consulta médica. Este texto se anexará a la auditoría de la cita y liberará la grilla horaria.
                    </p>

                    <div class="flex flex-col">
                        <textarea name="cancellation_notes" required rows="3" placeholder="Ej: Especialista presenta incapacidad médica / Solicitud directa del paciente..." class="w-full rounded-2xl border-slate-200 p-4 text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 shadow-inner text-slate-800"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-50">
                        <button type="button" @click="cancellationModal = false" class="px-5 py-3 border border-slate-200 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-50 uppercase tracking-wider">
                            Cerrar
                        </button>
                        <button type="submit" :disabled="loading" class="bg-rose-600 hover:bg-rose-700 text-white font-black px-6 py-3 rounded-xl text-xs uppercase tracking-wider shadow-md shadow-rose-100 transition-all flex items-center justify-center gap-1">
                            <svg x-show="loading" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" x-cloak><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Confirmar Cancelación
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div> {{-- Cierre de x-data Alpine.js principal --}}
</x-admin-layout>
