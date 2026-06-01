@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Bloqueo de días',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-7xl mx-auto px-4 py-8" x-data="{ allDay: true }">
        
        <!-- ALERTAS TRANSACCIONALES -->
        @if(session('success'))
            <div class="p-4 mb-6 text-sm text-emerald-800 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center gap-3 shadow-xs">
                <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/></svg>
                <span class="font-bold">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 mb-6 text-sm text-red-800 rounded-2xl bg-red-50 border border-red-200 flex items-center gap-3 shadow-xs">
                <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                <span class="font-bold">{{ session('error') }}</span>
            </div>
        @endif

        <!-- TARJETA DEL FORMULARIO PRINCIPAL -->
        <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 mb-8">
            <div class="border-b border-slate-50 pb-3 mb-5">
                <h2 class="text-xl font-black text-slate-800 tracking-tight flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                    Bloquear Agenda / Registrar Ausencia
                </h2>
                <p class="text-xs text-slate-400">Configura los días u horas libres. Los espacios serán dados de baja automáticamente del motor de búsqueda.</p>
            </div>

            <!-- FORMULARIO DIRECTO A TU RUTA -->
            <form action="{{ route('partner.unavailabilities.store') }}" method="POST" class="space-y-4">
                @csrf

                @if(Auth::user()->role === 'clinic')
                    <!-- Si es clínica, inyectar el selector obligatorio de médicos del staff -->
                    <div>
                        <label for="doctor_id" class="block text-[10px] font-black text-slate-400 uppercase ml-1 mb-1 tracking-wider">Especialista Afectado</label>
                        <select name="doctor_id" id="doctor_id" required class="w-full border-0 focus:ring-2 focus:ring-indigo-500 font-bold text-slate-700 bg-slate-50 rounded-2xl py-3 px-4 text-sm">
                            <option value="">Selecciona el doctor de tu nómina</option>
                            @foreach($clinicDoctors as $doc)
                                <option value="{{ $doc->id }}" {{ old('doctor_id') == $doc->id ? 'selected' : '' }}>{{ $doc->user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Selector de Sedes ( addresses ) -->
                <div>
                    <label for="address_id" class="block text-[10px] font-black text-slate-400 uppercase ml-1 mb-1 tracking-wider">Consultorio o Sede de Atención</label>
                    <select name="address_id" id="address_id" class="w-full border-0 focus:ring-2 focus:ring-indigo-500 font-bold text-slate-700 bg-slate-50 rounded-2xl py-3 px-4 text-sm">
                        @if(Auth::user()->role === 'doctor')
                            <option value="">Todas mis sedes (Bloqueo Global Absoluto)</option>
                        @endif
                        @foreach($addresses as $addr)
                            <option value="{{ $addr->id }}" {{ old('address_id') == $addr->id ? 'selected' : '' }}>{{ $addr->name }} — {{ $addr->address }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Rango de Fechas Calendario -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="start_date" class="block text-[10px] font-black text-slate-400 uppercase ml-1 mb-1 tracking-wider">Fecha Inicial</label>
                        <input type="date" name="start_date" id="start_date" required min="{{ now()->toDateString() }}" value="{{ old('start_date') }}" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 shadow-inner">
                    </div>
                    <div>
                        <label for="end_date" class="block text-[10px] font-black text-slate-400 uppercase ml-1 mb-1 tracking-wider">Fecha Final</label>
                        <input type="date" name="end_date" id="end_date" required min="{{ now()->toDateString() }}" value="{{ old('end_date') }}" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 shadow-inner">
                    </div>
                </div>
                <!-- Selector de Tipo de Bloqueo (Todo el día o Parcial) -->
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-100/60">
                    <span class="text-xs font-black text-slate-700 uppercase tracking-wider pl-1">¿Bloquear la jornada completa? (Todo el día)</span>
                    <label class="relative inline-flex items-center cursor-pointer select-none">
                        <input type="checkbox" x-model="allDay" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </div>

                <!-- Bloques Horarios de tu Migración (Nullable) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="!allDay" x-transition style="display: none;">
                    <div>
                        <label for="start_time" class="block text-[10px] font-black text-slate-400 uppercase ml-1 mb-1 tracking-wider">Hora Salida</label>
                        <input type="time" name="start_time" id="start_time" ::required="!allDay" value="{{ old('start_time') }}" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 shadow-inner">
                    </div>
                    <div>
                        <label for="end_time" class="block text-[10px] font-black text-slate-400 uppercase ml-1 mb-1 tracking-wider">Hora Retorno</label>
                        <input type="time" name="end_time" id="end_time" ::required="!allDay" value="{{ old('end_time') }}" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 shadow-inner">
            </div>
        </div>

        <div>
            <label for="reason" class="block text-[10px] font-black text-slate-400 uppercase ml-1 mb-1 tracking-wider">Motivo Interno</label>
            <input type="text" name="reason" id="reason" value="{{ old('reason') }}" placeholder="Ej: Vacaciones anuales, Congreso, Asuntos médicos" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 shadow-inner">
        </div>

        <!-- CONTROL CONDICIONAL DE COINCIDENCIAS (FLAG FORZADO DE TU BACKEND) -->
        @if(session('conflict_appointments'))
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl space-y-3">
                <p class="text-xs font-black text-amber-900 uppercase tracking-wide">⚠️ Alerta: Existen citas de pacientes en el rango seleccionado</p>
                <div class="max-h-36 overflow-y-auto space-y-1.5 pl-2">
                    @foreach(session('conflict_appointments') as $appointment)
                        <p class="text-xs font-bold text-amber-700">
                            • {{ \Carbon\Carbon::parse($appointment->date)->format('d M') }} ({{ \Carbon\Carbon::parse($appointment->start_time)->format('h:i A') }}) — Paciente: {{ $appointment->patient->user->name }}
                        </p>
                    @endforeach
                </div>
                <!-- Inyectamos el flag de guardado forzado solicitado por tu controlador -->
                <input type="hidden" name="force_save" value="1">
                <p class="text-[11px] text-amber-600 font-medium">Si confirmas el guardado, la indisponibilidad se registrará pero deberás gestionar de forma manual las citas listadas arriba.</p>
            </div>
        @endif

        <div class="pt-2 flex justify-end">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider py-3.5 px-6 rounded-xl shadow-md transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                {{ session('conflict_appointments') ? 'Aceptar Riesgo y Bloquear' : 'Confirmar Bloqueo' }}
            </button>
        </div>
    </form>
</div>
        <!-- LISTADO DE AUSENCIAS Y BLOQUEOS ACTIVOS -->
        <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100">
            <div class="border-b border-slate-50 pb-3 mb-4">
                <h3 class="text-base font-black text-slate-800 tracking-tight">Historial de Bloqueos Activos</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 uppercase font-black tracking-wider text-[10px]">
                            <th class="py-3 px-4">Especialista</th>
                            <th class="py-3 px-4">Sede / Consultorio</th>
                            <th class="py-3 px-4">Rango de Fechas</th>
                            <th class="py-3 px-4">Rango Horario</th>
                            <th class="py-3 px-4">Razón / Motivo</th>
                            <th class="py-3 px-4 text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 font-bold text-slate-700">
                        @forelse($unavailabilities as $unavailability)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3.5 px-4 text-indigo-600">Dr(a). {{ $unavailability->doctor->user->name }}</td>
                                <td class="py-3.5 px-4 text-slate-500">{{ $unavailability->address->name ?? 'Todas las sedes (Global)' }}</td>
                                <td class="py-3.5 px-4">{{ \Carbon\Carbon::parse($unavailability->start_date)->format('d M Y') }} — {{ \Carbon\Carbon::parse($unavailability->end_date)->format('d M Y') }}</td>
                                <td class="py-3.5 px-4">
                                    @if($unavailability->start_time && $unavailability->end_time)
                                        {{ \Carbon\Carbon::parse($unavailability->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($unavailability->end_time)->format('h:i A') }}
                                    @else
                                        <span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded text-[10px] uppercase">Todo el día</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 italic text-slate-400">{{ $unavailability->reason ?? 'No especificado' }}</td>
                                <td class="py-3.5 px-4 text-center">
                                    <!-- BOTÓN DE ELIMINACIÓN CONECTADO A TU RUTA partner.unavailabilities.destroy -->
                                    <form action="{{ route('partner.unavailabilities.destroy', $unavailability->id) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas liberar la agenda para este rango de fechas?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 focus:outline-none p-1 rounded-lg hover:bg-red-50 inline-flex items-center justify-center">
                                            {{-- Heroicon: Trash --}}
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.34 12m-4.72 0-.34-12M19.25 12l-.38 7.22a.75.75 0 0 1-.74.72H5.87a.75.75 0 0 1-.74-.72L4.75 12M10 5.25h4" /></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400 italic">No tienes bloqueos de agenda configurados ni activos en este momento.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-admin-layout>
