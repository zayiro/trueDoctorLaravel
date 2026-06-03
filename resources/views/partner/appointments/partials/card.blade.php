<div x-data="{ 
        openReschedule: false, 
        selectedDate: '', 
        slots: [], 
        loadingSlots: false,
        fetchSlots() {
            if (!this.selectedDate) return;
            this.loadingSlots = true;
            this.slots = [];
            
            // Consumimos tu endpoint unificado /slots inyectando los datos de la cita actual
            fetch(`/slots?date=${this.selectedDate}&doctor_id={{ $app->doctor_id }}&address_id={{ $app->address_id }}`)
                .then(res => res.json())
                .then(data => {
                    this.slots = Array.isArray(data) ? data : (data.slots || Object.values(data));
                    this.loadingSlots = false;
                })
                .catch(err => {
                    console.error('Error cargando agendas médicos móvil:', err);
                    this.loadingSlots = false;
                });
        }
     }" 
     class="p-5 bg-white space-y-3 relative">
    
    <!-- Fila 1: Hora, Duración y Estado -->
    <div class="flex items-center justify-between gap-4">
        <div>
            <span class="text-sm font-black text-slate-800">
                {{ \Carbon\Carbon::parse($app->start_time)->format('g:i A') }}
            </span>
            <span class="text-xs font-bold text-slate-400 ml-1">
                ({{ $app->duration }} min)
            </span>
        </div>
        
        <!-- Badge de Estado -->
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-black border uppercase tracking-wider
            @if($app->status === 'confirmed') bg-green-50 text-green-700 border-green-200
            @elseif($app->status === 'pending') bg-amber-50 text-amber-700 border-amber-200
            @elseif($app->status === 'completed') bg-gray-50 text-gray-700 border-gray-200
            @else bg-red-50 text-red-700 border-red-200 @endif">
            {{ $app->status === 'confirmed' ? 'Confirmada' : ($app->status === 'pending' ? 'Pendiente' : ($app->status === 'completed' ? 'Completada' : 'Cancelada')) }}
        </span>
    </div>

    <!-- Fila 2: Información Médica y Paciente -->
    <div class="space-y-1">
        <div class="text-xs font-bold text-slate-400 font-mono">Ref: {{ $app->reference }}</div>
        <h4 class="text-base font-black text-slate-900">{{ $app->patient->user->name ?? 'Paciente' }}</h4>
        <p class="text-xs font-black text-indigo-600">{{ $app->service->name }}</p>
    </div>

    <!-- Fila 3: Canal y Sede -->
    <div class="flex flex-wrap items-center gap-2 pt-1">
        @if($app->service && $app->service->type === 'virtual')
            <span class="bg-purple-50 text-purple-700 text-[9px] font-black px-2 py-0.5 rounded-lg border border-purple-200 uppercase tracking-wider">💻 Telemedicina</span>
        @else
            <span class="bg-emerald-50 text-emerald-700 text-[9px] font-black px-2 py-0.5 rounded-lg border border-emerald-200 uppercase tracking-wider">📍 Presencial</span>
        @endif
    </div>
    <!-- Fila 4: Botonera de Acción Táctil -->
    <div class="grid grid-cols-2 gap-2 pt-3 border-t border-slate-100">
        <!-- Ver Notas (Llama a tu función JavaScript global) -->
        <button type="button" @click="openNoteModal('{{ addslashes($app->notes) }}')" 
                class="inline-flex justify-center items-center text-center text-xs font-bold text-amber-600 bg-amber-50 hover:bg-amber-100 py-2.5 rounded-xl transition">
            👁️ Notas
        </button>

        <!-- Botón Reagendar Móvil -->
        @if(in_array($app->status, ['pending', 'confirmed']))
            <button type="button" @click="openReschedule = true" 
                    class="inline-flex justify-center items-center text-center text-xs font-black text-indigo-600 bg-indigo-50 hover:bg-indigo-100 py-2.5 rounded-xl transition uppercase tracking-wider">
                🔄 Reagendar
            </button>
        @else
            <div class="h-10"></div>
        @endif

        <!-- BOTÓN CANCELAR CITA MÓVIL -->
        @if(in_array($app->status, ['pending', 'confirmed']))
            <form action="{{ route('partner.appointments.update-status', $app->id) }}" method="POST" 
                  onsubmit="return confirm('¿Estás seguro de que deseas cancelar esta consulta médica?');" 
                  class="flex-1 min-w-[100px]">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="cancelled">
                
                <button type="submit" 
                        class="w-full inline-flex justify-center items-center text-center text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 py-2.5 rounded-xl transition uppercase tracking-wider">
                    ❌ Cancelar
                </button>
            </form>
        @endif
    </div>

    <!-- 👇 MODAL INTERACTIVO DE REAGENDAMIENTO MÓVIL (Aislado para esta Tarjeta) -->
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

            <!-- Formulario -->
            <form action="{{ route('partner.appointments.reschedule.process', $app->id) }}" method="POST" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <p class="text-xs text-slate-600 leading-relaxed font-medium">
                    Cambio de horario para el paciente <span class="font-bold text-slate-900">{{ $app->patient->user->name }}</span>.
                </p>

                <!-- Input 1: Fecha -->
                <div>
                    <label class="block text-[10px] font-black text-slate-700 uppercase tracking-wider mb-1">Nueva Fecha</label>
                    <input type="date" name="new_date" min="{{ date('Y-m-d') }}" x-model="selectedDate" @change="fetchSlots()" required
                           class="w-full text-xs font-bold text-slate-700 border-slate-200 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5 px-3 bg-white">
                </div>

                <!-- Input 2: Slots -->
                <div>
                    <label class="block text-[10px] font-black text-slate-700 uppercase tracking-wider mb-1">Turnos Libres</label>
                    
                    <div x-show="loadingSlots" class="text-xs font-bold text-indigo-600 py-2.5 flex items-center gap-2" x-cloak>
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Consultando matriz...
                    </div>

                    <select name="new_start_time" x-show="!loadingSlots && slots.length > 0" required
                            class="w-full text-xs font-bold text-slate-700 border-slate-200 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5 px-3 bg-white">
                        <option value="">Selecciona una opción...</option>
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
                        ⚠️ Agenda completa o sin turnos para este día.
                    </div>
                </div>

                <!-- Botones de Control -->
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="openReschedule = false; selectedDate = ''; slots = []" 
                            class="px-3 py-2 text-xs font-black text-slate-500 bg-slate-50 hover:bg-slate-100 rounded-xl transition tracking-wider uppercase">
                        Cancelar
                    </button>
                    <button type="submit" x-bind:disabled="slots.length === 0"
                            class="px-4 py-2 text-xs font-black text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition shadow-sm tracking-wider uppercase disabled:opacity-50 disabled:cursor-not-allowed">
                        Confirmar Cambio
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
