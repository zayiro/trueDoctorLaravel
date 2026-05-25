@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Consultorios', 'href' => route('partner.addresses.index')],
    ['name' => 'Configurar Agenda y Horarios']
];
$colorState = $address->status ? 'green' : 'red';
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-7xl mx-auto py-6 px-4">
        
        <!-- SECCIÓN DE ALERTAS INTELIGENTES -->
        <div class="space-y-4 mb-8">
            @if(session('schedule_conflicts'))
                <div class="mb-8 p-6 bg-red-50 border-2 border-red-200 rounded-[2.5rem] shadow-xl shadow-red-100 animate-pulse">
                    <div class="flex items-center gap-3 mb-4 text-red-700">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <h4 class="font-black uppercase tracking-widest text-lg">Bloqueo de Seguridad</h4>
                    </div>
                    
                    <p class="text-sm text-red-600 mb-4 font-bold">No se puede eliminar la franja de horario. Los siguientes pacientes agendados quedarían sin atención médica:</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6">
                        @foreach(session('schedule_conflicts') as $app)
                            <div class="flex justify-between items-center text-xs bg-white p-3 rounded-xl border border-red-100 shadow-sm">
                                <span class="font-bold text-red-800">{{ $app->patient->user->name }}</span>
                                <span class="text-red-500 font-black">{{ \Carbon\Carbon::parse($app->date)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($app->start_time)->format('g:i A') }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex gap-4">
                        <a href="{{ route('partner.appointments.index') }}" class="flex-1 bg-red-600 text-white py-3 rounded-2xl font-black text-xs uppercase tracking-widest text-center hover:bg-red-700 transition-all">
                            Ir a Reagendar Pacientes
                        </a>
                        <button onclick="this.parentElement.parentElement.remove()" class="px-6 py-3 text-red-400 font-bold text-xs uppercase">
                            Cerrar Aviso
                        </button>
                    </div>
                </div>
            @endif

            @if (session('success'))
                <div class="flex items-center p-4 text-emerald-800 rounded-3xl bg-emerald-50 border border-emerald-100 shadow-sm">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    <div class="ms-3 text-sm font-bold">{{ session('success') }}</div>
                </div>
            @endif

            @if (session('error'))
                <div class="flex items-center p-4 text-red-800 rounded-3xl bg-red-50 border border-red-100 shadow-sm">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div class="ms-3 text-sm font-bold">{{ session('error') }}</div>
                </div>
            @endif

            @if(session('conflict_appointments'))
                <div class="p-6 bg-amber-50 border-2 border-amber-200 rounded-[2.5rem] shadow-xl shadow-amber-100/50">
                    <div class="flex items-center gap-3 mb-4 text-amber-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <h4 class="font-black uppercase tracking-widest text-sm">¡Citas en Conflicto Detectadas!</h4>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        @foreach(session('conflict_appointments') as $app)
                            <div class="flex justify-between items-center text-xs bg-white/80 p-3 rounded-xl border border-amber-200 shadow-sm">
                                <span class="font-bold text-slate-800">{{ $app->patient->user->name }}</span>
                                <span class="text-amber-600 font-black">{{ \Carbon\Carbon::parse($app->date)->format('d/m') }} - {{ \Carbon\Carbon::parse($app->start_time)->format('g:i A') }}</span>
                            </div>
                        @endforeach
                    </div>
                    <form action="{{ route('partner.unavailabilities.store') }}" method="POST" class="flex gap-3">
                        @csrf
                        @foreach(session('old_data') as $key => $value) <input type="hidden" name="{{ $key }}" value="{{ $value }}"> @endforeach
                        <input type="hidden" name="force_save" value="1">
                        <button type="submit" class="flex-1 bg-amber-600 text-white py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-amber-700 transition-all shadow-lg shadow-amber-200">Registrar de todos modos</button>
                        <a href="{{ route('partner.appointments.index') }}" class="flex-1 bg-white text-slate-600 py-3 rounded-2xl font-black text-xs uppercase tracking-widest border border-slate-200 text-center hover:bg-slate-50 transition-all">Revisar Agenda</a>
                    </form>
                </div>
            @endif
        </div>

        <!-- CABECERA DE SEDE MULTIPERFIL -->
        <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-4 bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
            <div>
                <span class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em] mb-2 block">Sede Seleccionada</span>
                <h3 class="text-3xl font-black text-slate-900 tracking-tight">{{ $address->name }}</h3>
                <p class="text-slate-500 flex items-center gap-2 mt-1 font-medium text-sm">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                    {{ $address->address }}{{ $address->type === 'virtual' ? ' (Canal Digital)' : ', ' . ($address->city->name ?? '') }}
                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-tighter {{ $colorState === 'green' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                        {{ $colorState === 'green' ? 'Sede Activa' : 'Inactiva' }}
                    </span>
                </p>
            </div>
            <a href="{{ route('partner.appointments.index') }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver a la Agenda Principal
            </a>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- COLUMNA IZQUIERDA: FORMULARIO HORARIOS -->
            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-50 sticky top-24">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="bg-indigo-500 p-2 rounded-xl shadow-lg shadow-indigo-200">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </div>
                        <h4 class="font-black text-slate-800 text-sm uppercase tracking-wide">Asignar Turno</h4>
                    </div>

                    <form action="{{ route('partner.schedules.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="address_id" value="{{ $address->id }}">

                        <!-- 🔥 SELECTOR DE DOCTORES EXCLUSIVO PARA CLÍNICAS -->
                        @if(auth()->user()->role === 'clinic')
                            <div>
                                <label for="doctor_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Especialista Asignado</label>
                                <select name="doctor_id" id="doctor_id" class="w-full rounded-2xl border-slate-200 py-3 text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                    <option value="">Selecciona un médico</option>
                                    @foreach($availableDoctors as $doc)
                                        <option value="{{ $doc->id }}" {{ old('doctor_id') == $doc->id ? 'selected' : '' }}>
                                            {{ $doc->user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <!-- Día Base -->
                        <div>
                            <label for="day" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Día Principal</label>
                            <select name="day" id="day" class="w-full rounded-2xl border-slate-200 py-3 text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                <option value="1" {{ old('day') == 1 ? 'selected' : '' }}>Lunes</option>
                                <option value="2" {{ old('day') == 2 ? 'selected' : '' }}>Martes</option>
                                <option value="3" {{ old('day') == 3 ? 'selected' : '' }}>Miércoles</option>
                                <option value="4" {{ old('day') == 4 ? 'selected' : '' }}>Jueves</option>
                                <option value="5" {{ old('day') == 5 ? 'selected' : '' }}>Viernes</option>
                                <option value="6" {{ old('day') == 6 ? 'selected' : '' }}>Sábado</option>
                                <option value="0" {{ old('day') == 0 ? 'selected' : '' }}>Domingo</option>
                            </select>
                        </div>

                        <!-- Horas del Bloque -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="start_time" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Hora Inicio</label>
                                <input type="time" name="start_time" id="start_time" value="{{ old('start_time') }}" class="w-full rounded-2xl border-slate-200 py-3 text-sm focus:ring-indigo-500" required>
                            </div>
                            <div>
                                <label for="end_time" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Hora Fin</label>
                                <input type="time" name="end_time" id="end_time" value="{{ old('end_time') }}" class="w-full rounded-2xl border-slate-200 py-3 text-sm focus:ring-indigo-500" required>
                            </div>
                        </div>

                        <!-- Replicar Días -->
                        <div>
                            <span class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Replicar en otros días (Opcional)</span>
                            <div class="flex flex-wrap gap-2">
                                @foreach([1=>'L', 2=>'M', 3=>'M', 4=>'J', 5=>'V', 6=>'S', 0=>'D'] as $val => $letra)
                                    <label class="cursor-pointer">
                                        <input type="checkbox" name="repeat_days[]" value="{{ $val }}" class="sr-only peer" {{ in_array($val, old('repeat_days', [])) ? 'checked' : '' }}>
                                        <div class="w-8 h-8 flex items-center justify-center text-xs font-bold rounded-xl border border-slate-200 text-slate-500 bg-white peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 transition-all select-none">
                                            {{ $letra }}
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Botón de Envío -->
                        <div class="pt-2">
                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-2xl shadow-lg shadow-indigo-100 transition-all uppercase tracking-wider text-xs">
                                Agregar a la Agenda
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- COLUMNA DERECHA: TABLA Y LISTADO DE TURNOS ACTIVOS -->
            <div class="lg:col-span-3 space-y-6">
                <div class="bg-white p-6 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-50">
                    <div class="flex justify-between items-center mb-6">
                        <div class="flex items-center gap-3">
                            <div class="bg-emerald-500 p-2 rounded-xl shadow-lg shadow-emerald-200">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h4 class="font-black text-slate-800 text-sm uppercase tracking-wide">Cronograma Semanal de Turnos</h4>
                        </div>
                        
                        <a href="{{ route('partner.schedules.edit', $address->id) }}" class="text-xs font-bold text-indigo-600 hover:bg-indigo-50 p-2.5 rounded-xl border border-indigo-100 transition-all uppercase tracking-wider">
                            Editar Horarios en Lote
                        </a>
                    </div>

                    <!-- Grilla del listado de turnos -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @forelse($schedules as $schedule)
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex justify-between items-center gap-4 hover:border-slate-200 transition-all">
                                <div class="space-y-1">
                                    <!-- Nombre del día de la semana -->
                                    <span class="text-xs font-black uppercase text-indigo-600 tracking-wider block">
                                        {{ $schedule->day_name }}
                                    </span>
                                    
                                    <!-- Rango horario del bloque -->
                                    <span class="text-base font-bold text-slate-800 block">
                                        {{ $schedule->range }}
                                    </span>

                                    <!-- 🔥 RENDER DINÁMICO DEL ESPECIALISTA A CARGO -->
                                    <span class="text-xs text-slate-500 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        {{ $schedule->doctor->user->name ?? 'Especialista sin asignar' }}
                                    </span>
                                </div>

                                <!-- Botón atómico de eliminación con validación de seguridad -->
                                <form action="{{ route('partner.schedules.destroy', $schedule) }}" method="POST" onsubmit="return confirm('¿Estás seguro de remover este bloque horario de la agenda pública?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2.5 bg-white text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl border border-slate-200 hover:border-red-100 shadow-sm transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        @empty
                            <div class="col-span-1 md:col-span-2 text-center py-12 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 p-6">
                                <div class="mx-auto w-10 h-10 text-slate-400 mb-2">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <h5 class="text-sm font-bold text-slate-700">No hay franjas registradas</h5>
                                <p class="text-slate-400 text-xs mt-0.5">Asigna los rangos de atención en el bloque de la izquierda para abrir la disponibilidad.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- SECCIÓN SECUNDARIA: BLOQUEOS DE AGENDA / AUSENCIAS -->
                <div class="bg-white p-6 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-50">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-amber-500 p-2 rounded-xl shadow-lg shadow-amber-200">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                        </div>
                        <h4 class="font-black text-slate-800 text-sm uppercase tracking-wide">Ausencias y Bloqueos Programados</h4>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @forelse($unavailabilities as $unavailability)
                            <div class="p-3 bg-amber-50/50 rounded-xl border border-amber-100 flex flex-col justify-center">
                                <span class="text-xs font-bold text-slate-800">
                                    {{ $unavailability->reason ?? 'Bloqueo temporal' }}
                                </span>
                                <span class="text-[11px] text-amber-700 font-medium mt-0.5">
                                    {{ $unavailability->start_date->format('d/m/Y') }} al {{ $unavailability->end_date->format('d/m/Y') }}
                                </span>
                                @if(auth()->user()->role === 'clinic')
                                    <span class="text-[10px] text-slate-400 mt-1 block font-bold">
                                        Especialista: {{ $unavailability->doctor->user->name ?? 'N/A' }}
                                    </span>
                                @endif
                            </div>
                        @empty
                            <p class="text-slate-400 text-xs italic col-span-1 md:col-span-2 bg-slate-50 p-4 rounded-xl border border-dashed border-slate-200 text-center">
                                No hay bloqueos temporales de agenda activos o programados.
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
