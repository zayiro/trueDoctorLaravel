<x-guest-layout>
    <!-- Sección: Buscador de especialidades medicas por ciudad (opcional) -->
    <div class="relative bg-white pb-20 pt-16 lg:pt-32 overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
            <div class="text-center mb-12">
                <h1 class="text-5xl lg:text-7xl font-black text-slate-900 tracking-tight mb-6">
                    Encuentra a tu <span class="text-blue-600">médico ideal</span>
                </h1>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                    Agenda tu <span class="font-medium">Cita Médica Virtual o Presencial</span> con los mejores especialistas de tu ciudad. Rápido, seguro y sin filas.
                </p>
            </div>

            <!-- Buscador Flotante -->
            <div class="max-w-4xl mx-auto">
                <x-medical-search-bar :specialties="$specialties" :cities="$cities" />
            </div>

            <!-- Decoración de fondo (Opcional) -->
            <div class="absolute top-0 right-0 -z-10 opacity-10">
                <svg class="w-[600px] h-[600px]" viewBox="0 0 200 200" xmlns="http://w3.org">
                    <path fill="#2563EB" d="M45,-77.2C58.3,-69.1,69.1,-56.3,77.2,-42.2C85.3,-28.1,90.7,-12.7,89.4,2.3C88.1,17.3,80.1,31.9,70.1,44.7C60.1,57.5,48.1,68.5,34.2,74.7C20.3,80.9,4.5,82.3,-11.2,79.5C-26.9,76.7,-42.5,69.7,-55.8,59.3C-69.1,48.9,-80.1,35.1,-84.6,19.6C-89.1,4.1,-87.1,-13.1,-80.4,-28.1C-73.7,-43.1,-62.3,-55.9,-49,-64C-35.7,-72.1,-20.5,-75.5,-4.7,-67.4C11.1,-59.3,22.2,-39.7,31.7,-77.2Z" transform="translate(100 100)" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Sección: buscador de sintomas de enfermedades -->
    <div class="relative bg-white pb-10 pt-10 lg:pt-4 overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
            <div class="text-center mb-12">
                <h1 class="text-5xl lg:text-7xl font-black text-slate-900 tracking-tight mb-6">
                    ¿No sabes a que <span class="text-blue-600">médico acudir?</span>
                </h1>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                    Describe tu síntoma, dolor o enfermedad y nosotros te sugerimos las opciones.
                </p>
            </div>

            <!-- Buscador Flotante -->
            <div class="max-w-4xl mx-auto mb-4">
                <form x-data="{ loading: false }" 
                    x-on:submit="loading = true"
                    action="{{ route('search.symptom.view') }}" 
                    method="GET" 
                    class="bg-white p-4 rounded-3xl shadow-2xl border border-slate-100 flex flex-col md:flex-row gap-4"
                    @restore-booking-buttons.window="loading = false">
                    
                    <!-- Input de Síntomas -->
                    <div class="flex-1">
                        <label for="symptom" class="block text-xs font-black text-slate-400 uppercase ml-3 mb-1">¿Qué síntomas tienes?</label>
                        <input type="search" 
                            name="symptom" 
                            id="symptom" 
                            value="{{ request('symptom') }}" 
                            placeholder="Ej: Siento que la habitación me da vueltas al acostarme..." 
                            required 
                            minlength="3" 
                            class="w-full border-0 focus:ring-0 font-bold text-slate-700 bg-slate-50 rounded-2xl py-3 px-4 placeholder-slate-400">                        
                    </div>

                    <!-- Botón Buscar Dinámico con Alpine.js -->
                    <button type="submit" 
                            :disabled="loading"
                            :class="loading ? 'opacity-75 cursor-not-allowed bg-blue-500' : 'bg-blue-600 hover:bg-blue-700'"
                            class="text-white font-black px-10 py-4 rounded-2xl transition shadow-lg shadow-blue-200 flex items-center justify-center gap-2 min-w-[160px]">
                        
                        <!-- Icono de Lupa (Oculto al cargar) -->
                        <svg x-show="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>

                        <!-- Icono Spinner SVG Animado (Solo visible al cargar) -->
                        <svg x-show="loading" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" style="display: none;">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <!-- Texto Dinámico -->
                        <span x-text="loading ? 'Buscando...' : 'Buscar'">Buscar</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Sección de Promoción: Análisis Clínico con IA -->
     <div class="relative bg-white pb-20 pt-10 overflow-hidden">
        <section class="max-w-7xl mx-auto px-6">
            <!-- Cambiado a un fondo oscuro profundo con bordes blancos semi-transparentes para contraste perfecto sobre Slate -->
            <div class="relative bg-slate-950 rounded-3xl overflow-hidden shadow-2xl border border-white/10">
                
                <!-- Elementos visuales de fondo (Brillo tecnológico sutil) -->
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="grid md:grid-cols-12 gap-8 p-8 md:p-12 items-center relative z-10">
                    
                    <!-- Columna Texto (Izquierda) -->
                    <div class="md:col-span-7 space-y-5 text-left">    
                        <!-- Textos Persuasivos -->
                        <h1 class="text-5xl lg:text-7xl font-black text-white tracking-tight mb-6">
                            ¿Tienes análisis médicos en casa que <span class="text-blue-600">no logras entender?</span>
                        </h1>
                        
                        <p class="text-slate-300 text-base md:text-lg leading-relaxed">
                            No esperes días para descifrar tus resultados clínicos. Nuestro <span class="font-bold">Asistente Médico Digital</span> analiza tus informes en PDF, elimina tus datos personales por seguridad y te explica todo en un lenguaje claro, cercano y 100% comprensible.
                        </p>

                        <!-- Pequeños checks de confianza rápida -->
                        <div class="flex flex-wrap gap-x-6 gap-y-2 pt-2 text-xs text-slate-400 font-medium">
                            <span class="flex items-center gap-2"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> Análisis de Biomarcadores</span>
                            <span class="flex items-center gap-2"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> Análisis Multidocumento</span>
                            <span class="flex items-center gap-2"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> Privacidad 100% Protegida</span>
                            <span class="flex items-center gap-2"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> Reporte Inmediato</span>                            
                            <span class="flex items-center gap-2"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> Copia Segura en tu Email</span>
                        </div>
                    </div>

                    <!-- Columna Interactiva / CTA (Derecha) -->
                    <div class="md:col-span-5 flex flex-col items-center justify-center">
                        <!-- Caja de acción con fondo sutilmente más claro que el fondo negro para dar relieve -->
                        <div class="w-full bg-slate-900 border border-white/5 p-6 md:p-8 rounded-2xl shadow-xl text-center space-y-6">

                            <!-- Enlace directo al Index explicativo -->
                            <a href="{{ route('medical-analysis.index') }}" class="group w-full bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-bold py-4 px-6 rounded-xl transition-all duration-300 transform hover:-translate-y-0.5 shadow-lg shadow-blue-500/20 flex items-center justify-center gap-3 text-base">
                                Descubrir Análisis con IA 
                                <i class="fa-solid fa-arrow-right text-sm transition-transform group-hover:translate-x-1"></i>
                            </a>

                            <p class="text-[11px] text-slate-500 leading-normal">
                                Cumple con normativas internacionales de protección de datos de salud de forma estricta.
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </section>
    </div>

    <!-- Componente Automático de Enlazado de Síntomas para SEO -->
    <div class="relative bg-white pb-10 pt-10 lg:pt-4 overflow-hidden">
        <section class="my-5 max-w-6xl mx-auto px-4">
            <x-symptom-cards-home />
        </section>
    </div>

</x-guest-layout>