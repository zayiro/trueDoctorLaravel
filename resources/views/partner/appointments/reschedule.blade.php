@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Agenda Médica', 'href' => route('partner.appointments.index')],
    ['name' => 'Reagendamiento de Cita']
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="py-8 px-4 max-w-4xl mx-auto" x-data="{ loading: false }">
        <div class="bg-white rounded-[3rem] shadow-xl overflow-hidden border border-gray-100">
            
            <!-- HEADER DE SEGUIMIENTO DE LA CITA -->
            <div class="bg-slate-900 p-6 md:p-10">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex-1 space-y-1">
                        <span class="text-[10px] font-black text-indigo-400 uppercase tracking-[0.2em] block">Orden de Reagendamiento</span>
                        <h3 class="text-xl font-bold text-white leading-tight">
                            Paciente: <span class="text-indigo-300">{{ $appointment->patient->user->name }}</span>
                        </h3>
                        
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <span class="text-[10px] bg-white/10 px-2.5 py-1 rounded-md text-gray-300 border border-white/15 font-semibold">
                                CC/ID: {{ $appointment->patient->identification }}
                            </span>
                            
                            <!-- 🔥 EXCLUSIVO CLÍNICAS: Informa sobre qué especialista recae la modificación de agenda -->
                            @if(auth()->user()->role === 'clinic')
                                <span class="text-[10px] bg-indigo-500/20 px-2.5 py-1 rounded-md text-indigo-300 border border-indigo-500/30 font-bold">
                                    Médico: {{ $appointment->doctor->user->name ?? 'Sin asignar' }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Datos Técnicos del Servicio -->
                    <div class="flex flex-col items-start md:items-end gap-1.5">
                        <span class="bg-indigo-600 px-4 py-2 rounded-xl text-[10px] md:text-xs font-black text-white uppercase tracking-widest shadow-lg shadow-indigo-900/40">
                            {{ $appointment->service->name }}
                        </span>
                        <span class="text-slate-400 Lech text-[10px] font-bold uppercase tracking-widest px-1">
                            ⏱ Duración: {{ $appointment->duration }} Minutos
                        </span>
                    </div>
                </div>
            </div>

            <!-- CUERPO DE GESTIÓN OPERATIVA -->
            <div class="p-8 md:p-10">
                <form id="rescheduleForm" action="{{ route('partner.appointments.reschedule.process', $appointment->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <!-- 1. Selección de Fecha -->
                    <div class="mb-10">
                        <label Lifor="datePicker" class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">1. Elige la nueva fecha de consulta</label>
                        <input type="date" name="date" id="datePicker" value="{{ $appointment->date }}" min="{{ now()->toDateString() }}"
                            class="w-full bg-slate-50 border-none rounded-2xl p-5 text-lg font-bold focus:ring-2 focus:ring-indigo-500 transition-all shadow-inner text-slate-700">
                    </div>
                    <!-- 2. Selección de Bloques Horarios (Slots Dinámicos) -->
                    <div class="mb-10">
                        <div class="flex items-center justify-between mt-5 mb-4">
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest">2. Horarios disponibles para el especialista</p>
                            <span id="loadingSpinner" class="hidden text-indigo-600 text-xs font-bold animate-pulse italic">Consultando agenda médica...</span>
                        </div>

                        <!-- Caja de Alerta de Contingencia -->
                        <div id="noScheduleAlert" class="hidden mb-6 p-6 bg-amber-50 border-2 border-dashed border-amber-200 rounded-3xl text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 bg-amber-100 rounded-full mb-3">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <h4 class="text-amber-900 font-black text-sm uppercase tracking-widest">Sin turnos en agenda</h4>
                            <p class="text-amber-700 text-sm mt-1 mb-4">No se han configurado bloques de franjas horarias de atención para este día de la semana en el consultorio.</p>
                            
                            <a href="{{ route('partner.schedules.index', ['address' => $appointment->address_id]) }}" 
                                class="inline-flex items-center gap-2 bg-amber-600 text-white px-6 py-3 rounded-2xl text-xs font-bold hover:bg-amber-700 transition-all shadow-lg shadow-amber-200 uppercase tracking-wider">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Configurar Horarios Sede
                            </a>
                        </div>
                        
                        <!-- Caja contenedora de los Radio Buttons inyectados por JS -->
                        <div id="slotsContainer" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                            <!-- Inyección dinámica de franjas libres -->
                        </div>
                    </div>

                    <!-- Botones Inferiores de Acción -->
                    <div class="flex flex-col sm:flex-row gap-4 pt-6 mt-6 border-t border-slate-50 items-center justify-between">
                        <a href="{{ route('partner.appointments.index', ['date' => $appointment->date]) }}" class="text-sm font-bold text-slate-500 hover:text-indigo-600 transition-colors order-2 sm:order-1">
                            ← Volver a la agenda principal
                        </a>

                        <button type="submit" id="submitBtn" disabled class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white py-4 px-8 rounded-2xl shadow-lg font-black uppercase tracking-wider text-xs transition-all disabled:opacity-30 disabled:cursor-not-allowed order-1 sm:order-2">
                            Confirmar Cambio de Cita
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- SCRIPT DE CARGA DINÁMICA DE SLOTS POR API -->
    <!-- ======================================================== -->
    <script>
        const datePicker = document.getElementById('datePicker');
        const slotsContainer = document.getElementById('slotsContainer');
        const spinner = document.getElementById('loadingSpinner');
        const submitBtn = document.getElementById('submitBtn');

        async function fetchSlots(date) {
            const alertBox = document.getElementById('noScheduleAlert');
            spinner.classList.remove('hidden');
            slotsContainer.innerHTML = '';
            alertBox.classList.add('hidden'); 
            submitBtn.disabled = true;

            try {
                const isVirtual = "{{ $appointment->service->type === 'virtual' ? 'true' : 'false' }}";
                const addressId = "{{ $appointment->address_id }}";
                const duration = "{{ $appointment->duration }}";
                const excludeId = "{{ $appointment->id }}";
                // 🔥 SOLUCIÓN SAAS: Inyectamos de forma obligatoria el ID del doctor dueño de la cita
                const doctorId = "{{ $appointment->doctor_id }}"; 

                // Construcción de la URL de consulta hacia la API del motor de disponibilidad
                const url = `{{ route('api.slots.index') }}?date=${date}&duration=${duration}&exclude_id=${excludeId}&is_virtual=${isVirtual}&address_id=${addressId}&doctor_id=${doctorId}`;                
                
                const response = await fetch(url);                
                const slots = await response.json();

                spinner.classList.add('hidden');

                if (!slots || slots.length === 0) {
                    alertBox.classList.remove('hidden');
                    return;
                }

                // Dibujar e inyectar cada slot disponible en la pantalla
                slots.forEach((slot, index) => {
                    const slotTimeClean = slot.time.substring(0, 5); // Recorta a HH:MM
                    const div = document.createElement('div');
                    
                    div.innerHTML = `
                        <label class="cursor-pointer block select-none">
                            <input type="radio" name="hour" value="${slot.time}" onchange="document.getElementById('submitBtn').disabled = false" class="sr-only peer">
                            <div class="p-3.5 text-center bg-white border border-slate-200 rounded-xl font-bold text-sm text-slate-700 hover:bg-slate-50 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 transition-all shadow-sm">
                                ${slotTimeClean}
                            </div>
                        </label>
                    `;
                    slotsContainer.appendChild(div);
                });

            } catch (error) {
                console.error("Error al procesar la API de slots:", error);
                spinner.classList.add('hidden');
                slotsContainer.innerHTML = '<p class="col-span-full text-center py-6 text-red-500 font-bold text-xs bg-red-50 rounded-2xl border border-red-100">Ocurrió un error de conexión al consultar el calendario.</p>';
            }
        }

        // Listener para detectar cambios en el selector de fecha del calendario
        datePicker.addEventListener('change', (e) => fetchSlots(e.target.value));
        
        // Disparo automático de carga inicial al abrir la pantalla
        document.addEventListener('DOMContentLoaded', () => fetchSlots(datePicker.value));
    </script>
</x-admin-layout>
