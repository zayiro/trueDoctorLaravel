@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Consultorios', 'href' => route('partner.addresses.index')],
    ['name' => 'Configuración de Horarios']
];
$colorState = $address && $address->status ? 'green' : 'red';

// Preservamos la indexación nativa de tu base de datos (1=Lunes, 7=Domingo)
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
    {{-- Contenedor unificado con Alpine.js reactivo al conmutador regulatorio inyectado por el controlador --}}
    <div class="max-w-7xl mx-auto py-6 px-4" x-data="{ loading: false, showReplicate: false, selectedDoctorId: '', isReadOnly: {{ $isReadOnly ? 'true' : 'false' }} }">
        
        <!-- SECCIÓN DE ALERTAS INTELIGENTES CONTROLADAS CON ALPINE.JS -->
        <div class="space-y-4 mb-8">
            
            <!-- 🌟 BLINDAJE ABSOLUTO CONTRA EL ERROR DE TIPADO "Attempt to read property on array" -->
            @if(session('schedule_conflicts'))
                <div x-data="{ show: true }" x-show="show" x-transition class="mb-8 p-6 bg-red-50 border-2 border-red-200 rounded-[2.5rem] shadow-xl shadow-red-100">
                    <div class="flex items-center gap-3 mb-4 text-red-700">
                        <svg class="w-8 h-8 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <h4 class="font-black uppercase tracking-widest text-lg">Bloqueo de Seguridad</h4>
                    </div>
                    
                    <p class="text-sm text-red-600 mb-4 font-bold">No se puede eliminar la franja de horario. Los siguientes pacientes agendados quedarían sin atención médica:</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6">
                        @foreach(session('schedule_conflicts') as $app)
                            @php
                                // Normalización defensiva para soportar de forma segura objetos y arreglos deserializados de la sesión
                                $appData = is_array($app) ? $app : $app->toArray();
                                $patientName = 'Nombre no disponible';
                                if (is_object($app) && isset($app->patient->user->name)) {
                                    $patientName = $app->patient->user->name;
                                } elseif (isset($appData['patient']['user']['name'])) {
                                    $patientName = $appData['patient']['user']['name'];
                                }
                            @endphp
                            <div class="flex justify-between items-center text-xs bg-white p-3 rounded-xl border border-red-100 shadow-sm">
                                <span class="font-bold text-red-800">{{ $patientName }}</span>
                                <span class="text-red-500 font-black">
                                    {{ isset($appData['date']) ? \Carbon\Carbon::parse($appData['date'])->format('d/m/Y') : 'N/A' }} — 
                                    {{ isset($appData['start_time']) ? \Carbon\Carbon::parse($appData['start_time'])->format('g:i A') : 'N/A' }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex gap-4">
                        <a href="{{ route('partner.appointments.index') }}" class="flex-1 bg-red-600 text-white py-3 rounded-2xl font-black text-xs uppercase tracking-widest text-center hover:bg-red-700 transition-all shadow-md shadow-red-200">
                            Ir a Reagendar Pacientes
                        </a>
                        <button type="button" @click="show = false" class="px-6 py-3 text-red-400 font-bold text-xs uppercase tracking-wider hover:text-red-600 transition-colors">
                            Cerrar Aviso
                        </button>
                    </div>
                </div>
            @endif

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition class="flex items-center justify-between p-4 text-emerald-800 rounded-3xl bg-emerald-50 border border-emerald-100 shadow-sm">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        <div class="ms-3 text-sm font-bold">{{ session('success') }}</div>
                    </div>
                    <button type="button" @click="show = false" class="text-emerald-400 hover:text-emerald-600 font-black text-sm px-2">×</button>
                </div>
            @endif

            @if (session('error'))
                <div x-data="{ show: true }" x-show="show" x-transition class="flex items-center justify-between p-4 text-red-800 rounded-3xl bg-red-50 border border-red-100 shadow-sm">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div class="ms-3 text-sm font-bold">{{ session('error') }}</div>
                    </div>
                    <button type="button" @click="show = false" class="text-red-400 hover:text-red-600 font-black text-sm px-2">×</button>
                </div>
            @endif
        </div>

        <!-- CABECERA DE SEDE MULTIPERFIL -->
        <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-4 bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
            <div>
                <span class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em] mb-2 block">Sede Seleccionada</span>
                <h3 class="text-3xl font-black text-slate-900 tracking-tight">{{ $address ? $address->name : 'Sin Sede' }}</h3>
                <p class="text-slate-500 flex items-center gap-2 mt-1 font-medium text-sm">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                    {{ $address ? $address->address : '' }}{{ $address && $address->type === 'virtual' ? ' (Canal Digital)' : ', ' . ($address->city->name ?? '') }}
                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-tighter {{ $colorState === 'green' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                        {{ $colorState === 'green' ? 'Sede Activa' : 'Inactiva' }}
                    </span>
                </p>
            </div>
            <a href="{{ route('partner.addresses.index') }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver a las sedes
            </a>
        </div>

        <!-- REJILLA ADAPTATIVA HÍBRIDA: Se auto-expande eliminando las columnas si Alpine detecta isReadOnly = true -->
        <div class="grid grid-cols-1 gap-6" :class="isReadOnly ? 'grid-cols-1' : 'lg:grid-cols-4'">
            <!-- COLUMNA IZQUIERDA: FORMULARIO HORARIOS (Solo visible para Consultorio Particular o Clínicas Puras) -->
            <template x-if="!isReadOnly">
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

                        <!-- Enrutamiento adaptativo automático basado en el rol -->
                        <form action="{{ auth()->user()->role === 'clinic' ? route('partner.clinic.schedules.store') : route('partner.schedules.store') }}" method="POST" @submit="loading = true" class="space-y-4">
                            @csrf
                            <input type="hidden" name="address_id" value="{{ $address?->id }}">

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
                                    @error('doctor_id') <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            <!-- Día Base Principal (Rango ISO 1=Lun, 7=Dom unificado con el backend) -->
                            <div>
                                <label for="day" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Día Principal</label>
                                <select name="day" id="day" class="w-full rounded-2xl border-slate-200 py-3 text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                    <option value="1" {{ old('day') == 1 ? 'selected' : '' }}>Lunes</option>
                                    <option value="2" {{ old('day') == 2 ? 'selected' : '' }}>Martes</option>
                                    <option value="3" {{ old('day') == 3 ? 'selected' : '' }}>Miércoles</option>
                                    <option value="4" {{ old('day') == 4 ? 'selected' : '' }}>Jueves</option>
                                    <option value="5" {{ old('day') == 5 ? 'selected' : '' }}>Viernes</option>
                                    <option value="6" {{ old('day') == 6 ? 'selected' : '' }}>Sábado</option>
                                    <option value="7" {{ old('day') == 7 ? 'selected' : '' }}>Domingo</option>
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
                            @error('end_time') <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p> @enderror

                            <!-- Replicar Días con estilo de botones compactos sr-only -->
                            <div>
                                <span class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Replicar en otros días (Opcional)</span>
                                <div class="flex flex-wrap gap-2">
                                    @foreach([1=>'L', 2=>'Ma', 3=>'Mi', 4=>'J', 5=>'V', 6=>'S', 7=>'D'] as $val => $letra)
                                        <label class="cursor-pointer">
                                            <input type="checkbox" name="replicate_days[]" value="{{ $val }}" class="sr-only peer" {{ in_array($val, old('replicate_days', [])) ? 'checked' : '' }}>
                                            <div class="w-8 h-8 flex items-center justify-center text-xs font-bold rounded-xl border border-slate-200 text-slate-500 bg-white peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 transition-all select-none">
                                                {{ $letra }}
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="pt-2">
                                <button type="submit" :disabled="loading" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-2xl shadow-lg shadow-indigo-100 transition-all uppercase tracking-wider text-xs flex items-center justify-center gap-2 cursor-pointer">
                                    <svg x-show="loading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" x-cloak><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span x-show="loading" x-cloak>Sincronizando...</span>
                                    <span x-show="!loading">Agregar a la Agenda</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </template>
            <!-- COLUMNA DERECHA: SE EXPANDE AL ANCHO TOTAL EN CONTEXTO CLÍNICA (3 COLUMNAS O 5 COLUMNAS EN MODALIDAD LECTURA) -->
            <div :class="isReadOnly ? 'w-full' : 'lg:col-span-3'" class="space-y-6">
                <div class="bg-white p-6 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-50">
                    <div class="flex justify-between items-center mb-6">
                        <div class="flex items-center gap-3">
                            <div :class="isReadOnly ? 'bg-emerald-500 shadow-emerald-200' : 'bg-indigo-500 shadow-indigo-200'" class="p-2 rounded-xl shadow-lg text-white flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h4 class="font-black text-slate-800 text-sm uppercase tracking-wide">
                                <span x-show="isReadOnly">Mis Horarios Asignados por la Institución</span>
                                <span x-show="!isReadOnly">Cronograma Semanal de Turnos</span>
                            </h4>
                        </div>
                        
                        <!-- 🔒 FILTRO DEL BOTÓN DE EDICIÓN EN LOTE SEGÚN LA POTESTAD DE LA CLÍNICA -->
                        <template x-if="!isReadOnly">
                            <a href="{{ route('partner.schedules.edit', $address->id) }}" class="text-xs font-bold text-indigo-600 hover:bg-indigo-50 p-2.5 rounded-xl border border-indigo-100 transition-all uppercase tracking-wider">
                                Editar Horarios en Lote
                            </a>
                        </template>
                    </div>

                    <!-- 📆 CRONOGRAMA SEMANAL REFACTORIZADO: CONTENEDORES POR DÍA A DOS COLUMNAS -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($daysMap as $dayIndex => $dayLabel)
                            <div class="bg-slate-50/70 border border-slate-100 rounded-[2rem] p-4 flex flex-col min-h-[220px] shadow-2xs">
                                
                                <!-- Cabecera Semántica del Día (Insignia a lo ancho de la tarjeta) -->
                                <div class="py-2.5 px-4 rounded-2xl border text-xs font-black uppercase tracking-wider mb-3 shadow-3xs flex justify-between items-center {{ $daysColorMeta[$dayIndex] }}">
                                    <span>📅 {{ $dayLabel }}</span>
                                    <span class="text-[10px] opacity-70 bg-white/50 px-2 py-0.5 rounded-lg">
                                        {{ isset($schedulesByDay[$dayIndex]) ? $schedulesByDay[$dayIndex]->count() . ' bloque(s)' : 'Libre' }}
                                    </span>
                                </div>

                                <!-- Listado de Bloques del Día Específico (Se distribuyen en una mini-grilla interna de franjas) -->
                                <div class="grid gap-2 flex-1">
                                    @if(isset($schedulesByDay[$dayIndex]) && $schedulesByDay[$dayIndex]->count() > 0)
                                        @foreach($schedulesByDay[$dayIndex] as $item)
                                            <div 
                                                class="bg-white border border-slate-100 p-3 rounded-xl shadow-3xs flex flex-col justify-between gap-3 transition-all hover:border-slate-200"
                                                x-show="!selectedDoctorId || selectedDoctorId == '{{ $item->doctor_id }}'" 
                                                x-transition
                                            >
                                                <div class="space-y-1">
                                                    <!-- Rango Horario formateado por el Attribute de tu modelo -->
                                                    <span class="text-xs font-black text-slate-800 block tracking-tight">
                                                        {{ \Carbon\Carbon::parse($item->start_time)->format('g:i A') }}
                                                    </span>
                                                    <span class="text-[10px] font-bold text-slate-400 block leading-none">
                                                        hasta {{ \Carbon\Carbon::parse($item->end_time)->format('g:i A') }}
                                                    </span>

                                                    <!-- Identificador del profesional para la clínica -->
                                                    @if(auth()->user()->role === 'clinic')
                                                        <span class="text-[9px] text-indigo-600 font-bold block truncate mt-1">
                                                            👨‍⚕️ {{ $item->doctor->user->name ?? 'N/A' }}
                                                        </span>
                                                    @endif
                                                </div>

                                                <!-- Botón de eliminación individual condicionado al entorno (Ocultado en Modo Lectura) -->
                                                <template x-if="!isReadOnly">
                                                    <form action="{{ auth()->user()->role === 'clinic' ? route('partner.clinic.schedules.destroy', $item) : route('partner.schedules.destroy', $item) }}" method="POST" onsubmit="return confirm('¿Estás seguro de remover este bloque horario de la agenda pública?');" class="flex justify-end pt-1.5 border-t border-slate-50">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-[10px] font-bold text-slate-400 hover:text-red-600 transition-colors flex items-center gap-0.5 cursor-pointer">
                                                            ✕ Quitar
                                                        </button>
                                                    </form>
                                                </template>
                                            </div>
                                        @endforeach
                                        
                                    @else
                                        <div class="col-span-1 sm:col-span-2 flex items-center justify-center py-8 text-[11px] text-slate-400 italic font-medium tracking-wide">
                                            Libre de compromisos
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                </div>

                <!-- SECCIÓN SECUNDARIA: BLOQUEOS DE AGENDA / AUSENCIAS -->
                <div class="bg-white p-6 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-50">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-amber-500 p-2 rounded-xl shadow-lg shadow-amber-200 flex items-center justify-center">
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
                                    {{ is_string($unavailability->start_date) ? \Carbon\Carbon::parse($unavailability->start_date)->format('d/m/Y') : $unavailability->start_date->format('d/m/Y') }} al {{ _is_string($unavailability->end_date) ? \Carbon\Carbon::parse($unavailability->end_date)->format('d/m/Y') : $unavailability->end_date->format('d/m/Y') }}
                                </span>
                                @if(auth()->user()->role === 'clinic')
                                    <span class="text-[10px] text-slate-400 mt-1 block font-bold">
                                        Especialista: {{ $unavailability->doctor->user->name ?? 'N/A' }}
                                    </span>
                                @endif
                            </div>
                        @empty
                            <p class="text-slate-400 text-xs italic col-span-1 md:col-span-2 bg-slate-50 p-4 rounded-xl border border-dashed border-slate-200 text-center w-full">
                                No hay bloqueos temporales de agenda activos o programados.
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
