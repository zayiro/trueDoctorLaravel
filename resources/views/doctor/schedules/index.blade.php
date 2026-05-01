@php
$breadcrumbs = [
    [
        'name' => 'Doctor',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Agregar horario',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-7xl mx-auto">
        <!-- Información de la Sede -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h3 class="text-2xl font-extrabold text-slate-900">{{ $address->address }}</h3>
                <p class="text-slate-500 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                    {{ $address->city->name }}, {{ $address->city->state }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <!-- Columna Izquierda: Formulario -->
            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 sticky top-28">
                    <h4 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-6">Nueva Franja</h4>
                    
                    <form action="{{ route('doctor.schedules.store') }}" method="POST" class="space-y-5">
                        @csrf
                        <input type="hidden" name="address_id" value="{{ $address->id }}">
                        
                        <!-- Día Principal -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-2 uppercase tracking-wide">Día Principal</label>
                            <select name="day" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 transition border-0 py-3 px-4 shadow-sm">
                                <option value="1">Lunes</option>
                                <option value="2">Martes</option>
                                <option value="3">Miércoles</option>
                                <option value="4">Jueves</option>
                                <option value="5">Viernes</option>
                                <option value="6">Sábado</option>
                                <option value="0">Domingo</option>
                            </select>
                        </div>

                        <!-- Horas -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-2 uppercase tracking-wide">Inicio</label>
                                <input type="time" name="start_time" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:ring-2 focus:ring-blue-500 border-0 py-3 shadow-sm" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-2 uppercase tracking-wide">Fin</label>
                                <input type="time" name="end_time" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:ring-2 focus:ring-blue-500 border-0 py-3 shadow-sm" required>
                            </div>
                        </div>

                        <!-- Duración -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-2 uppercase tracking-wide">Duración Cita</label>
                            <select name="duration" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:ring-2 focus:ring-blue-500 border-0 py-3 shadow-sm">
                                <option value="15">15 Minutos</option>
                                <option value="20" selected>20 Minutos</option>
                                <option value="30">30 Minutos</option>
                                <option value="45">45 Minutos</option>
                                <option value="60">1 Hora</option>
                            </select>
                        </div>

                        <!-- AQUÍ VA EL NUEVO CÓDIGO: Replicar a otros días -->
                        <div class="pt-2 border-t border-slate-100">
                            <label class="block text-[10px] font-black text-slate-400 mb-3 uppercase tracking-widest">También aplicar en:</label>
                            <div class="flex flex-wrap gap-2">
                                @php
                                    $dias = [
                                        ['val' => 1, 'label' => 'Lun'],
                                        ['val' => 2, 'label' => 'Mar'],
                                        ['val' => 3, 'label' => 'Mie'],
                                        ['val' => 4, 'label' => 'Jue'],
                                        ['val' => 5, 'label' => 'Vie'],
                                        ['val' => 6, 'label' => 'Sab'],
                                        ['val' => 0, 'label' => 'Dom']
                                    ];
                                @endphp
                                @foreach($dias as $d)
                                    <label class="group flex items-center gap-1.5 bg-slate-50 px-3 py-2 rounded-lg border border-slate-100 cursor-pointer hover:bg-blue-50 hover:border-blue-200 transition-all">
                                        <input type="checkbox" name="repeat_days[]" value="{{ $d['val'] }}" class="w-3.5 h-3.5 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
                                        <span class="text-[11px] font-bold text-slate-600 group-hover:text-blue-700">{{ $d['label'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Botón -->
                        <button type="submit" class="w-full bg-blue-600 text-white font-black py-4 rounded-2xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-200 active:scale-95">
                            Añadir Horario
                        </button>
                    </form>

                </div>
            </div>

            <!-- Columna Derecha: Calendario -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
                        <span class="text-sm font-bold text-slate-600">Visualización Semanal</span>
                        <div class="flex gap-2">
                            <div class="flex items-center gap-1"><span class="w-3 h-3 bg-blue-500 rounded-full"></span><span class="text-[10px] font-bold text-slate-400">DISPONIBLE</span></div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div id="calendar-schedules"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Estilos personalizados para que FullCalendar sea Minimalista */
        .fc { --fc-border-color: #f1f5f9; --fc-today-bg-color: #f8fafc; }
        .fc-theme-standard .fc-scrollgrid { border: none; }
        .fc-col-header-cell { padding: 15px 0 !important; background: #fff; }
        .fc-col-header-cell-cushion { color: #64748b; font-weight: 800; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .fc-timegrid-slot-label-cushion { color: #94a3b8; font-size: 0.7rem; font-weight: 700; color: #64748b; }
        .fc-timegrid-event { border-radius: 12px !important; border: none !important; padding: 5px !important; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        .fc-event-main { padding: 4px !important; }
        .fc-event-title { font-weight: 800 !important; font-size: 0.7rem !important; }        
        .fc-timegrid-slot { height: 4rem !important; border-bottom: 1px solid #f1f5f9 !important; }        
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar-schedules');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridDay',//timeGridDay //timeGridWeek
            locale: 'es',            
            allDaySlot: false,
            slotMinTime: '06:00:00',
            slotMaxTime: '22:00:00',
            height: 'auto',
            // 2. Formato de 12 horas (AM/PM) en el lateral
            slotLabelFormat: {
                hour: 'numeric',
                minute: '2-digit',
                omitZeroMinute: false,
                meridiem: 'short',
                hour12: true
            },
            // 3. Formato de 12 horas en las etiquetas de los eventos
            eventTimeFormat: {
                hour: 'numeric',
                minute: '2-digit',
                meridiem: 'short',
                hour12: true
            },
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'timeGridWeek,timeGridDay' // Botones para cambiar entre semana y día
            },
            dayHeaderFormat: { weekday: 'short' },
            eventClick: function(info) {
                Swal.fire({
                    title: '¿Eliminar horario?',
                    text: "Esta franja de " + info.event.title + " será eliminada.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        popup: 'rounded-3xl',
                        confirmButton: 'rounded-xl px-4 py-2 font-bold',
                        cancelButton: 'rounded-xl px-4 py-2 font-bold'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Crear un formulario temporal para enviar el DELETE
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '/doctor/schedules/' + info.event.id;
                        form.innerHTML = `
                            @csrf
                            @method('DELETE')
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            },
            events: [
                @foreach($schedules as $schedule)
                {
                    id: '{{ $schedule->id }}',
                    title: '{{ $schedule->duration }} min',
                    daysOfWeek: [ '{{ $schedule->day }}' ],
                    startTime: '{{ $schedule->start_time }}',
                    endTime: '{{ $schedule->end_time }}',
                    backgroundColor: '#3b82f6',
                },
                @endforeach
            ]
        });
        
        calendar.render();
    });
    </script>
</x-admin-layout>
