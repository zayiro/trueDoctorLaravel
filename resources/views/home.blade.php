<x-guest-layout>

    @auth
        @php
            // Buscamos si el paciente tiene alguna cita virtual abandonada hoy
            $pendingAppointment = \App\Models\Appointment::where('patient_id', auth()->user()->patient?->id)
                ->where('status', 'pending')
                ->where('payment_status', 'pending')
                ->whereHas('service', function($query) {
                    $query->where('type', 'virtual');
                })
                ->whereDate('created_at', \Carbon\Carbon::today('America/Bogota'))
                ->latest()
                ->first();
        @endphp

        @if($pendingAppointment)
            <div class="relative z-40 bg-white pb-10 pt-10">
                <div class="max-w-7xl mx-auto px-4 mt-4">                
                    <div id="alert-1" class="p-4 mb-4 text-sm text-indigo-800 rounded-2xl bg-indigo-50 border border-indigo-100 shadow-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" role="alert">
                        <span class="font-bold text-indigo-900 block shrink-0">¡Tienes una reserva en curso!</span> 
                        <span class="text-indigo-700">
                            Completa ahora el pago de tu videoconsulta con el 
                            <strong>{{ $pendingAppointment->doctor->gender === 'male' ? 'Dr. ' : 'Dra. ' }}{{ ucfirst($pendingAppointment->doctor->user->name) }}</strong>.
                            Y no pierdas tu reservación médica.
                        </span>
                        <a href="{{ route('appointments.preview', $pendingAppointment->id) }}" 
                        class="inline-flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold uppercase tracking-wider px-4 py-2 pt-2.5 rounded-xl transition shadow-sm cursor-pointer">
                            Retomar y pagar
                        </a>
                        <span class="sr-only">Close</span>
                        <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>                  
                    </div>
                </div>
            </div>

            
        @endif
    @endauth

     @if(session('error'))
        <div class="relative z-40 bg-white pb-10 pt-10">
            <div class="max-w-7xl mx-auto px-4 mt-4"> 
                <div class="flex items-center p-4 mb-4 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm" role="alert">
                    <svg class="flex-shrink-0 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                    </svg>
                    <div class="ms-3 text-sm font-bold">
                        {{ session('error') }}
                    </div>
                </div>
            </div>
        </div>        
    @endif

    <!-- Sección: Buscador de medicos por especialidades medicas y por ciudad (opcional) o por sintomas -->
    <section class="relative z-40 bg-white pb-20 sm:pb-10 pt-16 lg:pt-30">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
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
                <x-medical-search-bar :specialties="$specialties" :cities="$cities" :symptoms="$symptoms" :show-city="false" />
            </div>

            <!-- Decoración de fondo (Opcional) -->
            <div class="absolute top-0 right-0 -z-10 opacity-10">
                <svg class="w-[600px] h-[600px]" viewBox="0 0 200 200" xmlns="http://w3.org">
                    <path fill="#2563EB" d="M45,-77.2C58.3,-69.1,69.1,-56.3,77.2,-42.2C85.3,-28.1,90.7,-12.7,89.4,2.3C88.1,17.3,80.1,31.9,70.1,44.7C60.1,57.5,48.1,68.5,34.2,74.7C20.3,80.9,4.5,82.3,-11.2,79.5C-26.9,76.7,-42.5,69.7,-55.8,59.3C-69.1,48.9,-80.1,35.1,-84.6,19.6C-89.1,4.1,-87.1,-13.1,-80.4,-28.1C-73.7,-43.1,-62.3,-55.9,-49,-64C-35.7,-72.1,-20.5,-75.5,-4.7,-67.4C11.1,-59.3,22.2,-39.7,31.7,-77.2Z" transform="translate(100 100)" />
                </svg>
            </div>
        </div>
    </section>    

    <!-- Teleconsulta Hero Section -->
    <section class="relative bg-white md:py-10 overflow-hidden">
        <!-- Destellos orgánicos sutiles de fondo -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-full pointer-events-none opacity-40">
            <div class="absolute top-[-5%] right-[-5%] w-[400px] h-[400px] bg-blue-200/20 rounded-full blur-[100px]"></div>
            <div class="absolute bottom-[5%] left-[-5%] w-[450px] h-[450px] bg-emerald-200/20 rounded-full blur-[110px]"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 z-10">
            <!-- Contenedor Principal Unificado en Gris Suave -->
            <div class="bg-slate-50 backdrop-blur-xl rounded-3xl p-6 sm:p-10 md:p-14 border border-slate-200/80 shadow-[0_8px_30px_rgb(0,0,0,0.02)]">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-8 lg:gap-12 items-center">
                    
                    <!-- COLUMNA IZQUIERDA: Textos y Beneficios Clave -->
                    <div class="md:col-span-7 space-y-6 text-left">
                        <span class="inline-flex items-center gap-1.5 px-3 py-2 pt-2.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10 tracking-wide uppercase">
                            Atención Médica Inmediata
                        </span>
                        
                        <h2 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-tight">
                            Consultas Médicas <span class="text-blue-600 block sm:inline">en línea</span>
                        </h2>
                        
                        <p class="text-slate-600 text-base md:text-lg leading-relaxed max-w-xl">
                            Accede a nuestro servicio de teleconsulta y recibe atención médica experta desde cualquier lugar, eliminando traslados innecesivos.
                        </p>

                        <!-- Beneficios Rápidos Exclusivos -->
                        <div class="flex flex-wrap gap-4 pt-2">
                            <span class="flex items-center gap-2 text-sm font-semibold text-slate-700 bg-white px-3 py-1.5 rounded-lg border border-slate-200/60 shadow-sm">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Disponible 24/7
                            </span>
                            <span class="flex items-center gap-2 text-sm font-semibold text-slate-700 bg-white px-3 py-1.5 rounded-lg border border-slate-200/60 shadow-sm">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0110 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/></svg> Doctores Certificados
                            </span>
                        </div>
                        <!-- Botón de Agendamiento Principal -->
                        <div class="pt-4 border-t border-slate-200/60">
                            <a href="{{ url('/search') }}?specialty=medicina-general" class="group w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-8 rounded-xl transition-all duration-200 shadow-md shadow-blue-600/15 flex items-center justify-center gap-2.5 text-base transform active:scale-[0.98]">
                                Agendar Cita
                                <svg class="w-4 h-4 text-blue-200 group-hover:text-white group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        </div>

                        <!-- Módulo Informativo Integrado en la estructura -->
                        <div class="space-y-3 pt-6">
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">¿Cómo funciona la teleconsulta?</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="flex items-start gap-3 bg-white p-3 rounded-xl border border-slate-100 shadow-sm">
                                    <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    <span class="text-sm text-slate-600 leading-normal">Consulta médica profesional mediante videollamada segura.</span>
                                </div>
                                <div class="flex items-start gap-3 bg-white p-3 rounded-xl border border-slate-100 shadow-sm">
                                    <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    <span class="text-sm text-slate-600 leading-normal">Conversa con tu doctor desde la comodidad de tu hogar u oficina.</span>
                                </div>
                                <div class="flex items-start gap-3 bg-white p-3 rounded-xl border border-slate-100 shadow-sm">
                                    <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    <span class="text-sm text-slate-600 leading-normal">Ahorra tiempo valioso al evitar filas y salas de espera.</span>
                                </div>
                                <div class="flex items-start gap-3 bg-white p-3 rounded-xl border border-slate-100 shadow-sm">
                                    <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    <span class="text-sm text-slate-600 leading-normal">Fácil acceso desde cualquier celular, tablet o computadora.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- COLUMNA DERECHA: Elemento Visual -->
                    <div class="md:col-span-5 flex justify-center items-center">
                        <div class="relative w-full max-w-sm md:max-w-none group">
                            <!-- Efecto sutil de brillo perimetral en la imagen al hacer hover -->
                            <div class="absolute inset-0 bg-gradient-to-tr from-blue-600 to-indigo-600 rounded-2xl opacity-0 group-hover:opacity-5 transition-opacity duration-300"></div>
                            <img src="{{ asset('images/telemedicina.png') }}" 
                                alt="Plataforma de Telemedicina Avanzada" 
                                class="w-full h-auto rounded-2xl shadow-[0_10px_35px_rgba(0,0,0,0.03)] border border-slate-200/60 object-cover transform group-hover:scale-[1.01] transition-all duration-300">
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- Sección de Promoción: Análisis Clínico con IA -->
    <section class="relative z-40 bg-white md:py-10 overflow-hidden">
        <!-- Destellos de luz orgánicos muy suaves en tonos salud -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-full pointer-events-none opacity-40">
            <div class="absolute top-[-10%] right-[-5%] w-[450px] h-[450px] bg-blue-200/30 rounded-full blur-[100px]"></div>
            <div class="absolute bottom-[10%] left-[-5%] w-[450px] h-[450px] bg-emerald-200/20 rounded-full blur-[100px]"></div>
        </div>

        <div class="relative max-w-7xl mx-auto pt-10 px-4 sm:px-6 z-10">
            <!-- Tarjeta Principal de Cristal Luminoso -->
            <div class="bg-slate-50 backdrop-blur-xl rounded-3xl p-6 sm:p-10 md:p-14 border border-slate-200/80 shadow-[0_8px_30px_rgb(0,0,0,0.02)]">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-8 lg:gap-12 items-center">
                    
                    <!-- Columna Texto (Izquierda) -->
                    <div class="md:col-span-7 space-y-6 text-left">    
                        <span class="inline-flex items-center gap-1.5 px-3 py-2 pt-2.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10 tracking-wide uppercase">
                            Segunda Opinión Médica con IA
                        </span>

                        <h2 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-tight">
                            Interpreta tus exámenes médicos <span class="text-blue-600">con Inteligencia Artificial</span>
                        </h2>
                        
                        <p class="text-slate-600 text-base md:text-lg leading-relaxed max-w-xl">                            
                            Obtén un informe técnico asistido por IA en segundos. Diseñado para ayudarte a comprender tus biomarcadores de forma clara y complementar el criterio de tu especialista.
                        </p>
                        <!-- Grid de Estudios Soportados (UI Limpia) -->
                        <div class="space-y-4 pt-5 border-t border-slate-100">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Estudios Soportados</p>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- 1. Laboratorio -->
                                <div class="flex items-center space-x-3 bg-white p-3 rounded-xl border border-slate-100 shadow-sm">
                                    <div class="p-2 bg-blue-50 text-blue-600 rounded-lg shrink-0">
                                        <svg xmlns="http://w3.org" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                                    </div>
                                    <div>
                                        <p class="text-slate-800 text-sm font-semibold">Laboratorio</p>
                                        <p class="text-[11px] text-slate-400">Sangre, orina y bioquímica</p>
                                    </div>
                                </div>

                                <!-- 2. Radiografía -->
                                <div class="flex items-center space-x-3 bg-white p-3 rounded-xl border border-slate-100 shadow-sm">
                                    <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg shrink-0">
                                        <svg xmlns="http://w3.org" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" /><path stroke-linecap="round" d="M12 6v12M8 10h8M7 14h10" /></svg>
                                    </div>
                                    <div>
                                        <p class="text-slate-800 text-sm font-semibold">Radiografía</p>
                                        <p class="text-[11px] text-slate-400">Placas Rayos X convencionales</p>
                                    </div>
                                </div>

                                <!-- 3. Ecografía -->
                                <div class="flex items-center space-x-3 bg-white p-3 rounded-xl border border-slate-100 shadow-sm">
                                    <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg shrink-0">
                                        <svg xmlns="http://w3.org" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                    </div>
                                    <div>
                                        <p class="text-slate-800 text-sm font-semibold">Ecografía</p>
                                        <p class="text-[11px] text-slate-400">Ultrasonido y Doppler</p>
                                    </div>
                                </div>

                                <!-- 4. Tomografía -->
                                <div class="flex items-center space-x-3 bg-white p-3 rounded-xl border border-slate-100 shadow-sm">
                                    <div class="p-2 bg-sky-50 text-sky-600 rounded-lg shrink-0">
                                        <svg xmlns="http://w3.org" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                                    </div>
                                    <div>
                                        <p class="text-slate-800 text-sm font-semibold">Tomografía</p>
                                        <p class="text-[11px] text-slate-400">Estudios seriados (TAC)</p>
                                    </div>
                                </div>

                                <!-- 5. Resonancia -->
                                <div class="flex items-center space-x-3 bg-white p-3 rounded-xl border border-slate-100 shadow-sm">
                                    <div class="p-2 bg-violet-50 text-violet-600 rounded-lg shrink-0">
                                        <svg xmlns="http://w3.org" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" /></svg>
                                    </div>
                                    <div>
                                        <p class="text-slate-800 text-sm font-semibold">Resonancia</p>
                                        <p class="text-[11px] text-slate-400">RMN de alta definición</p>
                                    </div>
                                </div>

                                <!-- 6. DICOM -->
                                <div class="flex items-center space-x-3 bg-white p-3 rounded-xl border border-slate-100 shadow-sm">
                                    <div class="p-2 bg-teal-50 text-teal-600 rounded-lg shrink-0">
                                        <svg xmlns="http://w3.org" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    </div>
                                    <div>
                                        <p class="text-slate-800 text-sm font-semibold">DICOM</p>
                                        <p class="text-[11px] text-slate-400">Archivos médicos (.dcm)</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Columna Interactiva / CTA (Derecha) -->
                    <div class="md:col-span-5 flex flex-col items-center justify-center mt-8 md:mt-0" x-data="{ loading: false }">
                        <!-- Tarjeta Blanca de Acción -->
                        <div class="w-full bg-white rounded-2xl border border-slate-200/60 p-6 md:p-8 text-center space-y-6">
                            <div class="space-y-1">
                                <p class="text-xs uppercase tracking-wider font-bold text-slate-400">Procesador Inteligente</p>
                                <h4 class="text-slate-800 font-extrabold text-lg">¿Listo para comenzar?</h4>
                            </div>

                            <!-- Botón Principal Clínico -->
                            <a href="{{ route('medical-analysis.index') }}" 
                            @click="
                                loading = true;
                                if (typeof gtag === 'function') {
                                    gtag('event', 'start_medical_analysis', {
                                        'action': 'discover_analysis',
                                        'source': 'landing_promotion',
                                        'feature_type': 'lab_analysis'
                                    });
                                }
                            "
                            :class="loading ? 'opacity-70 cursor-not-allowed pointer-events-none' : ''"
                            class="group w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 rounded-xl transition-all duration-200 shadow-md shadow-blue-600/15 flex items-center justify-center gap-2.5 text-base transform active:scale-[0.98]">
                                
                                <span x-show="!loading">Analizar exámenes con IA</span>
                                <span x-show="loading" class="flex items-center gap-2">
                                    <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Cargando...
                                </span>
                                
                                <svg class="w-4 h-4 text-blue-200 group-hover:text-white group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </a>

                            <div class="pt-3 border-t border-slate-200">
                                <p class="text-[11px] text-slate-400 leading-normal">
                                    Tu privacidad está asegurada. Cumplimos con estándares estrictos de protección de datos de salud.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- Skin Analysis Hero Section -->
    <section class="relative z-40 bg-white md:py-10 overflow-hidden">
        <!-- Destellos de luz orgánicos muy suaves en el fondo de la página -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-full pointer-events-none opacity-40">
            <div class="absolute top-[-5%] left-[-5%] w-[400px] h-[400px] bg-blue-200/30 rounded-full blur-[100px]"></div>
            <div class="absolute bottom-[5%] right-[-5%] w-[450px] h-[450px] bg-indigo-200/20 rounded-full blur-[110px]"></div>
        </div>

        <div class="relative max-w-7xl mx-auto pt-10 px-4 z-10">
            <!-- Contenedor Principal Estilizado en Gris Suave (Consistencia Visual) -->
            <div class="bg-slate-50 backdrop-blur-xl rounded-3xl p-6 sm:p-10 md:p-14 border border-slate-200/80 shadow-[0_8px_30px_rgb(0,0,0,0.02)]">
                
                <!-- Header Interno -->
                <div class="text-center mb-12 md:mb-16">
                    <span class="inline-flex items-center gap-1.5 px-3 py-2 pt-2.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10 mb-5 tracking-wide uppercase">
                        Tecnología Inteligente
                    </span>
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight max-w-3xl mx-auto leading-tight">
                        Analiza tu Piel 
                        <span class="text-blue-600 block sm:inline">al Instante</span>
                    </h1>
                    <p class="mt-4 text-base md:text-lg text-slate-600 max-w-xl mx-auto leading-relaxed">
                        3 pasos simples para detectar cambios de piel potencialmente graves con el respaldo de nuestra Inteligencia Artificial.
                    </p>
                </div>
                <!-- Steps Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8 mb-12">
                    <!-- Paso 1 -->
                    <div class="group relative bg-white rounded-2xl border border-slate-200/60 p-3 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between">
                        <div>
                            <div class="relative aspect-[4/3] w-full overflow-hidden rounded-xl bg-slate-100">
                                <span class="absolute top-3 left-3 bg-slate-900/90 backdrop-blur-sm text-white text-[11px] font-semibold px-2.5 py-1 rounded-lg flex items-center gap-1 z-10 shadow-sm">
                                    <span>01</span>
                                    <span class="text-slate-500">/</span>
                                    <span class="text-slate-300">Paso</span>
                                </span>
                                <img src="{{ asset('images/skin-analysis/mancha-piel.jpg') }}" alt="Toma una foto clara de tu piel" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500 ease-out">
                            </div>
                            <div class="p-4 pt-5">
                                <h4 class="text-lg font-bold text-slate-900 mb-2 group-hover:text-blue-600 transition-colors duration-200">Toma una Foto</h4>
                                <p class="text-slate-600 text-sm leading-relaxed">Sube una foto clara y bien iluminada del área de piel que te preocupa.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 2 -->
                    <div class="group relative bg-white rounded-2xl border border-slate-200/60 p-3 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between">
                        <div>
                            <div class="relative aspect-[4/3] w-full overflow-hidden rounded-xl bg-slate-100">
                                <span class="absolute top-3 left-3 bg-slate-900/90 backdrop-blur-sm text-white text-[11px] font-semibold px-2.5 py-1 rounded-lg flex items-center gap-1 z-10 shadow-sm">
                                    <span>02</span>
                                    <span class="text-slate-500">/</span>
                                    <span class="text-slate-300">Paso</span>
                                </span>
                                <img src="{{ asset('images/skin-analysis/step-2.webp') }}" alt="Análisis automatizado por IA" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500 ease-out">
                            </div>
                            <div class="p-4 pt-5">
                                <h4 class="text-lg font-bold text-slate-900 mb-2 group-hover:text-blue-600 transition-colors duration-200">IA Analiza</h4>
                                <p class="text-slate-600 text-sm leading-relaxed">Nuestra IA procesa los patrones visuales buscando signos dermatológicos tempranos.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 3 -->
                    <div class="group relative bg-white rounded-2xl border border-slate-200/60 p-3 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between">
                        <div>
                            <div class="relative aspect-[4/3] w-full overflow-hidden rounded-xl bg-slate-100">
                                <span class="absolute top-3 left-3 bg-slate-900/90 backdrop-blur-sm text-white text-[11px] font-semibold px-2.5 py-1 rounded-lg flex items-center gap-1 z-10 shadow-sm">
                                    <span>03</span>
                                    <span class="text-slate-500">/</span>
                                    <span class="text-slate-300">Paso</span>
                                </span>
                                <img src="{{ asset('images/skin-analysis/2.webp') }}" alt="Informe clínico inmediato" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500 ease-out">
                            </div>
                            <div class="p-4 pt-5">
                                <h4 class="text-lg font-bold text-slate-900 mb-2 group-hover:text-blue-600 transition-colors duration-200">Recibe Resultado</h4>
                                <p class="text-slate-600 text-sm leading-relaxed">Obtén un análisis detallado, nivel de riesgo estimado y próximos pasos sugeridos.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- CTA Button -->
                <div class="flex justify-center pt-4">                    
                    <a href="{{ route('skin-analysis.index') }}" 
                    class="group w-full sm:w-auto max-w-max sm:max-w-none bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 shadow-md shadow-blue-600/15 flex items-center justify-center gap-2.5 text-base transform active:scale-[0.98]">
                        Comenzar análisis clínico
                        <svg class="w-4 h-4 text-blue-200 group-hover:text-white group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>

            </div>
        </div>
    </section>

    <!-- Sección registro de profesionales de la salud en el directorio de opendoctor.online -->
    <section class="relative z-40 bg-white overflow-hidden py-16 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-2 lg:items-center">
            
            <!-- Columna Izquierda: Contenido y Propuesta de Valor -->
            <div class="flex flex-col justify-center text-center lg:text-left">
                <!-- Etiqueta superior -->
                <span class="inline-flex self-center lg:self-start items-center rounded-full bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10 px-4 py-2 pt-2.5 text-xs font-semibold uppercase tracking-wider text-blue-800 mb-6">
                Para Profesionales de la Salud
                </span>
                
                <!-- Título Principal -->
                <h2 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-950 sm:text-4xl lg:text-5xl">
                Digitaliza tu consulta y expande tu <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">reputación médica</span>
                </h2>
                
                <p class="mt-4 text-base sm:text-lg text-slate-600 max-w-2xl mx-auto lg:mx-0">
                Únete al directorio de especialistas de <span class="text-lg font-black text-slate-900 tracking-tight">
                    Open<span class="text-indigo-600">Doctor</span><span class="text-emerald-500">Online</span></span>. Permite que cientos de pacientes encuentren tu perfil, agenden citas en línea y conozcan tu trayectoria profesional.
                </p>

                <!-- Lista de Beneficios con Heroicons -->
                <div class="mt-8 space-y-4 max-w-xl mx-auto lg:mx-0 text-left">
                    <!-- Beneficio 1 -->
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white shadow-md shadow-blue-200">
                        <!-- Heroicon: eye -->
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        </div>
                        <div>
                        <h3 class="font-semibold text-slate-900">Mayor visibilidad local</h3>
                        <p class="text-sm text-slate-600">Posiciona tu consultorio frente a pacientes que buscan activamente tu especialidad en tu ciudad.</p>
                        </div>
                    </div>

                    <!-- Beneficio 2 -->
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white shadow-md shadow-blue-200">
                        <!-- Heroicon: calendar-days -->
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                        </div>
                        <div>
                        <h3 class="font-semibold text-slate-900">Gestión de citas inteligente</h3>
                        <p class="text-sm text-slate-600">Herramientas automatizadas de agenda para reducir el ausentismo y organizar tus horarios sin esfuerzo.</p>
                        </div>
                    </div>

                    <!-- Beneficio 3 -->
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white shadow-md shadow-blue-200">
                        <!-- Heroicon: ShieldCheck -->
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                        </svg>
                        </div>
                        <div>
                        <h3 class="font-semibold text-slate-900">Perfil médico verificado</h3>
                        <p class="text-sm text-slate-600">Construye confianza digital mostrando tus certificaciones, opiniones reales de pacientes y logros.</p>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="mt-10 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                    <a href="{{ route('partner.register') }}" class="group w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-8 rounded-xl transition-all duration-200 shadow-md shadow-blue-600/15 flex items-center justify-center gap-2.5 text-base transform active:scale-[0.98]">
                        Registrarme como Doctor
                        <svg class="w-4 h-4 text-blue-200 group-hover:text-white group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>   
                    <a href="{{ route('plans.index') }}" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-6 py-3.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition duration-200">
                        Saber más sobre los planes
                    </a>
                </div>
            </div>

            <!-- Columna Derecha: Composición de Imagen Estilizada -->
            <div class="relative mx-auto w-full max-w-md lg:max-w-none lg:mx-0 flex justify-center">
                <!-- Elementos decorativos de fondo -->
                <div class="absolute -top-4 -left-4 w-72 h-72 bg-blue-400 rounded-full filter blur-3xl opacity-20 animate-pulse"></div>
                <div class="absolute -bottom-4 -right-4 w-72 h-72 bg-indigo-400 rounded-full filter blur-3xl opacity-20"></div>
                
                <!-- Contenedor de Imagen -->
                <div class="relative overflow-hidden transform lg:rotate-1 hover:rotate-0 transition duration-300">
                <img 
                    src="{{ asset('images/professional-directory-register-medical.png') }}" 
                    alt="Médico especialista usando plataforma OpenDoctor" 
                    class="h-[350px] w-full object-cover sm:h-[450px] md:w-[500px]"
                    style="mask-image: linear-gradient(to bottom, black 75%, transparent 100%); -webkit-mask-image: linear-gradient(to bottom, black 75%, transparent 100%);"
                    loading="lazy"
                />
                </div>
            </div>

            </div>
        </div>
    </section>

    <!-- Agendar una cita medica por telemedicina -->
    <section class="py-10 px-4 bg-gray-50">
        <div class="max-w-5xl mx-auto pb-5">            
            <div class="text-center mb-6">
                <h1 class="text-5xl lg:text-7xl font-black text-slate-900 tracking-tight mb-6">
                    Agende su cita médica <span class="text-blue-600">en línea</span>
                </h1>
            </div>
            <div class="space-y-6">                               
            <!-- Beneficios finales -->
            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="flex justify-center mb-4">
                        <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="font-black text-gray-900 text-lg max-w-2xl mx-auto">Menos de 5 minutos</p>
                    <p class="text-sm text-gray-600">Proceso rápido y fácil</p>
                </div>
                <div class="text-center">
                    <div class="flex justify-center mb-4">
                        <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <p class="font-black text-gray-900 text-lg max-w-2xl mx-auto">100% Seguro</p>
                    <p class="text-sm text-gray-600">Encriptado de principio a fin</p>
                </div>
                <div class="text-center">
                    <div class="flex justify-center mb-4">
                        <svg class="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="font-black text-gray-900 text-lg max-w-2xl mx-auto">Confirmación Inmediata</p>
                    <p class="text-sm text-gray-600">Recibe enlace y recordatorios</p>
                </div>
            </div>
        </div>
    </section>  

    <!-- Como consultar un medico o especialista médico en opendoctor.online -->
    <section class="py-16 pt-16 px-4 bg-white">
        <div class="max-w-6xl mx-auto">            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Card 1 -->
                <div class="p-8 bg-blue-50 rounded-2xl border border-blue-100">
                    <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-black mb-2">Ahorra Tiempo</h3>
                    <p class="text-gray-600">Consulta desde casa, sin desplazamientos ni filas de espera</p>
                </div>
                
                <!-- Card 2 -->
                <div class="p-8 bg-green-50 rounded-2xl border border-green-100">
                    <div class="w-16 h-16 bg-green-600 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-black mb-2">100% Seguro</h3>
                    <p class="text-gray-600">Encriptado y confidencial según normativas colombianas</p>
                </div>
                
                <!-- Card 3 -->
                <div class="p-8 bg-purple-50 rounded-2xl border border-purple-100">
                    <div class="w-16 h-16 bg-purple-600 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-black mb-2">Precios Justos</h3>
                    <p class="text-gray-600">Tarifas accesibles sin intermediarios</p>
                </div>
            </div>
        </div>
    </section>    
    
    <!-- Pasos para reservar una cita médica en opendoctor.online -->
    <section class="py-16 px-4 bg-gray-50">
        <div class="max-w-6xl mx-auto">            
            <!-- Timeline Desktop -->
            <div class="hidden md:block">
                <div class="flex items-center justify-between relative">
                    <!-- Línea conectora -->
                    <div class="absolute top-12 left-0 right-0 h-1 bg-gradient-to-r from-blue-600 via-purple-600 to-red-600"></div>
                    
                    <!-- Step 1 -->
                    <div class="flex flex-col items-center relative z-10">
                        <div class="w-24 h-24 bg-blue-600 rounded-full flex items-center justify-center text-white mb-4 border-4 border-white shadow-lg">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <p class="font-black text-center text-gray-900">Busca<br>Especialidad</p>
                    </div>
                    
                    <!-- Step 2 -->
                    <div class="flex flex-col items-center relative z-10">
                        <div class="w-24 h-24 bg-green-600 rounded-full flex items-center justify-center text-white mb-4 border-4 border-white shadow-lg">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="font-black text-center text-gray-900">Elige<br>Doctor</p>
                    </div>
                    
                    <!-- Step 3 -->
                    <div class="flex flex-col items-center relative z-10">
                        <div class="w-24 h-24 bg-purple-600 rounded-full flex items-center justify-center text-white mb-4 border-4 border-white shadow-lg">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <p class="font-black text-center text-gray-900">Selecciona<br>Fecha y Hora</p>
                    </div>
                    
                    <!-- Step 4 -->
                    <div class="flex flex-col items-center relative z-10">
                        <div class="w-24 h-24 bg-orange-500 rounded-full flex items-center justify-center text-white mb-4 border-4 border-white shadow-lg">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <p class="font-black text-center text-gray-900">Ingresa<br>tus datos</p>
                    </div>
                    
                    <!-- Step 5 -->
                    <div class="flex flex-col items-center relative z-10">
                        <div class="w-24 h-24 bg-red-600 rounded-full flex items-center justify-center text-white mb-4 border-4 border-white shadow-lg">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h10m4 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                            </svg>
                        </div>
                        <p class="font-black text-center text-gray-900">Paga y<br>¡Listo!</p>
                    </div>
                </div>
            </div>
            
            <!-- Timeline Mobile -->
            <div class="md:hidden space-y-4">
                <!-- Step 1 -->
                <div class="bg-white p-4 rounded-xl border-l-4 border-blue-600">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-black text-gray-900">Busca la Especialidad</p>
                            <p class="text-sm text-gray-600">Encuentra el especialista que necesitas</p>
                        </div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="bg-white p-4 rounded-xl border-l-4 border-green-600">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center text-white flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-black text-gray-900">Elige tu Doctor</p>
                            <p class="text-sm text-gray-600">Consulta perfiles y disponibilidad</p>
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="bg-white p-4 rounded-xl border-l-4 border-purple-600">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-12 h-12 bg-purple-600 rounded-full flex items-center justify-center text-white flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-black text-gray-900">Selecciona la Fecha y Horario</p>
                            <p class="text-sm text-gray-600">Virtual o presencial, elige tu horario</p>
                        </div>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="bg-white p-4 rounded-xl border-l-4 border-orange-600">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center text-white flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-black text-gray-900">Ingresa tus datos</p>
                            <p class="text-sm text-gray-600">Registra o inicia sesión</p>
                        </div>
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="bg-white p-4 rounded-xl border-l-4 border-red-600">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-12 h-12 bg-red-600 rounded-full flex items-center justify-center text-white flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h10m4 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-black text-gray-900">Paga y Confirma</p>
                            <p class="text-sm text-gray-600">Tu cita está agendada al instante</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Componente Automático de Enlazado de Síntomas para SEO -->
    <section class="relative bg-white pb-10 pt-10 lg:pt-4 overflow-hidden">
        <section class="my-5 max-w-7xl mx-auto px-4">
            <x-symptom-cards-home />
        </section>
    </section>

    <!-- Comentarios de los pacientes -->
    <section class="py-10 px-4 bg-gray-50">
        <div class="max-w-6xl mx-auto pb-5">
            <h1 class="text-5xl lg:text-7xl text-center font-black text-slate-900 tracking-tight mb-10">
                Lo que dicen nuestros
                <span class="text-blue-600">pacientes</span>
            </h1>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Testimonio 1 -->
                <div class="bg-white p-8 rounded-2xl shadow-md border-l-4 border-blue-600">
                    <div class="flex items-center gap-1 mb-4">
                        @for($i = 0; $i < 5; $i++)
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                        @endfor
                    </div>
                    <p class="text-gray-700 mb-4 italic">"Excelente servicio. El doctor fue muy atento y resolvió mis dudas rápidamente. Sin esperas."</p>
                    <p class="font-black text-gray-900">María González</p>
                    <p class="text-sm text-gray-500">Paciente verificado</p>
                </div>
                
                <!-- Testimonio 2 -->
                <div class="bg-white p-8 rounded-2xl shadow-md border-l-4 border-green-600">
                    <div class="flex items-center gap-1 mb-4">
                        @for($i = 0; $i < 5; $i++)
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                        @endfor
                    </div>
                    <p class="text-gray-700 mb-4 italic">"Muy práctico. Pude agendar en 2 minutos y consulté desde mi oficina."</p>
                    <p class="font-black text-gray-900">Carlos Rodríguez</p>
                    <p class="text-sm text-gray-500">Paciente verificado</p>
                </div>
                
                <!-- Testimonio 3 -->
                <div class="bg-white p-8 rounded-2xl shadow-md border-l-4 border-purple-600">
                    <div class="flex items-center gap-1 mb-4">
                        @for($i = 0; $i < 5; $i++)
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                        @endfor
                    </div>
                    <p class="text-gray-700 mb-4 italic">"Confiable y seguro. Mi información está protegida. Recomendado."</p>
                    <p class="font-black text-gray-900">Laura Martínez</p>
                    <p class="text-sm text-gray-500">Paciente verificado</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Texto de Privacidad de los datos del paciente medico -->
    <section class="py-16 px-4 bg-white">
        <h1 class="text-5xl lg:text-7xl text-center font-black text-slate-900 tracking-tight mb-10">
            Tu privacidad es nuestra
            <span class="text-blue-600">prioridad</span>
        </h1>
        <div class="max-w-4xl mx-auto">                        
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="flex gap-4">
                    <svg class="w-6 h-6 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                    </svg>
                    <div>
                        <h3 class="font-black text-gray-900">Encriptación End-to-End</h3>
                        <p class="text-gray-600 text-sm">Tus datos viajan de forma segura</p>
                    </div>
                </div>
                
                <div class="flex gap-4">
                    <svg class="w-6 h-6 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                    </svg>
                    <div>
                        <h3 class="font-black text-gray-900">Cumple Resolución 1995</h3>
                        <p class="text-gray-600 text-sm">Normativa colombiana de telemedicina</p>
                    </div>
                </div>
                
                <div class="flex gap-4">
                    <svg class="w-6 h-6 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                    </svg>
                    <div>
                        <h3 class="font-black text-gray-900">Doctores Verificados</h3>
                        <p class="text-gray-600 text-sm">Todos profesionales licenciados</p>
                    </div>
                </div>
                
                <div class="flex gap-4">
                    <svg class="w-6 h-6 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                    </svg>
                    <div>
                        <h3 class="font-black text-gray-900">Historial Confidencial</h3>
                        <p class="text-gray-600 text-sm">Solo tú accedes a tus registros</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Preguntas frecuentes -->
    <section class="py-16 px-4 bg-gray-50">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-5xl lg:text-7xl text-center font-black text-slate-900 tracking-tight mb-10">
                Preguntas
                <span class="text-blue-600">Frecuentes</span>
            </h1>
            <div class="space-y-4">
                <details class="bg-white p-6 rounded-xl cursor-pointer group">
                    <summary class="flex justify-between items-center font-black text-gray-900">
                        ¿Necesito cámara web?
                        <svg class="w-5 h-5 group-open:rotate-180 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                    </summary>
                    <p class="text-gray-600 mt-4">Sí, necesitas una cámara y micrófono. Cualquier smartphone o computadora funciona.</p>
                </details>
                
                <details class="bg-white p-6 rounded-xl cursor-pointer group">
                    <summary class="flex justify-between items-center font-black text-gray-900">
                        ¿Qué pasa si se cae internet?
                        <svg class="w-5 h-5 group-open:rotate-180 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                    </summary>
                    <p class="text-gray-600 mt-4">Puedes reagendar la consulta sin costo. También ofrecemos chat de texto como alternativa.</p>
                </details>
                
                <details class="bg-white p-6 rounded-xl cursor-pointer group">
                    <summary class="flex justify-between items-center font-black text-gray-900">
                        ¿Es válido para certificados?
                        <svg class="w-5 h-5 group-open:rotate-180 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                    </summary>
                    <p class="text-gray-600 mt-4">Sí, emitimos certificados médicos válidos para trámites laborales y administrativos.</p>
                </details>
                
                <details class="bg-white p-6 rounded-xl cursor-pointer group">
                    <summary class="flex justify-between items-center font-black text-gray-900">
                        ¿Cuánto cuesta una consulta?
                        <svg class="w-5 h-5 group-open:rotate-180 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                    </summary>
                    <p class="text-gray-600 mt-4">Desde $35.000 COP. Varía según el especialista. Sin costos ocultos.</p>
                </details>
            </div>
        </div>
    </section>

    <!-- Medios de pago seguros para pagar tu reservacion medica -->
    <section class="py-16 px-4 bg-white">
        <div class="max-w-5xl mx-auto">
            <h1 class="text-5xl lg:text-7xl text-center font-black text-slate-900 tracking-tight mb-10">
                Medios de Pago
                <span class="text-blue-600">Seguros</span>
            </h1>
            <p class="text-center text-gray-600 mb-12">Múltiples opciones de pago con la máxima seguridad</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                <!-- Columna Izquierda: Métodos de Pago -->
                <div>
                    <h3 class="text-2xl font-black text-gray-900 mb-8">Formas de Pago</h3>
                    
                    <div class="space-y-4">
                        <!-- Tarjeta de Crédito -->
                        <div class="flex items-center gap-4 p-4 border-2 border-gray-200 rounded-xl hover:border-blue-600 hover:bg-blue-50 transition cursor-pointer">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h10m4 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="font-black text-gray-900">Tarjeta de Crédito</p>
                                <p class="text-sm text-gray-600">Visa, Mastercard, American Express</p>
                            </div>
                        </div>
                        
                        <!-- Tarjeta de Débito -->
                        <div class="flex items-center gap-4 p-4 border-2 border-gray-200 rounded-xl hover:border-green-600 hover:bg-green-50 transition cursor-pointer">
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h10m4 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="font-black text-gray-900">Tarjeta de Débito</p>
                                <p class="text-sm text-gray-600">Todos los bancos colombianos</p>
                            </div>
                        </div>
                        
                        <!-- PSE -->
                        <div class="flex items-center gap-4 p-4 border-2 border-gray-200 rounded-xl hover:border-purple-600 hover:bg-purple-50 transition cursor-pointer">
                            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="font-black text-gray-900">PSE</p>
                                <p class="text-sm text-gray-600">Transferencia bancaria instantánea</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Columna Derecha: Seguridad -->
                <div>
                    <h3 class="text-2xl font-black text-gray-900 mb-8">Seguridad Garantizada</h3>
                    
                    <div class="space-y-4">
                        <!-- Encriptación -->
                        <div class="flex gap-4">
                            <svg class="w-6 h-6 text-green-600 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            <div>
                                <p class="font-black text-gray-900">Encriptación SSL</p>
                                <p class="text-sm text-gray-600">Todos tus datos se protegen con tecnología de banco</p>
                            </div>
                        </div>
                        
                        <!-- PCI DSS -->
                        <div class="flex gap-4">
                            <svg class="w-6 h-6 text-green-600 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            <div>
                                <p class="font-black text-gray-900">Cumplimiento PCI DSS</p>
                                <p class="text-sm text-gray-600">Estándar internacional de seguridad de pagos</p>
                            </div>
                        </div>
                        
                        <!-- Fraude -->
                        <div class="flex gap-4">
                            <svg class="w-6 h-6 text-green-600 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            <div>
                                <p class="font-black text-gray-900">Protección Antifraude</p>
                                <p class="text-sm text-gray-600">Monitoreo avanzado 24/7</p>
                            </div>
                        </div>
                        
                        <!-- Logo Wompi -->
                        <div class="mt-8 p-6 bg-gray-50 rounded-xl border border-gray-200">
                            <p class="text-xs text-gray-600 mb-3 font-medium">PROCESADOR DE PAGOS</p>
                            <div class="flex items-center gap-2">
                                <div class="max-w-xs mx-auto">
                                    <img src="{{ asset('images/wompi.jpg') }}" 
                                        alt="Paga tu consulta por Telemedicina con wompi de Bancolombia" 
                                        class="w-full h-auto rounded-2xl">
                                </div>
                                <p class="font-black text-gray-900">Wompi</p>
                            </div>
                            <p class="text-xs text-gray-600 mt-2">Plataforma líder de pagos en Colombia</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <x-scroll-to-top />

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof gtag === 'function') {
                // 🧬 EVENTO: Ver landing de análisis con IA
                gtag('event', 'view_medical_analysis_landing', {
                    'page_title': 'Medical Analysis with AI',
                    'feature_type': 'lab_analysis'
                });
            }
        });
    </script>
</x-guest-layout>