<div {{ $attributes->merge(['class' => 'w-full']) }} 
     x-data="{ 
        reference: '', 
        appointment: null, 
        errorMessage: '', 
        loading: false,
        openModal: false,
        
        executeSearch() {
            if (!this.reference) return;
            this.loading = true;
            this.errorMessage = '';
            this.appointment = null;
            
            // Consumimos tu endpoint con el nombre exacto del método
            fetch(`/appointments/search-reference?reference=${this.reference.toUpperCase().trim()}`)
                .then(async res => {
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error || 'Error en la búsqueda');
                    return data;
                })
                .then(data => {
                    this.appointment = data;
                    this.openModal = true;
                    this.loading = false;
                })
                .catch(err => {
                    this.errorMessage = err.message;
                    this.loading = false;
                });
        }
     }">
     
    <!-- Formulario del Buscador (Modificado para no recargar la página) -->
    <form @submit.prevent="executeSearch()" class="relative w-full">
        <label for="reference" class="text-[10px] font-black text-indigo-600 uppercase tracking-[0.2em] mb-2 block">
            Buscador de Citas Global
        </label>
        
        <div class="relative flex items-center bg-white border border-slate-200 rounded-2xl shadow-sm w-full focus-within:border-indigo-500 focus-within:ring-4 focus-within:ring-indigo-100/50 transition-all duration-300">
            <div class="pl-4 text-slate-400 pointer-events-none shrink-0">
                <!-- Icono de Lupa cargando o decorativo -->
                <template x-if="loading">
                    <svg class="animate-spin h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </template>
                <template x-if="!loading">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </template>
            </div>
            
            <input 
                type="text" 
                x-model="reference"
                id="reference"
                placeholder="Ingresa la referencia (Ej: 26060121-YHK)" 
                class="w-full flex-1 bg-transparent border-0 py-5 pl-3 pr-28 text-sm font-semibold text-slate-800 uppercase placeholder-slate-400 focus:ring-0 focus:outline-none tracking-wider"
                required
            >
            
            <div class="absolute right-2 top-3 bottom-2 flex items-center">
                <button type="submit" :disabled="loading" class="h-full bg-indigo-600 p-3 text-white hover:text-black rounded-xl text-xs font-black uppercase tracking-wider hover:bg-indigo-300 active:scale-95 shadow-md flex items-center gap-1.5 transition-all duration-200 whitespace-nowrap shrink-0 disabled:opacity-50">
                    <span>Buscar</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </button>
            </div>
        </div>
    </form>

    <!-- Renderizado reactivo de errores en el SaaS -->
    <div x-show="errorMessage" x-cloak class="mt-3 flex items-center gap-2 text-xs font-bold text-red-600 bg-red-50 border border-red-100/80 px-4 py-3 rounded-xl">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <span x-text="errorMessage"></span>
    </div>

    <!-- 👇 MODAL DE LECTURA PURA GLOBAL (CONFIRMACIÓN INFORMATIVA) -->
    <div x-show="openModal" 
         @click.self="openModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak>
        
        <div class="bg-white w-full max-w-md p-6 rounded-[2rem] shadow-2xl border border-slate-100 mx-4 text-left relative overflow-hidden">
            <div class="h-2 w-full bg-indigo-600 absolute top-0 left-0 right-0"></div>
            
            <!-- Cabecera Informativa -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mt-2">
                <div>
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Información de Consulta</h3>
                    <p class="text-[10px] font-mono text-indigo-600 font-bold" x-text="'REF: ' + appointment?.reference"></p>
                </div>
                <button type="button" @click="openModal = false" class="text-slate-400 hover:text-slate-600 text-2xl font-bold p-1">&times;</button>
            </div>

            <!-- Ficha Médica de Lectura limpia -->
            <template x-if="appointment">
                <div class="mt-4 space-y-3.5 text-xs font-bold text-slate-700">
                    
                    <!-- Tarjeta de Paciente (Existente) -->
                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100 flex justify-between items-center mb-1">
                        <div>
                            <span class="block text-[10px] text-slate-400 uppercase tracking-wider font-black mb-0.5">Paciente</span>
                            <span class="text-sm text-slate-900 font-black" x-text="appointment.patient"></span>
                        </div>
                        <span class="text-[9px] font-black px-2 py-0.5 rounded-lg border uppercase tracking-wider"
                              :class="appointment.type === 'virtual' ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                              x-text="appointment.type === 'virtual' ? '💻 Virtual' : '📍 Presencial'"></span>
                    </div>

                    <!-- 👇 AQUÍ ENCAJA EL NUEVO BLOQUE: Profesional Asignado -->
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 mb-1">
                        <span class="block text-[9px] text-slate-400 uppercase tracking-wider font-black mb-0.5">Profesional asignado</span>
                        <p class="text-slate-800 font-black" x-text="'Dr. ' + appointment.doctor"></p>
                    </div>

                    <!-- Grilla de Fecha y Horario (Existente) -->
                    <div class="grid grid-cols-2 gap-3 mb-1">
                        <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                            <span class="block text-[9px] text-slate-400 uppercase tracking-wider font-black mb-0.5">Fecha</span>
                            <span class="text-slate-800 font-black" x-text="appointment.date"></span>
                        </div>
                        <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                            <span class="block text-[9px] text-slate-400 uppercase tracking-wider font-black mb-0.5">Horario</span>
                            <span class="text-slate-800 font-black" x-text="appointment.time"></span>
                        </div>
                    </div>

                    <!-- Servicio Contratado (Existente) -->
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 mb-1">
                        <span class="block text-[9px] text-slate-400 uppercase tracking-wider font-black mb-0.5">Servicio Contratado</span>
                        <p class="text-indigo-600 font-black" x-text="appointment.service"></p>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5" x-text="'Duración estimada: ' + appointment.duration + ' minutos'"></p>
                    </div>

                    <!-- Sede Asignada (Existente) -->
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 mb-1">
                        <span class="block text-[9px] text-slate-400 uppercase tracking-wider font-black mb-0.5">Sede asignada</span>
                        <p class="text-slate-600 font-bold" x-text="appointment.address"></p>
                    </div>

                    <!-- Observaciones (Existente) -->
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 mb-1">
                        <span class="block text-[9px] text-slate-400 uppercase tracking-wider font-black mb-0.5">Observaciones de la Cita</span>
                        <p class="text-slate-500 font-medium leading-relaxed mt-1 whitespace-pre-line" x-text="appointment.notes"></p>
                    </div>
                </div>
            </template>

            <!-- Botón de cierre único (Lectura Pura) -->
            <div class="mt-5 pt-3 border-t border-slate-100">
                <button type="button" @click="openModal = false" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-black py-3 rounded-xl transition uppercase tracking-wider text-xs text-center">
                    Cerrar Consulta
                </button>
            </div>
        </div>
    </div>
</div>
