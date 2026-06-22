<!-- CAMBIAR EL x-data PRINCIPAL -->
<div x-data="{ 
    openReschedule: false,
    actionsOpen: false,  <!-- ✅ AGREGAR ESTO -->
    selectedDate: '', 
    slots: [], 
    loadingSlots: false,
    fetchSlots() {
        if (!this.selectedDate) return;
        this.loadingSlots = true;
        this.slots = [];
        
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
                ({{ $app->service->duration ?? $app->address->pivot->duration ?? 20 }} min)
            </span>
        </div>
        
        <!-- Badge de Estado -->
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-black border uppercase tracking-wider
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
    </div>

    <!-- Fila 2: Información Médica y Paciente -->
    <div class="space-y-1">
        <div class="text-xs font-bold text-slate-400 font-mono">Ref: {{ $app->reference }}</div>
        <h4 class="text-base font-black text-slate-900">{{ $app->patient->user->name ?? 'Paciente' }}</h4>
        <p class="text-xs font-black text-slate-600">{{ $app->service->name }}</p>
        
        <!-- ⚡ INDICADOR STAFF MÓVIL: Visible si audita la clínica -->
        @if(auth()->user()->role === 'clinic')
            <span class="inline-flex items-center gap-1 text-[9px] font-black text-indigo-600 uppercase tracking-wider mt-1 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-100/60">
                Dr(a). {{ $app->doctor->user->name ?? 'Sin asignar' }}
            </span>
        @endif
    </div>

    <!-- Fila 3: Canal y Sede -->
    <div class="flex flex-wrap items-center gap-2 pt-1">
        @if($app->address && $app->address->type === 'virtual')
            <span class="bg-purple-50 text-purple-700 text-[9px] font-black px-2 py-0.5 rounded-lg border border-purple-200 uppercase tracking-wider">💻 Telemedicina</span>
        @else
            <span class="bg-blue-50 text-blue-700 text-[9px] font-black px-2 py-0.5 rounded-lg border border-blue-200 uppercase tracking-wider">📍 Presencial</span>
        @endif        
    </div>

    <!-- Fila 4: Botonera de Acción -->
    <div class="pt-3 border-t border-slate-100">
        
        <!-- Botón Principal: Iniciar Consulta -->
        <div class="mb-3">
            @if($app->address && $app->address->type === 'virtual' && $app->status_label === 'confirmed' && $app->zoom_start_url)
                <a href="{{ $app->zoom_start_url }}" target="_blank" 
                   class="w-full block text-center text-xs font-black uppercase tracking-wider px-3 py-2.5 rounded-xl border shadow-sm transition
                   {{ (auth()->user()->role === 'doctor' && (session('doctor_context')['type'] ?? 'particular') === 'clinic') 
                       ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' 
                       : 'bg-purple-50 text-purple-700 border-purple-200 hover:bg-purple-100' }}">
                    🚀 Iniciar Consulta
                </a>
            @else
                <a href="{{ route('partner.patients.show', [$app->patient_id, $app->reference]) }}" target="_blank" 
                   class="w-full block text-center text-xs font-black uppercase tracking-wider px-3 py-2.5 rounded-xl border shadow-sm transition
                   {{ (auth()->user()->role === 'doctor' && (session('doctor_context')['type'] ?? 'particular') === 'clinic') 
                       ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' 
                       : 'bg-purple-50 text-purple-700 border-purple-200 hover:bg-purple-100' }}">
                    📋 Iniciar Consulta
                </a>
            @endif
        </div>

        <!-- Botón Más Acciones -->
        <button @click="actionsOpen = true" 
                class="w-full inline-flex justify-center items-center gap-2 text-xs font-black uppercase tracking-wider px-3 py-2 rounded-xl border shadow-sm transition
                {{ (auth()->user()->role === 'doctor' && (session('doctor_context')['type'] ?? 'particular') === 'clinic') 
                   ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' 
                   : 'bg-indigo-50 text-indigo-700 border-indigo-200 hover:bg-indigo-100' }}">
            ⚙️ Más Acciones
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
            </svg>
        </button>
    </div>

    <!-- MODAL FLOTANTE DE ACCIONES (Igual que reagendamiento) -->
    <div x-show="actionsOpen" 
         @click.self="actionsOpen = false"
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak>
        
        <div @click.stop class="bg-white w-full sm:max-w-sm p-4 rounded-t-2xl sm:rounded-2xl shadow-xl border border-slate-100 text-left">
            
            <!-- Cabecera -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Más Acciones</h3>
                <button @click="actionsOpen = false" class="text-slate-400 hover:text-slate-600 text-2xl font-bold">&times;</button>
            </div>

            <!-- Acciones -->
            <div class="space-y-2">
                <!-- Ver Notas -->
                @if($app->notes)
                    <button type="button" @click="openNoteModal('{{ addslashes($app->notes) }}'); actionsOpen = false" 
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-bold text-amber-600 hover:bg-amber-50 rounded-lg transition text-left">
                        👁️ Ver Notas
                    </button>
                @endif

                <!-- Completar -->
                @if(in_array($app->status_label, ['pending', 'confirmed']))
                    <form action="{{ route('partner.appointments.complete', $app->id) }}" method="POST" class="w-full">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-bold text-green-600 hover:bg-green-50 rounded-lg transition text-left">
                            ✓ Marcar Atendida
                        </button>
                    </form>
                @endif

                <!-- Reagendar -->
                @if(in_array($app->status_label, ['pending', 'confirmed']))
                    <button type="button" @click="openReschedule = true; actionsOpen = false" 
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-bold text-indigo-600 hover:bg-indigo-50 rounded-lg transition text-left">
                        🔄 Reagendar
                    </button>
                @endif

                <!-- Cancelar -->
                @if(in_array($app->status_label, ['pending', 'confirmed']))
                    <form action="{{ route('partner.appointments.cancel', $app->id) }}" method="POST" 
                          onsubmit="return confirm('¿Estás seguro de que deseas cancelar esta consulta médica?');" 
                          class="w-full">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-bold text-red-600 hover:bg-red-50 rounded-lg transition text-left border-t border-slate-100 mt-1 pt-2">
                            ❌ Cancelar Cita
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- MODAL INTERACTIVO DE REAGENDAMIENTO (Sigue igual) -->
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
        
        <!-- ... resto del modal de reagendamiento igual ... -->
    </div>
</div>