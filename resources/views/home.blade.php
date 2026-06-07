<x-guest-layout>
    <!-- Sección: Buscador de especialidades medicas por ciudad (opcional) -->
    <div class="relative bg-white pb-20 pt-16 lg:pt-32 overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
            <div class="text-center mb-12">
                <h1 class="text-5xl lg:text-7xl font-black text-slate-900 tracking-tight mb-6">
                    Encuentra a tu <span class="text-blue-600">médico ideal</span>
                </h1>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                    Reserva tu <span class="font-medium">Cita Médica en Línea</span> con los mejores especialistas de tu ciudad, de forma rápida, sencilla y sin esperas.
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
    <div class="relative bg-white pb-10 pt-12 lg:pt-4 overflow-hidden">
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
                    class="bg-white p-4 rounded-3xl shadow-2xl border border-slate-100 flex flex-col md:flex-row gap-4">
                    
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

    <!-- Sección: Analizador de Exámenes IA -->
    <div class="relative bg-white pb-10 pt-10 lg:pt-4 overflow-hidden">
        <section class="my-16 max-w-5xl mx-auto px-4 mt-5">
            <div class="relative bg-white rounded-[2.5rem] p-8 sm:p-10 overflow-hidden shadow-xl shadow-slate-900/10 border border-slate-400">
                
                <!-- Elementos Decorativos de Fondo (Luz radial suave) -->
                <div class="absolute -right-20 -top-20 w-80 h-80 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="grid gap-8 items-center relative z-10">
                    <!-- Textos Persuasivos -->
                     <h1 class="text-5xl lg:text-7xl font-black text-slate-900 tracking-tight mb-6">
                        ¿Tienes exámenes de laboratorio y <span class="text-blue-600">no entiendes los resultados?</span>
                    </h1>
                    <div class="space-y-4 text-center md:text-left">                        
                        <p class="text-base text-slate-500 leading-relaxed">
                            Sube tus reportes en PDF o imagen y recibe una 
                            <span class="text-slate-900 font-bold underline decoration-blue-500">interpretación médica en tiempo real</span>. 
                            Nuestro sistema analiza tus biomarcadores <span class="text-blue-600 font-bold">al instante</span>, entregándote un reporte detallado y seguro en segundos.
                        </p>
                        
                        <!-- Micro Ventajas Clínicas -->
                        <div class="pt-2 flex flex-wrap justify-center md:justify-start gap-x-4 gap-y-2 font-bold text-slate-600">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                Análisis de Biomarcadores
                            </span>
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                Copia Segura en tu Email
                            </span>
                        </div>
                    </div>

                    <!-- Botón y Precio -->
                    <div class="flex flex-col items-center justify-center bg-slate-50 border border-slate-300 p-8 rounded-2xl text-center space-y-4 text-lg">
    
                        {{-- CASO 1: Logueado y es un Paciente (Recibe beneficio Gratis) --}}
                        @if(auth()->check() && auth()->user()->role === 'patient')
                            <div class="space-y-1">
                                <span class="bg-green-100 text-green-800 text-xs font-bold px-2.5 py-1 rounded-full border border-green-300">
                                    Beneficio de Bienvenida Activo
                                </span>
                                <p class="text-3xl font-black tracking-tight text-slate-900 tabular-nums mt-2">
                                    $0 <span class="text-sm font-bold text-slate-500 underline decoration-red-500 line-through">$18.500</span>
                                </p>
                            </div>
                            
                            <a href="{{ route('exams.index') }}" 
                                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-base font-black px-6 py-4 rounded-2xl transition shadow-lg shadow-emerald-200 flex items-center justify-center gap-2 group">
                                Interpretar mi examen GRATIS
                                <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path>
                                </svg>
                            </a>
                            <p class="text-slate-500 text-xs font-medium">Sesión iniciada como Paciente</p>

                        {{-- CASO 2: Logueado pero NO es paciente (Doctores, Clínicas, Admins) --}}                        
                        @elseif(auth()->check() && auth()->user()->role !== 'patient')
                            <div class="space-y-1">
                                <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2.5 py-1 rounded-full border border-blue-300">
                                    Perfil Profesional / Administrativo
                                </span>
                                <p class="text-3xl font-black tracking-tight text-slate-900 tabular-nums mt-2">
                                    $18.500 <span class="text-sm font-bold text-slate-500">/ examen</span>
                                </p>
                            </div>
                            
                            <a href="{{ route('exams.index') }}" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white text-base font-black px-6 py-4 rounded-2xl transition shadow-lg shadow-blue-200 flex items-center justify-center gap-2 group">
                                Pruebas de Interpretación
                                <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path>
                                </svg>
                            </a>
                            <p class="text-slate-500 text-xs font-medium">Entorno corporativo de {{ ucfirst(auth()->user()->role) }}</p>

                        {{-- CASO 3: Visitante No Autenticado --}}
                        @else
                            <div class="space-y-0.5">
                                <p class="text-3xl font-black tracking-tight text-slate-900 tabular-nums">
                                    $18.500 <span class="text-sm font-bold text-slate-500">/ examen</span>
                                </p>
                            </div>
                            
                            <a href="{{ route('exams.index') }}" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white text-base font-black px-6 py-4 rounded-2xl transition shadow-lg shadow-blue-200 flex items-center justify-center gap-2 group">
                                Interpretar mis exámenes ahora
                                <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path>
                                </svg>
                            </a>
                            
                            <p class="text-slate-600 text-sm font-medium flex items-center justify-center gap-1">
                                Regístrate hoy y obtén tu <a href="{{ route('register') }}" class="text-blue-600 font-bold underline hover:text-blue-800">primer análisis 100% gratis</a>
                            </p>
                        @endif

                    </div>

                </div>
            </div>
        </section>
    </div>
</x-guest-layout>