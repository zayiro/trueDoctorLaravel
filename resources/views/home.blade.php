<x-guest-layout>

    <!-- Sección: Buscador de medicos por especialidades medicas y por ciudad (opcional) o por sintomas -->
    <div class="relative z-40 bg-white pb-20 pt-16 lg:pt-30">
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
    </div>

    <section class="py-16 px-4 bg-gray-50">
        <div class="max-w-5xl mx-auto">            
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

    <section class="py-12 px-4">
        <div class="max-w-6xl mx-auto">            
            <!-- Grid Responsivo: 1 columna en mobile, 2 en desktop -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                
                <!-- COLUMNA IZQUIERDA: Imagen -->
                <div class="order-2 md:order-1">
                    <img src="{{ asset('images/telemedicina.png') }}" 
                        alt="Telemedicina" 
                        class="w-full h-auto rounded-2xl shadow-xl">
                </div>
                
                <!-- COLUMNA DERECHA: Texto -->
                <div class="order-1 md:order-2">             
                    <h1 class="text-5xl lg:text-7xl font-black text-slate-900 tracking-tight mb-6">
                        Consultas Médicas <span class="text-blue-600">en línea</span>
                    </h1>
                    <p class="text-lg text-gray-600 mb-6 leading-relaxed">                        
                        Servicio de Teleconsulta, atención médica desde cualquier lugar
                    </p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            <span class="text-gray-700">Disponible 24/7</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            <span class="text-gray-700">Doctores certificados</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            <span class="text-gray-700">Precios accesibles</span>
                        </li>
                    </ul>
                    <a href="{{ url('/search') }}?specialty=medicina-general" class="block w-full md:w-auto md:inline-block px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white text-center font-bold rounded-lg transition shadow-lg">
                        Agendar Cita
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 pt-10 px-4 bg-white">
        <div class="max-w-6xl mx-auto">
            <h2 class="text-3xl lg:text-6xl font-black text-slate-900 text-center tracking-tight mb-6">
                ¿Por qué elegir <span class="text-blue-600"><span class="font-black text-slate-900 tracking-tight">
                        Open<span class="text-indigo-600">Doctor</span><span class="text-emerald-500">Online</span></span>?</span>
            </h2>            
            
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
                            ¿Tienes exámenes médicos que <span class="text-blue-600">no logras entender?</span>
                        </h1>
                        <p class="text-slate-300 text-base md:text-lg leading-relaxed">                            
                            Leer exámenes médicos en línea.<br>
                            Interpreta tus exámenes médicos con <span class="font-medium">Inteligencia Artificial.</span>
                        </p>
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
                    <div class="md:col-span-5 flex flex-col items-center justify-center" x-data="{ loading: false }">
                        <!-- Caja de acción con fondo sutilmente más claro que el fondo negro para dar relieve -->
                        <div class="w-full bg-slate-900 border border-white/5 p-6 md:p-8 rounded-2xl shadow-xl text-center space-y-6">

                            <!-- Enlace directo al Index explicativo -->
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
                            :class="loading ? 'opacity-70 cursor-not-allowed' : ''"
                            class="group w-full bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-bold py-4 px-6 rounded-xl transition-all duration-300 transform hover:-translate-y-0.5 shadow-lg shadow-blue-500/20 flex items-center justify-center gap-3 text-base">
                                <span x-show="!loading">Análizar mis exámenes con IA</span>
                                <span x-show="loading">Cargando...</span>
                                <i class="fa-solid fa-arrow-right text-sm transition-transform group-hover:translate-x-1" x-show="!loading"></i>
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
    
    <section class="py-16 px-4 bg-gray-50">
        <div class="max-w-6xl mx-auto">
            <h2 class="text-4xl font-black text-center mb-12">El flujo de <span class="font-black text-slate-900 tracking-tight">
                        Open<span class="text-indigo-600">Doctor</span><span class="text-emerald-500">Online</span></span></h2>
            
            <!-- Timeline Desktop -->
            <div class="hidden md:block">
                <div class="flex items-center justify-between relative mb-16">
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

    <section class="py-16 px-4 bg-white">
        <div class="max-w-6xl mx-auto">
            <h2 class="text-4xl text-indigo-900 font-bold text-center mb-12">Especialidades Disponibles</h2>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach(['Medicina General', 'Cardiología', 'Dermatología', 'Pediatría', 'Ginecología', 'Psicología', 'Neurocirugía', 'Endocrinología'] as $specialty)
                    <div class="p-6 border border-gray-200 rounded-xl hover:border-blue-600 hover:shadow-lg transition text-center">
                        <svg class="w-8 h-8 text-blue-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="font-bold text-gray-800">{{ $specialty }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Componente Automático de Enlazado de Síntomas para SEO -->
    <div class="relative bg-white pb-10 pt-10 lg:pt-4 overflow-hidden">
        <section class="my-5 max-w-7xl mx-auto px-4">
            <x-symptom-cards-home />
        </section>
    </div>

    <section class="py-16 px-4 bg-gray-50">
        <div class="max-w-6xl mx-auto">
            <h2 class="text-4xl text-indigo-900 font-bold text-center mb-12">Lo que dicen nuestros pacientes</h2>
            
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

    <section class="py-16 px-4 bg-white">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-4xl text-indigo-900 font-bold text-center mb-12">Tu privacidad es nuestra prioridad</h2>
            
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

    <section class="py-16 px-4 bg-gray-50">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-4xl text-indigo-900 font-bold text-center mb-12">Preguntas Frecuentes</h2>
            
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

    <section class="py-16 px-4 bg-white">
        <div class="max-w-5xl mx-auto">
            <h2 class="text-4xl text-indigo-900 font-bold text-center mb-4">Medios de Pago Seguros</h2>
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