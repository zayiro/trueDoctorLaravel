<x-guest-layout>
    <div class="max-w-7xl mx-auto px-4 py-8 mt-5">
        <div x-data="{ 
            ultimaBusqueda: '',
            query: '', 
            medicos: [], 
            triageInfo: { 
                activa: false, 
                consejo: '', 
                urgencia: 'Baja', 
                nombreEspecialidad: '', 
                slug: '' 
            },
            cargando: false,
            busquedaRealizada: false,
            
            init() {
                // Si el usuario viene desde el Home, capturamos el síntoma automáticamente
                const urlParams = new URLSearchParams(window.location.search);
                const sintomaUrl = urlParams.get('symptom');
                
                if (sintomaUrl && sintomaUrl.trim().length >= 3) {
                    this.query = sintomaUrl;
                    this.buscar();
                }
            },            
            buscar() {
                // Si el síntoma es exactamente igual al anterior, no hacemos nada
                if (this.query.trim() === this.ultimaBusqueda) return;

                if (this.query.trim().length < 3) return;
                this.cargando = true;
                this.busquedaRealizada = true;

                // Guardamos la consulta actual como la última realizada
                this.ultimaBusqueda = this.query.trim();
                
                fetch(`{{ route('partner.search.symptom') }}?symptom=${encodeURIComponent(this.query)}`, {
                    headers: { 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(res => {
                    if (res.exito) {
                        // Ajustamos a 'res.medicos' que es el nombre enviado desde el nuevo controlador
                        this.medicos = res.medicos; 
                        
                        // Mapeamos los datos regresados por el backend unificado
                        this.triageInfo.activa = true; // La IA siempre se ejecuta si res.exito es true
                        this.triageInfo.consejo = res.triage.consejo;
                        this.triageInfo.urgencia = res.triage.urgencia;
                        this.triageInfo.nombreEspecialidad = res.triage.especialidad_correcta;
                        
                        // El slug de la landing del síntoma generado automáticamente
                        this.triageInfo.slug = res.triage.slug_sugerido; 
                    }
                    this.cargando = false;
                })
                .catch(() => this.cargando = false);
            }
        }">

            <div class="justify-content-center">                                    
                <!-- Encabezado de la nueva sección -->
                <div class="text-center mt-5 mb-5">
                    <h1 class="fw-bold text-slate-900">Asistente de Orientación Médica</h1>
                    <p class="text-muted">Describe cómo te sientes en lenguaje natural y nuestro sistema inteligente te guiará con el especialista adecuado.</p>
                </div>

                <div class="max-w-4xl mx-auto mb-4">
                    <form @submit.prevent="buscar()" class="bg-white p-4 rounded-3xl shadow-2xl flex flex-col md:flex-row gap-4">
                        
                        <!-- Selector de Especialidad -->
                        <div class="flex-1">
                            <label for="symptom" class="block text-[10px] font-black text-slate-400 uppercase ml-3 mb-1">¿Qué sintomas tienes?</label>
                            <input type="search" id="symptom" x-model="query" :disabled="cargando" name="symptom" placeholder="Ej: Siento que la habitación me da vueltas al acostarme..." required minlength="3" class="w-full border-0 focus:ring-0 font-bold text-slate-700 bg-slate-50 rounded-2xl py-3 px-4" placeholder="Ej: Tengo un dolor punzante en el pecho que se pasa al brazo...">
                        </div>

                        <!-- Botón Buscar -->
                        <button type="submit" :disabled="cargando || query.trim().length < 3" class="bg-blue-600 hover:bg-blue-700 text-white font-black px-10 py-4 rounded-2xl transition shadow-lg shadow-blue-200 flex items-center justify-center gap-2" wire:loading.attr="disabled">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            <span x-show="cargando" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            <span x-show="!cargando">Buscar</span>
                            <span x-show="cargando">Analizando...</span>
                        </button>                  
                    </form>
                </div>

                <!-- Alerta de Triage con Enlace Dinámico -->
                <template x-if="triageInfo.activa && !cargando">
                    <div :class="{
                        'alert alert-success': triageInfo.urgencia === 'Baja',
                        'alert alert-warning': triageInfo.urgencia === 'Media',
                        'alert alert-danger': triageInfo.urgencia === 'Alta'
                    }" class="border-start border-4 shadow-sm mb-4 py-3" role="alert">
                        <div class="d-flex align-items-start">
                            <span class="fs-3 me-3">💡</span>
                            <div>
                                <p class="mb-2 fs-5">
                                    <strong>Prioridad <span x-text="triageInfo.urgencia"></span>:</strong> 
                                    <span x-text="triageInfo.consejo"></span>
                                </p>
                                <p class="mb-0 text-muted">
                                    Contamos con profesionales calificados para atenderte.
                                    <a :href="`{{ route('search') }}?specialty=${triageInfo.slug}`" class="alert-link text-decoration-underline fw-bold ms-1">
                                        Ver nuestros especialistas en <span x-text="triageInfo.nombreEspecialidad.toLowerCase()"></span> recomendados →
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Resultados de Médicos Coincidentes -->
                <div class="mt-4">
                    <!-- Spinner de Carga -->
                    <template x-if="cargando">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
                            <p class="text-muted mt-3">Procesando diagnóstico preliminar de especialidades...</p>
                        </div>
                    </template>

                    <!-- Listado Dinámico de Alpine -->
                    <template x-if="medicos.length > 0 && !cargando">
                        <div class="space-y-3">
                            <h4 class="text-secondary mb-3 fs-5 fw-semibold">Especialistas sugeridos disponibles para agendar:</h4>
                            <template x-for="medico in medicos" :key="medico.id">
                                <div class="card shadow-sm border-0 border-start border-4 border-primary mb-3">
                                    <div class="card-body d-flex justify-content-between align-items-center py-3">
                                        <div>
                                            <h5 class="card-title mb-1 text-dark fw-bold" x-text="'Dr(a). ' + medico.user.name"></h5>
                                            <span class="badge bg-light text-primary border" x-text="triageInfo.nombreEspecialidad"></span>
                                        </div>
                                        <button class="btn btn-outline-primary btn-sm px-4 rounded-pill shadow-sm fw-bold">
                                            Agendar Cita
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Estado sin resultados tras la consulta -->
                    <template x-if="busquedaRealizada && medicos.length === 0 && !cargando">
                        <div class="card text-center p-5 border-0 shadow-sm bg-light">
                            <div class="fs-1 mb-2">🔍</div>
                            <h5 class="fw-bold text-dark">No hay médicos directos para esta descripción</h5>
                            <p class="text-muted small mb-3">La IA identificó la rama médica, pero no tienes doctores asignados a ella actualmente.</p>
                            <a :href="`{{ route('search') }}`" class="text-blue-600 underline text-sm mt-2 block">
                                Intentar otra busqueda
                            </a>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>