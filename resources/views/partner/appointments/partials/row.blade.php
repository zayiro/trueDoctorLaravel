<tr x-data="{ 
        openReschedule: false, 
        selectedDate: '', 
        slots: [], 
        loadingSlots: false,
        fetchSlots() {
            if (!this.selectedDate) return;
            this.loadingSlots = true;
            this.slots = [];
            
            // Evaluamos contextualmente si viaja un clinic_id en la sesión para inyectarlo en el endpoint
            @php
                $context = session('doctor_context');
                $clinicParam = (($context['type'] ?? 'particular') === 'clinic') ? '&clinic_id=' . $context['id'] : '';
            @endphp
            
            fetch(`/slots?date=${this.selectedDate}&doctor_id={{ $app->doctor_id }}&address_id={{ $app->address_id }}{{ $clinicParam }}`)
                .then(res => res.json())
                .then(data => {
                    this.slots = Array.isArray(data) ? data : (data.slots || Object.values(data));
                    this.loadingSlots = false;
                })
                .catch(err => {
                    console.error('Error cargando agendas médicos:', err);
                    this.loadingSlots = false;
                });
        }
    }" 
    class="hover:bg-slate-50/80 transition-colors relative">
    
    <!-- Columna 1: Hora de la Cita -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-xs font-medium text-slate-500 font-mono mt-0.5 mb-2">Ref: {{ $app->reference }}</div>
        <div class="text-sm font-black text-slate-800">
            <div>{{ ucfirst(\Carbon\Carbon::parse($app->date)->locale('es')->isoFormat('dddd, D [de] MMMM')) }}</div>
            {{ \Carbon\Carbon::parse($app->start_time)->format('g:i A') }}
        </div>
        <div class="text-[11px] font-bold text-slate-400 mt-0.5">
            {{ $app->service->duration ?? $app->address->pivot->duration ?? 20 }} min
        </div>
    </td>
    <!-- Columna 2: Información del Paciente -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="flex flex-col">
            <div class="text-sm font-black text-slate-900">{{ $app->patient->user->name ?? 'Paciente' }}</div>            
            <div class="mt-2">
                @php
                    $cleanPatientPhone = preg_replace('/[^0-9]/', '', $app->patient->phone);
                    $gender = $app->doctor->gender === 'female' ? 'la doctora' : 'el doctor';

                    $whatsappMessage = "Hola " . $app->patient->user->name . ". Le saluda " . $gender . " " . auth()->user()->name . ". Lo contacto por su cita médica con Referencia: " . $app->reference . ".";
                    $whatsappUrl = "https://wa.me/" . $cleanPatientPhone . "?text=" . urlencode($whatsappMessage);
                @endphp

                <a href="{{ $whatsappUrl }}" 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    class="inline-flex items-center gap-2 px-4 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                    <!-- Icono estilo Heroicons (SVG Nativo) -->
                    <svg xmlns="http://w3.org" 
                        fill="currentColor" 
                        viewBox="0 0 24 24" 
                        class="w-5 h-5">
                        <path d="M12.004 2c-5.51 0-9.993 4.483-9.993 9.993 0 1.763.461 3.42 1.262 4.873L2 22l5.304-1.392a9.922 9.922 0 0 0 4.699 1.183c5.51 0 9.994-4.483 9.994-9.993C21.997 6.483 17.514 2 12.004 2zm5.221 14.195c-.227.64-.1.115-.902.937-.738.756-1.688.855-2.853.336-2.585-1.15-4.417-3.618-5.385-4.935-.37-.503-1.026-1.511-.968-2.316.05-.688.423-1.011.664-1.242.215-.207.48-.3.69-.3.21 0 .42.01.6.1.25.13.56.66.68.91.13.27.14.57.02.82-.12.25-.26.4-.41.58-.15.17-.32.36-.14.68.39.69.96 1.34 1.63 1.9 1.11.93 2.02 1.36 2.65 1.55.45.13.84.03 1.13-.24.33-.3.99-.95 1.22-1.32.22-.36.5-.28.82-.16.32.12 2.05 1.01 2.14 1.06.1.05.23.11.28.2.09.16.03.74-.2 1.37z"/>
                    </svg>

                    <span>Hablar con el paciente</span>
                </a>
            </div>

            <div x-data="{ open: false }" class="mt-2">

                <!-- Botón para abrir el Popup -->
                <div @click="open = true" class="text-blue-600 hover:text-blue-800 underline-offset-4 hover:underline font-medium transition-colors">
                    Datos del paciente
                </div>

                <!-- Fondo oscuro (Overlay) y Contenedor del Popup -->
                <div x-show="open" 
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
                    x-cloak>
                    
                    <!-- Caja del Popup -->
                    <div @click.away="open = false" 
                        x-show="open"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="w-full max-w-md bg-white rounded-xl shadow-xl overflow-hidden transform transition-all">
                        
                        <!-- Encabezado -->
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900">Datos del paciente</h3>
                            <button @click="open = false" class="text-gray-400 hover:text-gray-600 text-2xl font-semibold">&times;</button>
                        </div>

                        <!-- Contenido -->
                        <div class="p-6 space-y-3">
                            <p class="text-sm text-gray-600"><strong>Paciente:</strong> {{ $app->patient->user->name }} ({{ $app->patient->blood_type }})</p>
                            <p class="text-sm text-gray-600"><strong>Fecha de nacimiento:</strong> {{ explode(" ", $app->patient->birth_date)[0] }}</p>
                            <p class="text-sm text-gray-600"><strong>Altura/peso:</strong> {{ $app->patient->height }} / {{ $app->patient->weight }}</p>
                            <p class="text-sm text-gray-600"><strong>Teléfono:</strong> {{ $app->patient->phone }}</p>
                            <p class="text-sm text-gray-600"><strong>Contacto:</strong> {{ $app->patient->emergency_contact_name }} ({{ $app->patient->emergency_contact_phone }})</p>                            
                            <p class="text-sm text-gray-600"><strong>Relación:</strong> {{ $app->patient->emergency_contact_relationship }}</p>                            
                        </div>

                        <!-- Botones de Acción -->
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end space-x-3">
                            <button @click="open = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ⚡ INDICADOR STAFF: Visible si la clínica audita la nómina -->
            @if(auth()->user()->role === 'clinic')
                <span class="inline-flex items-center gap-1 text-[10px] font-black text-indigo-600 uppercase tracking-wider mt-1.5 bg-indigo-50 px-2 py-0.5 rounded-lg w-max border border-indigo-100/60">
                    Dr(a). {{ $app->doctor->user->name ?? 'Sin asignar' }}
                </span>
            @endif
        </div>
    </td>

    <!-- Columna 3: Servicio y Canal -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm font-bold {{ (auth()->user()->role === 'doctor' && (session('doctor_context')['type'] ?? 'particular') === 'clinic') ? 'text-emerald-700' : 'text-indigo-600' }}">{{ $app->service->name }}</div>
        <div class="mt-1">
            @if($app->address && $app->address->type === 'virtual')
                <span class="bg-purple-50 text-purple-700 text-[9px] font-black px-2 py-0.5 rounded-lg border border-purple-200 uppercase tracking-wider">💻 Telemedicina</span>
            @else
                <span class="bg-blue-50 text-blue-700 text-[9px] font-black px-2 py-0.5 rounded-lg border border-blue-200 uppercase tracking-wider">📍 Presencial</span>
            @endif
        </div>
    </td>
    <!-- Columna 4: Estado de la Cita -->
    <td class="px-6 py-4 whitespace-nowrap">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black border uppercase tracking-wider
            @if($app->status_label === 'confirmed') bg-green-50 text-green-700 border-green-200
            @elseif($app->status_label === 'pending') bg-amber-50 text-amber-700 border-amber-200
            @elseif($app->status_label === 'completed') bg-blue-50 text-blue-700 border-blue-200
            @else bg-rose-50 text-rose-700 border-rose-200 @endif">
            @switch($app->status_label)
                @case('confirmed') Confirmada @break
                @case('pending') Pendiente @break
                @case('completed') Atendida @break
                @case('cancelled') Cancelada @break
                @default {{ $app->status_label }}
            @endswitch
        </span>
        <div>
            <!-- Ver Notas -->
            @if($app->notes)
                <button type="button" @click="openNoteModal('{{ addslashes($app->notes) }}'); actionsOpen = false" 
                        class="flex items-center gap-3 px-3 py-2.5 text-sm font-bold text-amber-600 hover:bg-amber-50 rounded-lg transition w-full text-left">
                    👁️ Ver Notas
                </button>
            @endif
        </div>
    </td>

    <!-- Columna 5: Acciones Administrativas del Médico / Clínica -->
    <td class="px-6 py-4 whitespace-nowrap text-right">
        
        <div x-data="{ actionsOpen: false }" class="relative inline-block">
            <!-- Botón Principal -->
            <button @click="actionsOpen = !actionsOpen" 
                    class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-wider px-3 py-2 rounded-xl border shadow-sm transition
                    {{ (auth()->user()->role === 'doctor' && (session('doctor_context')['type'] ?? 'particular') === 'clinic') 
                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' 
                    : 'bg-purple-50 text-purple-700 border-purple-200 hover:bg-purple-100' }}">
                ⚙️ Acciones
                <svg :class="{'rotate-180': actionsOpen}" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>
            </button>

            <!-- Menú Desplegable con Teleport -->
            <div x-teleport="body"
                x-show="actionsOpen" 
                @click.away="actionsOpen = false"
                class="fixed right-0 mt-1 w-52 bg-white border border-slate-200 rounded-xl shadow-lg z-50 overflow-hidden"
                x-transition
                x-cloak>
                
                <div class="p-1">
                    <!-- 🌐 INICIAR CONSULTA (Principal) -->
                    @if($app->address && $app->address->type === 'virtual' && $app->status_label === 'confirmed' && $app->zoom_start_url)
                        <a href="{{ $app->zoom_start_url }}" target="_blank" 
                        class="flex items-center gap-3 px-3 py-2.5 text-sm font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition w-full text-left">
                            💻 Iniciar Consulta
                        </a>
                    @else
                        <a href="{{ route('partner.patients.show', [$app->patient_id, $app->reference]) }}" target="_blank" 
                        class="flex items-center gap-3 px-3 py-2.5 text-sm font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition w-full text-left">
                            📋 Iniciar Consulta
                        </a>
                    @endif
                    
                    <!-- Completar -->
                    @if(in_array($app->status_label, ['pending', 'confirmed']))
                        <form action="{{ route('partner.appointments.complete', $app->id) }}" method="POST" class="w-full">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="flex items-center gap-3 px-3 py-2.5 text-sm font-bold text-green-600 hover:bg-green-50 rounded-lg transition w-full text-left">
                                ✓ Marcar Completada
                            </button>
                        </form>
                    @endif

                    <!-- Reagendar -->
                    @if(in_array($app->status_label, ['pending', 'confirmed']))
                        <button type="button" @click="openReschedule = true; actionsOpen = false" 
                                class="flex items-center gap-3 px-3 py-2.5 text-sm font-bold text-indigo-600 hover:bg-indigo-50 rounded-lg transition w-full text-left">
                            🔄 Reagendar
                        </button>
                    @endif

                    <!-- Cancelar -->
                    @if(in_array($app->status_label, ['pending', 'confirmed']))
                        <form action="{{ route('partner.appointments.cancel', $app->id) }}" method="POST" 
                            onsubmit="return confirm('¿Estás seguro de que deseas cancelar esta consulta médica de forma definitiva? Se notificará al paciente.');" 
                            class="w-full">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="flex items-center gap-3 px-3 py-2.5 text-sm font-bold text-red-600 hover:bg-red-50 rounded-lg transition w-full text-left border-t border-slate-100 mt-1 pt-2">
                                ❌ Cancelar Cita
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- MODAL INTERACTIVO DE REAGENDAMIENTO (Sigue siendo lo mismo) -->
        <div x-show="openReschedule" 
            @click.self="openReschedule = false; selectedDate = ''; slots = []"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-cloak>
            
            <div @click.stop class="bg-white w-full max-w-md p-6 rounded-2xl shadow-xl border border-slate-100 mx-4 text-left whitespace-normal">
                <!-- Cabecera -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Reprogramación Médica</h3>
                    <button type="button" @click="openReschedule = false; selectedDate = ''; slots = []" class="text-slate-400 hover:text-slate-600 transition font-bold text-lg">&times;</button>
                </div>

                <form action="{{ route('partner.appointments.reschedule.process', $app->id) }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <p class="text-xs text-slate-600 leading-relaxed font-medium">
                        Forzar cambio de horario para el paciente <span class="font-bold text-slate-900">{{ $app->patient->user->name }}</span>.
                    </p>

                    <!-- Input 1: Fecha -->
                    <div>
                        <label class="block text-[10px] font-black text-slate-700 uppercase tracking-wider mb-1">Nueva Fecha</label>
                        <input type="date" name="new_date" min="{{ date('Y-m-d') }}" x-model="selectedDate" @change="fetchSlots()" required
                            class="w-full text-xs font-bold text-slate-700 border-slate-200 rounded-xl shadow-sm py-2.5 px-3 bg-white focus:ring-2 {{ (auth()->user()->role === 'doctor' && (session('doctor_context')['type'] ?? 'particular') === 'clinic') ? 'focus:border-emerald-500 focus:ring-emerald-500' : 'focus:border-indigo-500 focus:ring-indigo-500' }}">
                    </div>
                    
                    <!-- Input 2: Slots calculados -->
                    <div>
                        <label class="block text-[10px] font-black text-slate-700 uppercase tracking-wider mb-1">Turnos Libres</label>
                        
                        <div x-show="loadingSlots" class="text-xs font-bold py-2.5 flex items-center gap-2 {{ (auth()->user()->role === 'doctor' && (session('doctor_context')['type'] ?? 'particular') === 'clinic') ? 'text-emerald-600' : 'text-indigo-600' }}" x-cloak>
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Consultando matriz horaria...
                        </div>

                        <select name="new_start_time" x-show="!loadingSlots && slots.length > 0" required
                                class="w-full text-xs font-bold text-slate-700 border-slate-200 rounded-xl shadow-sm py-2.5 px-3 bg-white focus:ring-2 {{ (auth()->user()->role === 'doctor' && (session('doctor_context')['type'] ?? 'particular') === 'clinic') ? 'focus:border-emerald-500 focus:ring-emerald-500' : 'focus:border-indigo-500 focus:ring-indigo-500' }}">
                            <option value="">Selecciona una opción libre...</option>
                            <template x-for="slot in slots" :key="slot.time">
                                <option :value="(() => {
                                            let [time, modifier] = slot.time.split(' ');
                                            let [hours, minutes] = time.split(':');
                                            if (hours === '12') hours = '00';
                                            if (modifier === 'PM') hours = parseInt(hours, 10) + 12;
                                            return `${hours.toString().padStart(2, '0')}:${minutes}:00`;
                                        })()" x-text="slot.time"></option>
                            </template>
                        </select>

                        <div x-show="!loadingSlots && selectedDate && slots.length === 0" class="text-xs font-bold text-red-600 bg-red-50 border border-red-100 p-2.5 rounded-xl" x-cloak>
                            ⚠️ Agenda completa o sin turnos disponibles para este día.
                        </div>
                    </div>

                    <!-- Botones de Control -->
                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="openReschedule = false; selectedDate = ''; slots = []" 
                                class="px-3 py-2 text-xs font-black text-slate-500 bg-slate-50 hover:bg-slate-100 rounded-xl transition tracking-wider uppercase">
                            Cancelar
                        </button>
                        <button type="submit" x-bind:disabled="slots.length === 0"
                                class="px-4 py-2 text-xs font-black text-white rounded-xl transition shadow-sm tracking-wider uppercase disabled:opacity-50 disabled:cursor-not-allowed
                                {{ (auth()->user()->role === 'doctor' && (session('doctor_context')['type'] ?? 'particular') === 'clinic') ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-indigo-600 hover:bg-indigo-700' }}">
                            Confirmar Cambio
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </td>
</tr>
