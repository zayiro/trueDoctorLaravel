@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Reagendamiento de Cita']
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">

    <div class="py-8" x-data="{ loading: false }">
        <div class="bg-white rounded-[3rem] shadow-xl overflow-hidden border border-gray-100">
            <!-- Header -->
            <div class="bg-gray-900 p-6 md:p-10">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <!-- Títulos -->
                    <div class="flex-1">
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <p class="text-gray-400 text-sm md:text-base">
                                Paciente: <span class="text-indigo-400 font-bold">{{ $appointment->patient->user->name }}</span>
                            </p>
                            <!-- Badge de ID o info extra sutil -->
                            <span class="text-[10px] bg-white/10 px-2 py-0.5 rounded text-gray-400 border border-white/10">
                                ID: {{ $appointment->patient->identification }}
                            </span>
                        </div>
                    </div>

                    <!-- Info del Servicio (Ya no es absoluta) -->
                    <div class="flex flex-col items-start md:items-end gap-1">
                        <span class="bg-indigo-600 px-4 py-2 rounded-xl text-[10px] md:text-xs font-black text-white uppercase tracking-wider shadow-lg shadow-indigo-900/20">
                            {{ $appointment->service->name }}
                        </span>
                        <span class="text-gray-400 text-[10px] font-bold uppercase tracking-widest px-1">
                            ⏱ {{ $appointment->duration }} Minutos
                        </span>
                    </div>
                </div>
            </div>

            <div class="p-10 pt-1">
                <form id="rescheduleForm" action="{{ route('partner.appointments.reschedule.process', $appointment->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <!-- 1. Selección de Fecha -->
                    <div class="mb-10">
                        <label for="datePicker" class="block text-xs font-black text-gray-400 uppercase tracking-widest mt-4 mb-4">1. Elige la nueva fecha</label>
                        <input type="date" name="date" id="datePicker" value="{{ $appointment->date }}"
                            class="w-full bg-gray-50 border-none rounded-2xl p-5 text-lg font-bold focus:ring-2 focus:ring-indigo-500 transition-all shadow-inner">
                    </div>

                    <!-- 2. Selección de Slots (Dinámico) -->
                    <div class="mb-10">
                        <div class="flex items-center justify-between mt-5 mb-4">
                            <p class="text-xs font-black text-gray-400 uppercase tracking-widest">2. Horarios disponibles</p>
                            <span id="loadingSpinner" class="hidden text-indigo-600 text-xs font-bold animate-pulse italic">Consultando agenda...</span>
                        </div>

                        <!-- Mensaje de Alerta (Oculto por defecto) -->
                        <div id="noScheduleAlert" class="hidden mb-6 p-6 bg-amber-50 border-2 border-dashed border-amber-200 rounded-3xl text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 bg-amber-100 rounded-full mb-3">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <h4 class="text-amber-900 font-black text-sm uppercase tracking-widest">Sin disponibilidad</h4>
                            <p class="text-amber-700 text-sm mt-1 mb-4">No has configurado horarios para este día de la semana en esta sede.</p>
                            
                            <!-- Botón para ir directo a configurar horarios -->
                            <a href="{{ route('partner.schedules.index', ['address' => $appointment->address_id]) }}" 
                                class="inline-flex items-center gap-2 bg-amber-600 text-white px-6 py-2 rounded-xl text-xs font-bold hover:bg-amber-700 transition-all shadow-lg shadow-amber-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Configurar Horarios Ahora
                            </a>
                        </div>
                        
                        <div id="slotsContainer" class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                            <!-- Los slots se cargarán aquí -->
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex flex-col md:flex-row gap-4 pt-6 mt-6 border-t border-gray-50">
                        <button type="submit" id="submitBtn" disabled class="bg-blue-500 hover:bg-blue-700 text-white py-2 px-4 rounded">
                            Confirmar cambio
                        </button>

                        <a href="{{ route('partner.appointments.index', ['date' => $appointment->date]) }}" class="flex-1 py-5 px-8 text-blue-600 hover:text-blue-800 active:text-red-500 underline">
                            Volver a la agenda
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Script de carga dinámica -->
    <script>
        const datePicker = document.getElementById('datePicker');
        const slotsContainer = document.getElementById('slotsContainer');
        const spinner = document.getElementById('loadingSpinner');
        const submitBtn = document.getElementById('submitBtn');
        const addressId = "{{ $appointment->address_id }}";

        async function fetchSlots(date) {
            const alertBox = document.getElementById('noScheduleAlert');
            spinner.classList.remove('hidden');
            slotsContainer.innerHTML = '';
            alertBox.classList.add('hidden'); 
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-30');

            try {
                const isVirtual = "{{ $appointment->service->type === 'virtual' ? 'true' : 'false' }}";
                const addressId = "{{ $appointment->address_id }}";
                const duration = "{{ $appointment->duration }}";
                const excludeId = "{{ $appointment->id }}";

                const url = `{{ route('api.slots.index') }}?date=${date}&duration=${duration}&exclude_id=${excludeId}&is_virtual=${isVirtual}&address_id=${addressId}`;                
                const response = await fetch(url);                
                const slots = await response.json();

                // SI NO HAY HORARIOS CONFIGURADOS
                if (slots.length === 0) {
                    alertBox.classList.remove('hidden');
                    return;
                }

                if (slots.length === 0) {
                    slotsContainer.innerHTML = '<p class="col-span-full text-center py-10 text-gray-400 font-bold italic">No hay horarios para este día.</p>';
                    return;
                }

                slots.forEach(slot => {
                    const slotId = `slot_${slot.time.replace(':', '')}`;
                    const div = document.createElement('div');
                    
                    // Clases base para el botón de hora
                    const baseClasses = "text-center p-4 rounded-2xl font-bold border-2 transition-all duration-200 cursor-pointer";
                    const availableClasses = slot.available 
                        ? "bg-gray-50 text-gray-700 border-gray-100 hover:border-indigo-200" 
                        : "bg-gray-100 text-gray-300 border-transparent cursor-not-allowed opacity-50";

                    div.innerHTML = `
                        <label class="relative block ${slot.available ? 'cursor-pointer' : ''}">
                            <input type="radio" name="hour" value="${slot.time}" class="sr-only" ${!slot.available ? 'disabled' : ''}>
                            <div class="slot-visual ${baseClasses} ${availableClasses}">
                                ${formatTime(slot.time)}
                            </div>
                        </label>
                    `;

                    // Lógica de clic manual para asegurar el cambio visual
                    if (slot.available) {
                        const label = div.querySelector('label');
                        label.addEventListener('click', function() {
                            // 1. Limpiar selección previa en todos los slots
                            document.querySelectorAll('.slot-visual').forEach(el => {
                                el.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-600', 'shadow-lg', 'shadow-indigo-100');
                                el.classList.add('bg-gray-50', 'text-gray-700', 'border-gray-100');
                            });

                            // 2. Aplicar estilo al seleccionado
                            const visual = this.querySelector('.slot-visual');
                            visual.classList.remove('bg-gray-50', 'text-gray-700', 'border-gray-100');
                            visual.classList.add('bg-indigo-600', 'text-white', 'border-indigo-600', 'shadow-lg', 'shadow-indigo-100');

                            // 3. Activar botón de enviar
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('opacity-30');
                        });
                    }

                    slotsContainer.appendChild(div);
                });

            } catch (error) {
                slotsContainer.innerHTML = '<p class="col-span-full text-center py-10 text-red-400 font-bold">Error al cargar horarios.</p>';
            } finally {
                spinner.classList.add('hidden');
            }
        }

        function formatTime(time) {
            const [hours, minutes] = time.split(':');
            const h = hours % 12 || 12;
            const ampm = hours >= 12 ? 'PM' : 'AM';
            return `${h}:${minutes} ${ampm}`;
        }

        datePicker.addEventListener('change', (e) => fetchSlots(e.target.value));
        
        // Carga inicial
        fetchSlots(datePicker.value);
    </script>
</x-admin-layout>

