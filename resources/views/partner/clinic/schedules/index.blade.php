@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Grillas de Horarios Institucionales']
];

// Mapeo e indexación nativa ISO (1=Lunes, 7=Domingo) para el staff de la clínica
$daysMap = [
    1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'
];

$daysColorMeta = [
    1 => 'bg-blue-50 text-blue-800 border-blue-100',
    2 => 'bg-purple-50 text-purple-800 border-purple-100',
    3 => 'bg-green-50 text-green-800 border-green-100',
    4 => 'bg-orange-50 text-orange-800 border-orange-100',
    5 => 'bg-indigo-50 text-indigo-800 border-indigo-100',
    6 => 'bg-cyan-50 text-cyan-800 border-cyan-100',
    7 => 'bg-rose-50 text-rose-800 border-rose-100'
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    {{-- Contenedor con Alpine.js nativo. Al ser vista exclusiva de clínica, isReadOnly SIEMPRE es false --}}
    <div class="max-w-7xl mx-auto py-8 px-4" x-data="{ loading: false, showReplicate: false, selectedDoctorId: '' }">
        
        <!-- ALERTAS DEL ECOSISTEMA GLOBAL DE PRODUCCIÓN -->
        @if (session('success'))
            <div id="alert-success" class="flex items-center p-4 mb-6 text-green-800 rounded-2xl bg-green-50 border border-green-100 shadow-sm" role="alert">
                <x-heroicon-s-check-circle class="flex-shrink-0 w-5 h-5 text-green-500" />
                <div class="ms-3 text-sm font-bold">{{ session('success') }}</div>
                <button type="button" class="ms-auto bg-green-50 text-green-500 rounded-lg p-1.5 hover:bg-green-100 inline-flex items-center justify-center h-8 w-8" onclick="document.getElementById('alert-success').remove()">
                    <span class="text-xl">&times;</span>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div id="alert-error" class="flex items-center p-4 mb-6 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm" role="alert">
                <x-heroicon-s-x-circle class="flex-shrink-0 w-5 h-5 text-red-500" />
                <div class="ms-3 text-sm font-bold">{{ session('error') }}</div>
                <button type="button" class="ms-auto bg-red-50 text-red-500 rounded-lg p-1.5 hover:bg-red-100 inline-flex items-center justify-center h-8 w-8" onclick="document.getElementById('alert-error').remove()">
                    <span class="text-xl">&times;</span>
                </button>
            </div>
        @endif

        <!-- DISTRIBUCIÓN CORPORATIVA (2 COLUMNAS FORMULARIO / 3 COLUMNAS CRONOGRAMA) -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">
            <!-- COLUMNA IZQUIERDA: FORMULARIO DE ASIGNACIÓN INSTITUCIONAL (2 COLUMNAS) -->
            <div class="lg:col-span-2 bg-white border rounded-[2.5rem] p-4 shadow-sm border-slate-100">
                <div class="flex items-center gap-3 mb-5 border-b border-slate-50 pb-3">
                    <div class="p-2 bg-indigo-500 text-white rounded-xl shadow-md shadow-indigo-100">
                        <x-heroicon-o-clock class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-800 tracking-tight">Asignar Jornada</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Establece franjas horarias y clónalas de forma masiva en otros días.</p>
                    </div>
                </div>

                <form action="{{ route('partner.clinic.schedules.store') }}" method="POST" @submit="loading = true" class="space-y-4 987456">
                    @csrf
                    
                    <!-- Selector de Sede Física de la Clínica -->
                    <div class="flex flex-col">
                        <x-label value="Consultorio / Sede Asignada" class="mb-1 text-slate-500 font-bold text-xs" />
                        <select name="address_id" onchange="window.location.href='?address_id='+this.value" required class="w-full rounded-2xl border-slate-200 py-3.5 px-4 text-sm text-slate-800 bg-slate-50 focus:ring-2 focus:ring-indigo-500 font-bold shadow-2xs">
                            @foreach($addresses as $addr)
                                <option value="{{ $addr->id }}" {{ $addr->id == $address->id ? 'selected' : '' }}>🏢 {{ $addr->name }} — {{ $addr->address }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Selector Directo de Médicos de la Nómina (Sin condicionales de rol) -->
                    <div class="flex flex-col">
                        <x-label value="Médico Especialista" class="mb-1 text-slate-500 font-bold text-xs" />
                        <select name="doctor_id" x-model="selectedDoctorId" required class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm text-slate-500 bg-white focus:ring-2 focus:ring-indigo-500 shadow-inner">
                            <option value="" selected>Selecciona el profesional</option>
                            @foreach($availableDoctors as $doc)
                                <option value="{{ $doc->id }}">{{ $doc->gender === 'female' ? 'Dra. ' : 'Dr. ' }}{{ ucfirst($doc->user->name) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col">
                        <x-label value="Día Principal de Origen" class="mb-1 text-slate-500 font-bold text-xs" />
                        <select name="day" required class="w-full rounded-2xl border-slate-200 py-3.5 px-4 text-sm text-slate-500 bg-white focus:ring-2 focus:ring-indigo-500 shadow-inner">
                            <option value="" disabled selected>Selecciona el día</option>
                            @foreach($daysMap as $num => $label) 
                                <option value="{{ $num }}">{{ $label }}</option> 
                            @endforeach
                        </select>
                    </div>

                    <!-- Horas del Bloque -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex flex-col">
                            <x-label value="Hora de Entrada" class="mb-1 text-slate-500 font-bold text-xs" />
                            <input type="time" name="start_time" required class="w-full rounded-2xl border-slate-200 py-3 px-4 text-sm focus:ring-2 focus:ring-indigo-500 shadow-inner">
                        </div>
                        <div class="flex flex-col">
                            <x-label value="Hora de Salida" class="mb-1 text-slate-500 font-bold text-xs" />
                            <input type="time" name="end_time" required class="w-full rounded-2xl border-slate-200 py-3 px-4 text-sm focus:ring-2 focus:ring-indigo-500 shadow-inner">
                        </div>
                    </div>

                    <!-- Interfaz para Replicar Días con el array real replicate_days[] -->
                    <div class="pt-2">
                        <button type="button" @click="showReplicate = !showReplicate" class="flex items-center justify-between w-full p-3 bg-slate-50 rounded-xl border border-slate-100 hover:bg-slate-100 transition duration-150 text-left">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-document-duplicate class="w-4 h-4 text-indigo-500" />
                                <span class="text-xs font-bold text-slate-700">¿Replicar este bloque en otros días?</span>
                            </div>
                            <x-heroicon-s-chevron-down ::class="showReplicate ? 'rotate-180 text-indigo-600' : ''" class="w-4 h-4 text-slate-400 transition-transform duration-200" />
                        </button>

                        <div x-show="showReplicate" x-transition class="mt-3 p-3 bg-slate-50/50 border border-dashed border-slate-200 rounded-2xl space-y-2" style="display: none;" x-cloak>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wide mb-1">Selecciona los días destino:</p>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($daysMap as $num => $label)
                                    <label class="flex items-center gap-2 px-2 py-1.5 bg-white border border-slate-100 rounded-xl cursor-pointer text-xs font-semibold text-slate-700 hover:border-slate-200 shadow-2xs">
                                        <input type="checkbox" name="replicate_days[]" value="{{ $num }}" class="rounded text-indigo-600 border-slate-300 focus:ring-indigo-500 h-3.5 w-3.5 shadow-xs">
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-3 border-t border-slate-50">
                        <button type="submit" :disabled="loading" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black px-8 py-3.5 rounded-2xl text-xs uppercase tracking-wider shadow-lg shadow-indigo-100 transition-all flex items-center justify-center gap-2 w-full cursor-pointer">
                            <svg x-show="loading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" x-cloak><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-show="loading" x-cloak>Asignando jornada...</span>
                            <span x-show="!loading">Vincular Horario</span>
                        </button>
                    </div>
                </form>
            </div>
            <!-- COLUMNA DERECHA: CRONOGRAMA SEMANAL Y LISTADO DE TURNOS ACTIVOS (3 COLUMNAS) -->
            <div class="lg:col-span-3 space-y-6">
                <div class="bg-white p-6 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-50">
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 border-b border-slate-50 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-emerald-500 p-2 rounded-xl shadow-lg shadow-emerald-200 text-white">
                                <x-heroicon-o-calendar class="w-5 h-5" />
                            </div>
                            <div>
                                <h4 class="font-black text-slate-800 text-sm uppercase tracking-wide">Cronograma Semanal de Turnos</h4>
                                <p class="text-xs text-slate-400 mt-0.5">Sede institucional activa: <span class="font-bold text-slate-700">{{ $address ? $address->name : 'Sin asignar' }}</span></p>
                            </div>
                        </div>

                        <!-- Filtro interactivo de Staff directo para el rol corporativo (sin directivas defensivas) -->
                        <div class="w-full sm:w-48">
                            <select x-model="selectedDoctorId" class="w-full text-xs font-bold rounded-xl border-slate-200 bg-slate-50 text-slate-600 focus:ring-indigo-500">
                                <option value="">🎯 Ver Todo el Staff</option>
                                @foreach($availableDoctors as $doc)
                                    <option value="{{ $doc->id }}">Dr(a). {{ $doc->user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- REJILLA SEMANAL ESTILO DOCTORALIA (Layout limpio a 2 columnas para el panel institucional) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @php 
                            $flatSchedules = collect($schedules)->flatten(); 
                        @endphp

                        @forelse($flatSchedules as $schedule)
                            <div 
                                class="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex justify-between items-center gap-4 hover:border-slate-200 transition-all shadow-2xs"
                                x-show="!selectedDoctorId || selectedDoctorId == '{{ $schedule->doctor_id }}'" x-transition
                            >
                                <div class="space-y-1 min-w-0">
                                    <!-- Nombre del día de la semana mapeado desde tu base de datos -->
                                    <span class="text-xs font-black uppercase text-indigo-600 tracking-wider block">
                                        {{ $daysMap[$schedule->day] }}
                                    </span>
                                    
                                    <!-- Rango horario del bloque -->
                                    <span class="text-base font-bold text-slate-800 block">
                                        {{ $schedule->range }}
                                    </span>

                                    <!-- ESPECIALISTA A CARGO -->
                                    <span class="text-xs text-slate-500 flex items-center gap-1.5 truncate">
                                        <x-heroicon-o-user class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" />
                                        <span class="truncate font-medium">{{ $schedule->doctor->user->name ?? 'Especialista sin asignar' }}</span>
                                    </span>
                                </div>

                                <!-- Botón de eliminación directa de la clínica corporativa (Acción siempre visible) -->
                                <form action="{{ route('partner.clinic.schedules.destroy', $schedule) }}" method="POST" onsubmit="return confirm('¿Estás seguro de remover este bloque horario de la agenda pública?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2.5 bg-white text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl border border-slate-200 hover:border-red-100 shadow-sm transition-all flex-shrink-0 cursor-pointer">
                                        <x-heroicon-o-trash class="w-4 h-4" />
                                    </button>
                                </form>
                            </div>
                        @empty
                            <div class="col-span-1 md:col-span-2 text-center py-12 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 p-6">
                                <div class="mx-auto w-10 h-10 text-slate-400 mb-2">
                                    <x-heroicon-o-clock class="w-10 h-10" />
                                </div>
                                <h5 class="text-sm font-bold text-slate-700">No hay franjas registradas</h5>
                                <p class="text-slate-400 text-xs mt-0.5">Asigna los rangos de atención en el bloque de la izquierda para abrir la disponibilidad de tu staff.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- SECCIÓN SECUNDARIA: AUSENCIAS Y BLOQUEOS PROGRAMADOS DEL STAFF -->
                <div class="bg-white p-6 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-50">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-amber-500 p-2 rounded-xl shadow-lg shadow-amber-200 text-white">
                            <x-heroicon-o-no-symbol class="w-5 h-5" />
                        </div>
                        <h4 class="font-black text-slate-800 text-sm uppercase tracking-wide">Ausencias y Bloqueos Programados</h4>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @forelse($unavailabilities ?? [] as $unavailability)
                            <div 
                                class="p-3 bg-amber-50/50 rounded-xl border border-amber-100 flex flex-col justify-center shadow-2xs"
                                x-show="!selectedDoctorId || selectedDoctorId == '{{ $unavailability->doctor_id }}'" x-transition
                            >
                                <span class="text-xs font-bold text-slate-800 truncate">
                                    {{ $unavailability->reason ?? 'Bloqueo temporal' }}
                                </span>
                                <span class="text-[11px] text-amber-700 font-semibold mt-0.5">
                                    {{ \Carbon\Carbon::parse($unavailability->start_date)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($unavailability->end_date)->format('d/m/Y') }}
                                </span>
                                <span class="text-[10px] text-slate-400 mt-1 block font-bold truncate">
                                    Especialista: {{ $unavailability->doctor->user->name ?? 'N/A' }}
                                </span>
                            </div>
                        @empty
                            <p class="text-slate-400 text-xs italic col-span-1 md:col-span-2 bg-slate-50 p-4 rounded-xl border border-dashed border-slate-200 text-center w-full">
                                No hay bloqueos temporales de agenda activos o programados para el staff.
                            </p>
                        @endforelse
                    </div>
                </div>
            </div> <!-- Cierre de la columna derecha lg:col-span-3 -->

        </div> <!-- Cierre de la rejilla adaptativa grid-cols-5 -->
    </div> {{-- Cierre de x-data Alpine.js principal --}}
</x-admin-layout>
