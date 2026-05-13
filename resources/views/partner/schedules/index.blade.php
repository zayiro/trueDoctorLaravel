@php
$breadcrumbs = [
    ['name' => 'Doctor', 'href' => route('admin.dashboard')],
    ['name' => 'Configurar Agenda y Horarios']
];
$colorState = $address->city->state ? 'green' : 'red';
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-7xl mx-auto">
        
        <!-- SECCIÓN DE ALERTAS -->
        <div class="space-y-4 mb-8">
            @if(session('schedule_conflicts'))
                <div class="mb-8 p-6 bg-red-50 border-2 border-red-200 rounded-[2.5rem] shadow-xl shadow-red-100 animate-pulse">
                    <div class="flex items-center gap-3 mb-4 text-red-700">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <h4 class="font-black uppercase tracking-widest text-lg">Bloqueo de Seguridad</h4>
                    </div>
                    
                    <p class="text-sm text-red-600 mb-4 font-bold">No se puede eliminar el horario. Los siguientes pacientes quedarían sin atención:</p>
                    
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
                <div class="flex items-center p-4 text-emerald-800 rounded-3xl bg-emerald-50 border border-emerald-100 shadow-sm animate-fade-in-down">
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

        <!-- CABECERA DE SEDE -->
        <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-4 bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
            <div>
                <span class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em] mb-2 block">Sede Seleccionada</span>
                <h3 class="text-3xl font-black text-slate-900 tracking-tight">{{ $address->address }}</h3>
                <p class="text-slate-500 flex items-center gap-2 mt-1 font-medium">
                    
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                    {{ $address->type === 'virtual' ? 'Atención Virtual' : $address->city->name }}
                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-tighter {{ $colorState === 'green' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                        {{ $colorState === 'green' ? 'Sede Activa' : 'Inactiva' }}
                    </span>
                </p>
            </div>
            <a href="{{ route('partner.appointments.index') }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver a la Agenda
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
            <!-- COLUMNA IZQUIERDA: FORMULARIO HORARIOS -->
            <div class="lg:col-span-1">
                <div class="bg-white p-4 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-50 sticky top-24">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="bg-indigo-500 p-2 rounded-xl shadow-lg shadow-indigo-200">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest">Nueva Franja</h4>
                    </div>
                    
                    <form action="{{ route('partner.schedules.store') }}" method="POST" class="space-y-6">
                        @csrf
                        <input type="hidden" name="address_id" value="{{ $address->id }}">
                        
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 mb-2 uppercase tracking-widest">Día Principal</label>
                            <select name="day" class="w-full rounded-2xl border-none bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition py-4 px-4 font-bold text-slate-700 shadow-inner">
                                <option value="1">Lunes</option><option value="2">Martes</option><option value="3">Miércoles</option>
                                <option value="4">Jueves</option><option value="5">Viernes</option><option value="6">Sábado</option><option value="0">Domingo</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 mb-2 uppercase tracking-widest">Inicio</label>
                                <input type="time" name="start_time" value="{{ old('start_time') }}" class="w-full rounded-2xl border-none bg-slate-50 focus:ring-2 focus:ring-indigo-500 py-4 font-bold text-slate-700 shadow-inner" required>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 mb-2 uppercase tracking-widest">Fin</label>
                                <input type="time" name="end_time" value="{{ old('end_time') }}" class="w-full rounded-2xl border-none bg-slate-50 focus:ring-2 focus:ring-indigo-500 py-4 font-bold text-slate-700 shadow-inner" required>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-50">
                            <label class="block text-[10px] font-black text-slate-400 mb-4 uppercase tracking-widest">Replicar en otros días:</label>
                            <div class="grid grid-cols-4 gap-2">
                                @php 
                                    $diasArr = [
                                        ['val' => 1, 'lbl' => 'Lun'], ['val' => 2, 'lbl' => 'Mar'], 
                                        ['val' => 3, 'lbl' => 'Mie'], ['val' => 4, 'lbl' => 'Jue'], 
                                        ['val' => 5, 'lbl' => 'Vie'], ['val' => 6, 'lbl' => 'Sab'], 
                                        ['val' => 0, 'lbl' => 'Dom']
                                    ]; 
                                @endphp
                                @foreach($diasArr as $d)
                                    <label class="day-chip relative cursor-pointer">
                                        <!-- El input debe estar ANTES que el div del diseño -->
                                        <input type="checkbox" name="repeat_days[]" value="{{ $d['val'] }}" onchange="toggleDayStyle(this)" class="hidden-checkbox sr-only">
                                        
                                        <div class="chip-design text-center py-2.5 rounded-xl bg-slate-100 border-2 border-transparent text-[10px] font-black text-slate-400 transition-all hover:bg-slate-200">
                                            {{ $d['lbl'] }}
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-indigo-600 text-white font-black py-5 rounded-[2rem] hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-100 active:scale-95 text-xs uppercase tracking-[0.15em]">
                            Guardar Horario
                        </button>
                    </form>
                </div>
            </div>

            <!-- COLUMNA DERECHA: CALENDARIO -->
            <div class="lg:col-span-3 space-y-8">
                <div class="bg-white rounded-[3rem] shadow-xl shadow-slate-200/50 border border-slate-50 overflow-hidden">
                    <div class="p-4 border-b border-slate-50 bg-slate-50/30 flex justify-between items-center">
                        <div>
                            <h4 class="text-lg font-black text-slate-800 tracking-tight">Vista Semanal</h4>
                            <p class="text-xs text-slate-500 font-medium mt-1 uppercase tracking-widest">Franjas de disponibilidad activa</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-indigo-500 rounded-full animate-pulse"></span>
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest tracking-tighter">Disponible</span>
                        </div>
                    </div>
                    <div class="p-4">
                        <div id="calendar-schedules"></div>
                    </div>
                </div>

                <!-- SECCIÓN DE AUSENCIAS / VACACIONES -->
                <div class="pt-8">
                    <div class="flex items-center gap-4 mb-8">
                        <h4 class="text-2xl font-black text-slate-900 tracking-tight">Bloquear Fechas (Ausencias)</h4>
                        <div class="h-px flex-1 bg-slate-100"></div>
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Formulario Ausencia -->
                        <div class="bg-amber-50 p-8 rounded-[2.5rem] border border-amber-100 shadow-inner">
                            <form action="{{ route('partner.unavailabilities.store') }}" method="POST" class="space-y-6">
                                @csrf
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-black text-amber-600 uppercase mb-2 ml-1 tracking-widest">Desde</label>
                                        <input type="date" name="start_date" required class="w-full rounded-2xl border-none bg-white py-4 shadow-sm text-sm font-bold text-slate-700">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-amber-600 uppercase mb-2 ml-1 tracking-widest">Hasta</label>
                                        <input type="date" name="end_date" required class="w-full rounded-2xl border-none bg-white py-4 shadow-sm text-sm font-bold text-slate-700">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-amber-600 uppercase mb-2 ml-1 tracking-widest">Motivo</label>
                                    <input type="text" name="reason" placeholder="Ej: Congreso Médico" class="w-full rounded-2xl border-none bg-white py-4 shadow-sm text-sm font-medium text-slate-700">
                                </div>
                                <button type="submit" class="w-full bg-amber-500 text-white font-black py-4 rounded-[2rem] hover:bg-amber-600 transition-all shadow-lg shadow-amber-200 text-xs uppercase tracking-widest">
                                    Registrar Ausencia
                                </button>
                            </form>
                        </div>

                        <!-- Lista de Ausencias -->
                        <div class="lg:col-span-2 bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @forelse($unavailabilities as $un)
                                    <div class="flex items-center justify-between p-5 bg-slate-50 rounded-3xl border border-slate-100 group hover:border-indigo-200 transition-all">
                                        <div class="flex items-center gap-4">
                                            <div class="bg-white p-3 rounded-2xl shadow-sm text-amber-500 ring-4 ring-amber-50">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-black text-slate-800">{{ \Carbon\Carbon::parse($un->start_date)->format('d M') }} - {{ \Carbon\Carbon::parse($un->end_date)->format('d M, Y') }}</p>
                                                <p class="text-[11px] text-slate-500 font-bold uppercase tracking-tighter">{{ $un->reason ?? 'Sin motivo' }}</p>
                                            </div>
                                        </div>
                                        <form action="{{ route('partner.unavailabilities.destroy', $un->id) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-2 text-slate-300 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                @empty
                                    <div class="col-span-full py-16 text-center">
                                        <div class="bg-slate-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                        </div>
                                        <p class="text-sm font-bold text-slate-400">No hay ausencias programadas.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <form id="delete-schedule-form" action="" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>

    <script>
        function toggleDayStyle(input) {
            const design = input.nextElementSibling;
            if (input.checked) {
                design.classList.remove('bg-slate-100', 'text-slate-400', 'border-transparent');
                design.classList.add('bg-indigo-600', 'text-white', 'border-indigo-700', 'shadow-lg', 'shadow-indigo-100');
            } else {
                design.classList.add('bg-slate-100', 'text-slate-400', 'border-transparent');
                design.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-700', 'shadow-lg', 'shadow-indigo-100');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {                        
            const calendarEl = document.getElementById('calendar-schedules');
            const deleteForm = document.getElementById('delete-schedule-form');

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek',
                headerToolbar: false,
                allDaySlot: false,
                slotMinTime: '06:00:00',
                slotMaxTime: '22:00:00',
                slotDuration: '00:30:00',
                locale: 'es',
                firstDay: 1,
                height: 'auto',
                events: [
                    // --- 1. BLOQUES DE HORARIO NORMAL (Índigo) ---
                    @foreach($schedules as $sch)
                    {
                        id: '{{ $sch->id }}',
                        daysOfWeek: [{{ $sch->day }}],
                        startTime: '{{ \Carbon\Carbon::parse($sch->start_time)->format('H:i:s') }}',
                        endTime: '{{ \Carbon\Carbon::parse($sch->end_time)->format('H:i:s') }}',
                        title: 'Horario: {{ \Carbon\Carbon::parse($sch->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($sch->end_time)->format('g:i A') }}\nSede: {{ $address->address }}', // <-- Este es el Tooltip
                        display: 'block',
                        backgroundColor: '#6366f1',
                        borderColor: '#4f46e5',
                        zIndex: 1,
                        extendedProps: { type: 'schedule' }
                    },
                    @endforeach

                    // --- 2. BLOQUES DE AUSENCIA (Rojo/Gris con Texto) ---
                    @foreach($unavailabilities as $un)
                    {
                        id: 'un_{{ $un->id }}',
                        @php
                            $start = \Carbon\Carbon::parse($un->start_date);
                            $end = \Carbon\Carbon::parse($un->end_date);
                            $days = [];
                            for($date = $start->copy(); $date->lte($end); $date->addDay()) {
                                $days[] = $date->dayOfWeek;
                            }
                        @endphp
                        daysOfWeek: [{{ implode(',', array_unique($days)) }}],
                        startTime: '00:00:00',
                        endTime: '23:59:59',
                        title: '{{ $un->reason ?? 'AUSENTE' }}',
                        display: 'block',
                        backgroundColor: '#fee2e2', // bg-red-100 para que sea más sólido
                        borderColor: '#ef4444',
                        textColor: '#b91c1c',
                        zIndex: 999999, // <--- Forzamos que esté arriba de los horarios normales
                        extendedProps: { 
                            type: 'unavailability',
                            reason: '{{ $un->reason ?? 'No disponible' }}'
                        }
                    },
                    @endforeach
                ],
                // Lógica para borrar al hacer clic
                eventClick: function(info) {
                    if (confirm("¿Deseas eliminar esta franja de horario?")) {
                        // Construimos la ruta dinámicamente
                        const scheduleId = info.event.id;
                        const url = "{{ route('partner.schedules.destroy', ':id') }}".replace(':id', scheduleId);
                        
                        deleteForm.action = url;
                        deleteForm.submit();
                    }
                },
                eventContent: function(arg) {
                    if (arg.event.extendedProps.type === 'unavailability') {
                        return { 
                            html: `
                                <div class="p-2 h-full flex flex-col justify-center items-center border-l-4 border-red-500 bg-red-50/50">
                                    <svg class="w-4 h-4 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    <p class="text-[10px] font-black uppercase tracking-tighter text-red-700">AUSENTE</p>
                                    <p class="text-[9px] font-bold text-red-500 uppercase text-center leading-none mt-1">${arg.event.title}</p>
                                </div>` 
                        };
                    }

                    // Calculamos el rango para mostrarlo dentro del bloque también
                    let start = arg.event.startTime ? arg.event.startTime : ''; 
                    return { 
                        html: `
                            <div class="p-2 overflow-hidden" title="${arg.event.title}">
                                <div class="flex items-center justify-between">
                                    <p class="text-[9px] font-black uppercase text-white/70 tracking-widest">Disponible</p>
                                    <svg class="w-3 h-3 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <p class="text-[11px] font-bold text-white leading-tight mt-1">
                                    ${arg.timeText}
                                </p>
                            </div>` 
                    };
                },
                eventDidMount: function(info) {
                    if (info.event.extendedProps.type === 'unavailability') {
                        // Forzamos que se ponga por encima de todo
                        info.el.style.zIndex = "999999";
                        // Le damos una sombra para que parezca que flota sobre lo azul
                        info.el.style.boxShadow = "0 10px 15px -3px rgba(220, 38, 38, 0.3)";
                    }
                }
            });
            calendar.render();
        });
    </script>

    <style>
        /* Forzamos el estado seleccionado con CSS puro para evitar fallos de Tailwind */
        .hidden-checkbox:checked + .chip-design {
            background-color: #4f46e5 !important; /* Indigo 600 */
            color: white !important;
            border-color: #4338ca !important;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
        }
     
        /* Aseguramos que la franja de ausencia sea opaca y tape lo de abajo */
        .fc-timegrid-event {
            z-index: 1; /* Horarios normales */
        }

        .fc-timegrid-event:hover {
            background-color: #4338ca !important;
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4) !important;
        }

        /* Estilo específico para el bloque de AUSENTE */
        .fc-event-main:has(p:contains("AUSENTE")), 
        .fc-timegrid-event[style*="background-color: rgb(254, 226, 226)"] { 
            z-index: 999999 !important;
            opacity: 0.95 !important; /* Casi sólido para que no se mezcle con el azul */
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2) !important;
        }

        /* Si usas los extendedProps que pusimos antes, esta es la mejor forma: */
        .fc-event-main-frame {
            height: 100%;
        }

        /* Opcional: Cambia el cursor para indicar que es eliminable */
        .fc-event-main:after {
            content: '✕';
            position: absolute;
            top: 3px;
            right: 8px;
            font-size: 10px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .fc-timegrid-event:hover .fc-event-main:after {
            opacity: 0.5;
            color: white;
        }

        /* Estilo para el sombreado de fondo (Ausencias) */
        .fc-bg-event {
            opacity: 1 !important;
            background-image: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(239, 68, 68, 0.05) 10px,
                rgba(239, 68, 68, 0.05) 20px
            ) !important;
            border-left: 4px solid #ef4444 !important;
        }

        /* Estilo para que la ausencia se vea como una advertencia */
        .fc-v-event { /* Eventos verticales */
            border-radius: 1rem !important;
        }
        
        /* Cuando es una ausencia, le damos un estilo rayado sutil */
        [data-type="unavailability"] {
            background-image: repeating-linear-gradient(
                -45deg,
                rgba(239, 68, 68, 0.05),
                rgba(239, 68, 68, 0.05) 5px,
                transparent 5px,
                transparent 10px
            );
        }
    </style>
</x-admin-layout>
