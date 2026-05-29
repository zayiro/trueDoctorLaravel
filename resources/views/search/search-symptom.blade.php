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
                const urlParams = new URLSearchParams(window.location.search);
                const sintomaUrl = urlParams.get('symptom');
                
                if (sintomaUrl && sintomaUrl.trim().length >= 3) {
                    this.query = sintomaUrl;
                    this.buscar();
                }
            },            
            buscar() {
                if (this.query.trim() === this.ultimaBusqueda) return;
                if (this.query.trim().length < 3) return;
                
                this.cargando = true;
                this.busquedaRealizada = true;
                this.ultimaBusqueda = this.query.trim();
                
                fetch(`{{ route('partner.search.symptom') }}?symptom=${encodeURIComponent(this.query)}`, {
                    headers: { 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(res => {
                    if (res.exito) {
                        console.log('Respuesta del servidor:', res); // Debug detallado
                        this.medicos = res.medicos; 
                        this.triageInfo.activa = true; 
                        this.triageInfo.consejo = res.triage.consejo;
                        this.triageInfo.urgencia = res.triage.urgencia;
                        this.triageInfo.nombreEspecialidad = res.triage.especialidad_correcta;
                        this.triageInfo.slug = res.triage.especialidad_slug; 
                    }
                    this.cargando = false;
                })
                .catch(() => this.cargando = false);
            }
        }">

            <div class="flex flex-col items-center justify-center">                                    
                <!-- Encabezado de la nueva sección -->
                <div class="text-center mt-5 mb-8 max-w-2xl">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900 md:text-4xl">Asistente de Orientación Médica</h1>
                    <p class="text-slate-500 mt-2 text-base">Describe cómo te sientes en lenguaje natural y nuestro sistema inteligente te guiará con el especialista adecuado.</p>
                </div>

                <!-- Formulario de búsqueda -->
                <div class="w-full max-w-4xl mb-6">
                    <form @submit.prevent="buscar()" class="bg-white p-4 rounded-3xl shadow-2xl flex flex-col md:flex-row gap-4 border border-slate-100">
                        
                        <!-- Selector de Especialidad -->
                        <div class="flex-1">
                            <label for="symptom" class="block text-[10px] font-black text-slate-400 uppercase ml-3 mb-1">¿Qué síntomas tienes?</label>
                            <input type="search" id="symptom" x-model="query" :disabled="cargando" name="symptom" placeholder="Ej: Siento que la habitación me da vueltas al acostarme..." required minlength="3" class="w-full border-0 focus:ring-0 font-bold text-slate-700 bg-slate-50 rounded-2xl py-3 px-4 placeholder-slate-400">
                        </div>

                        <!-- Botón Buscar -->
                        <button type="submit" :disabled="cargando || query.trim().length < 3" class="bg-blue-600 hover:bg-blue-700 text-white font-black px-10 py-4 rounded-2xl transition disabled:opacity-50 shadow-lg shadow-blue-200 flex items-center justify-center gap-2">
                            <!-- Icono de lupa / Spinner -->
                            <svg x-show="!cargando" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            <svg x-show="cargando" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            
                            <span x-text="cargando ? 'Analizando...' : 'Buscar'"></span>
                        </button>                  
                    </form>
                </div>

                <!-- Alerta de Triage Dinámica -->
                <div class="w-full max-w-4xl">
                    <template x-if="triageInfo.activa && !cargando">
                        <div :class="{
                            'bg-emerald-50 border-emerald-500 text-emerald-900': triageInfo.urgencia === 'Baja',
                            'bg-amber-50 border-amber-500 text-amber-900': triageInfo.urgencia === 'Media',
                            'bg-rose-50 border-rose-500 text-rose-900': triageInfo.urgencia === 'Alta'
                        }" class="border-l-4 shadow-sm mb-6 rounded-r-2xl p-4 flex items-start gap-3" role="alert">
                            <span class="text-2xl leading-none">💡</span>
                            <div>
                                <p class="text-base font-medium mb-1">
                                    <strong>Prioridad <span x-text="triageInfo.urgencia"></span>:</strong> 
                                    <span x-text="triageInfo.consejo"></span>
                                </p>
                                <p class="text-sm opacity-90">
                                    Contamos con profesionales calificados para atenderte.
                                    <a :href="`{{ route('search') }}?specialty=${triageInfo.slug}&city=null`" class="underline font-bold ml-1 hover:opacity-80 transition">
                                        Ver nuestros especialistas en <span x-text="triageInfo.nombreEspecialidad.toLowerCase()"></span> recomendados →
                                    </a>
                                </p>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Resultados de Médicos Coincidentes -->
                <div class="w-full max-w-4xl mt-4">
                    <!-- Spinner de Carga Central -->
                    <template x-if="cargando">
                        <div class="text-center py-12 flex flex-col items-center justify-center">
                            <svg class="animate-spin h-12 w-12 text-blue-600 mb-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <p class="text-slate-500 font-medium">Procesando diagnóstico preliminar de especialidades...</p>
                        </div>
                    </template>

                    <!-- Listado Dinámico de Alpine -->
                    <template x-if="medicos.length > 0 && !cargando">
                        <div class="space-y-4">
                            <h4 class="text-slate-700 font-bold text-lg">Especialistas sugeridos disponibles para agendar:</h4>
                            
                            <template x-for="medico in medicos" :key="medico.id">
                                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 border-l-4 border-l-blue-600 p-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 transition hover:shadow-md">
                                    <div>
                                        <h5 class="text-slate-900 font-bold text-lg mb-1" x-text="'Dr(a). ' + medico.user.name"></h5>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200" x-text="triageInfo.nombreEspecialidad"></span>
                                    </div>
                                    <button class="w-full sm:w-auto bg-white border border-blue-600 text-blue-600 hover:bg-blue-50 text-sm font-bold px-6 py-2 rounded-full transition shadow-sm">
                                        Agendar Cita
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Estado Vacío (Opcional pero recomendado) -->
                    <template x-if="busquedaRealizada && medicos.length === 0 && !cargando">
                        <div class="text-center py-16 bg-white rounded-3xl border-2 border-dashed border-slate-100 p-8 max-w-5xl mx-auto shadow-sm">
                            <!-- Icono de advertencia amigable -->
                            <div class="w-16 h-16 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-4 text-amber-500">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>

                            <!-- Mensaje Principal -->
                            <h3 class="text-lg font-black text-slate-800 mb-2">No encontramos especialistas con estos filtros</h3>
                            <p class="text-sm text-slate-500 font-medium max-w-sm mx-auto mb-8">
                                Es posible que no haya turnos disponibles en la ciudad seleccionada para esta semana o el término de búsqueda sea muy específico.
                            </p>

                            <!-- Opciones de Guía / Alternativas de Acción -->
                            <div class="grid grid-col-1 gap-3 text-left bg-slate-50 p-5 rounded-2xl border border-slate-100 mb-6">
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">¿Qué puedes hacer ahora?</h4>
                                
                                <!-- Opción 1: Buscar en todas las ciudades -->
                                <button @click="document.getElementById('city').value = ''; window.scrollTo({top: 0, behavior: 'smooth'});" 
                                        class="flex items-center gap-3 text-xs font-bold text-blue-600 hover:text-blue-700 transition">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                    Ampliar la búsqueda a todas las ciudades
                                </button>

                                <!-- Opción 2: Telemedicina / Virtual -->
                                <button @click="document.getElementById('specialty').value = ''; window.scrollTo({top: 0, behavior: 'smooth'});" 
                                        class="flex items-center gap-3 text-xs font-bold text-slate-600 hover:text-slate-800 transition">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Ver médicos con atención virtual (Telemedicina)
                                </button>
                            </div>

                            <!-- Botón de Limpieza Rápida -->
                            <a href="{{ route('search') }}" 
                            class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-black text-xs uppercase tracking-wider px-6 py-3 rounded-xl transition">
                                <svg xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                Restablecer todos los filtros
                            </a>
                        </div>
                    </template>

                </div>

            </div>
        </div>
    </div>
</x-guest-layout>
