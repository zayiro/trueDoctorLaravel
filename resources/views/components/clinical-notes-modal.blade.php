<!-- Componente reutilizable: Popup centrado con Fetch para Notas SOAP y Extemporáneas -->
<div x-data="{
        open: false,
        loading: false,
        error: null,
        data: null,
        async openModal(appointmentId) {
            this.open = true;
            this.loading = true;
            this.error = null;
            this.data = null;

            try {
                const response = await fetch(`/appointments/${appointmentId}/clinical-notes`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (!response.ok) {
                    this.error = result.message || 'No fue posible cargar las notas médicas.';
                } else {
                    this.data = result;
                }
            } catch (e) {
                this.error = 'Error de conexión al consultar la historia clínica.';
            } finally {
                this.loading = false;
            }
        },
        closeModal() {
            this.open = false;
            this.data = null;
            this.error = null;
        }
    }"
    @open-clinical-notes.window="openModal($event.detail.appointmentId)"
    @keydown.escape.window="if (open) closeModal()"
    x-show="open"
    style="display: none; z-index: 9999;"
    class="fixed inset-0 flex items-center justify-center p-4 sm:p-6"
    role="dialog"
    aria-modal="true">

    <!-- Fondo oscuro difuminado -->
    <div x-show="open"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="closeModal()"
         class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs"></div>

    <!-- Tarjeta Centrada del Modal -->
    <div x-show="open"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="relative z-10 w-full max-w-3xl max-h-[90vh] bg-white rounded-3xl shadow-2xl border border-slate-200 flex flex-col overflow-hidden text-left">

        <!-- Cabecera fija -->
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-base">🩺</span>
                <div>
                    <h3 class="text-base font-black text-slate-900">Notas Clínicas de la Consulta</h3>
                    <p x-show="data && data.has_history" class="text-xs text-slate-500 font-medium" x-text="`Ref: ${data?.reference} • ${data?.date}`"></p>
                </div>
            </div>
            <button type="button" @click="closeModal()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <!-- Cuerpo con Scroll interno -->
        <div class="p-6 overflow-y-auto space-y-5 flex-1">
            
            <!-- Estado 1: Cargando -->
            <div x-show="loading" class="py-12 flex flex-col items-center justify-center text-slate-500">
                <svg class="animate-spin h-8 w-8 text-indigo-600 mb-3" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-sm font-bold">Cargando evolución médica...</p>
            </div>

            <!-- Estado 2: Error o Sin historia -->
            <div x-show="!loading && (error || (data && !data.has_history))" class="py-10 text-center bg-slate-50 rounded-2xl border border-slate-200 p-6">
                <p class="text-sm font-bold text-slate-700" x-text="error || data?.message"></p>
            </div>

            <!-- Estado 3: Datos Clínicos Cargados -->
            <template x-if="!loading && data && data.has_history">
                <div class="space-y-5">
                    
                    <!-- Barra de estado médico y CIE-10 -->
                    <div class="flex flex-wrap items-center justify-between gap-3 bg-indigo-50/70 border border-indigo-100 p-3.5 rounded-2xl">
                        <div>
                            <span class="text-[11px] uppercase font-bold text-indigo-500 block">Especialista tratante</span>
                            <span class="text-sm font-black text-slate-900" x-text="`Dr(a). ${data.doctor_name}`"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <template x-if="data.cie10_code">
                                <span class="px-3 py-1 rounded-xl bg-white border border-indigo-200 text-xs font-black text-indigo-800" x-text="`CIE-10: ${data.cie10_code}`"></span>
                            </template>
                            <span class="px-3 py-1 rounded-xl text-xs font-black uppercase"
                                  :class="{
                                      'bg-emerald-100 text-emerald-800': data.status === 'signed',
                                      'bg-amber-100 text-amber-800': data.status === 'amended',
                                      'bg-slate-200 text-slate-700': data.status === 'draft'
                                  }"
                                  x-text="data.status === 'signed' ? 'Firmada' : (data.status === 'amended' ? 'Con Notas Extras' : 'Borrador')">
                            </span>
                        </div>
                    </div>
                    <!-- Cuadrícula SOAP -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        <div class="p-4 rounded-2xl border border-slate-200 bg-white">
                            <span class="text-[11px] font-black uppercase tracking-wider text-blue-600 block mb-1">S • Subjetivo</span>
                            <p class="text-sm text-slate-700 whitespace-pre-line leading-relaxed" x-text="data.soap.subjective || 'Sin registros.'"></p>
                        </div>

                        <div class="p-4 rounded-2xl border border-slate-200 bg-white">
                            <span class="text-[11px] font-black uppercase tracking-wider text-indigo-600 block mb-1">O • Objetivo</span>
                            <p class="text-sm text-slate-700 whitespace-pre-line leading-relaxed" x-text="data.soap.objective || 'Sin registros.'"></p>
                        </div>

                        <div class="p-4 rounded-2xl border border-slate-200 bg-white">
                            <span class="text-[11px] font-black uppercase tracking-wider text-amber-600 block mb-1">A • Análisis</span>
                            <p class="text-sm text-slate-700 whitespace-pre-line leading-relaxed" x-text="data.soap.assessment || 'Sin registros.'"></p>
                        </div>

                        <div class="p-4 rounded-2xl border border-slate-200 bg-white">
                            <span class="text-[11px] font-black uppercase tracking-wider text-emerald-600 block mb-1">P • Plan</span>
                            <p class="text-sm text-slate-700 whitespace-pre-line leading-relaxed" x-text="data.soap.plan || 'Sin registros.'"></p>
                        </div>
                    </div>

                    <!-- Sección de Notas Extemporáneas / Extras (si existen) -->
                    <template x-if="data.notes && data.notes.length > 0">
                        <div class="pt-3 border-t border-slate-200 space-y-3">
                            <h4 class="text-xs font-black uppercase tracking-wider text-amber-800 flex items-center gap-1.5">
                                <span>📌</span> Notas Extemporáneas / Aclaratorias (<span x-text="data.notes.length"></span>/3)
                            </h4>
                            <div class="space-y-2.5">
                                <template x-for="(note, idx) in data.notes" :key="note.id">
                                    <div class="p-3.5 rounded-2xl bg-amber-50/70 border border-amber-200">
                                        <div class="flex items-center justify-between text-[11px] font-bold text-amber-800 mb-1">
                                            <span x-text="`Nota #${idx + 1} • Dr(a). ${note.doctor}`"></span>
                                            <span x-text="note.created_at"></span>
                                        </div>
                                        <p class="text-sm text-slate-800 whitespace-pre-line leading-relaxed" x-text="note.content"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                </div>
            </template>
        </div>

        <!-- Pie fijo -->
        <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex justify-end shrink-0">
            <button type="button" @click="closeModal()" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-black uppercase tracking-wider rounded-xl transition">
                Cerrar
            </button>
        </div>
    </div>
</div>
