<x-guest-layout
    meta_title="Crear Cuenta Gratis | Únete a OpenDoctorOnline" 
    meta_description="Regístrate gratis en OpenDoctor. Elige tu perfil como Paciente para agendar citas y gestionar tu historial, o como Doctor para digitalizar tu agenda y consultas"
>
    @if (session('info'))
        <div id="alert-info" class="flex items-center p-4 mb-4 text-info-800 rounded-2xl bg-info-50 border border-info-100 shadow-sm transition-opacity duration-500" role="alert">
            <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://w3.org" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L8 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
            </svg>
            <span class="sr-only">Éxito</span>
            <div class="ms-3 text-sm font-medium">
                {{ session('info') }}
            </div>
            <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-info-50 text-info-500 rounded-lg focus:ring-2 focus:ring-info-400 p-1.5 hover:bg-info-200 inline-flex items-center justify-center h-8 w-8" onclick="document.getElementById('alert-info').remove()">
                <span class="sr-only">Cerrar</span>
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://w3.org" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                </svg>
            </button>
        </div>
    @endif
    <div class="min-h-screen bg-slate-50 flex items-center justify-center py-12 px-6">
        <div class="max-w-6xl w-full mt-3">
            
            <div class="text-center mb-12">
                <h2 class="text-5xl font-black text-slate-900 mb-4">Crear una cuenta gratuita</h2>
                <p class="text-lg text-slate-600 font-medium">Selecciona tu perfil para comenzar tu experiencia</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                <!-- Opción: Paciente -->
                <a href="{{ route('register') }}" class="group bg-white p-8 rounded-3xl shadow-sm border-2 border-transparent hover:border-blue-500 hover:shadow-xl transition-all duration-300 text-center">
                    <div class="bg-blue-100 text-blue-600 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Soy Paciente</h3>
                    <p class="text-sm text-slate-500 text-left pt-3">Busca doctores, agenda citas y gestiona tu historial médico.</p>
                    <ul class="text-sm space-y-1.5 pt-3 pb-3">
                        <li class="flex items-start gap-1.5">
                            <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span class="text-gray-500 dark:text-gray-400">Reserva citas en segundos</span>
                        </li>
                        <li class="flex items-start gap-1.5">
                            <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span class="text-gray-500 dark:text-gray-400">Telemedicina y citas presenciales</span>
                        </li>
                        <li class="flex items-start gap-1.5">
                            <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span class="text-gray-500 dark:text-gray-400">Encuentra especialistas cerca</span>
                        </li>
                        <li class="flex items-start gap-1.5">
                            <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span class="text-gray-500 dark:text-gray-400">Historial de citas</span>
                        </li>
                        <li class="flex items-start gap-1.5">
                            <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span class="text-gray-500 dark:text-gray-400">Documentos médicos seguros</span>
                        </li>
                        <li class="flex items-start gap-1.5">
                            <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span class="text-gray-500 dark:text-gray-400">Recordatorios automáticos</span>
                        </li>
                    </ul>

                    <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-teal-100 text-teal-800 dark:bg-teal-500/20 dark:text-teal-400 mt-3">SELECCIONAR</span>
                </a>

                <!-- Opción: Especialista -->
                <a href="{{ route('partner.register') }}" class="group bg-white p-8 rounded-3xl shadow-sm border-2 border-transparent hover:border-blue-500 hover:shadow-xl transition-all duration-300 text-center">
                    <div class="bg-emerald-100 text-emerald-600 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Soy Doctor</h3>
                    <p class="text-sm text-slate-500 text-left pt-3">Gestiona tu agenda, sedes y atiende a tus pacientes digitalmente.</p>
                    <ul class="text-sm space-y-1.5 pt-3 pb-3">
                        <li class="flex items-start gap-1.5">
                            <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span class="text-gray-500 dark:text-gray-400">Perfil público en el buscador</span>
                        </li>
                        <li class="flex items-start gap-1.5">
                            <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span class="text-gray-500 dark:text-gray-400">Agenda y citas online</span>
                        </li>
                        <li class="flex items-start gap-1.5">
                            <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span class="text-gray-500 dark:text-gray-400">Historial de citas</span>
                        </li>
                        <li class="flex items-start gap-1.5">
                            <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span class="text-gray-500 dark:text-gray-400">Historial de pacientes</span>
                        </li>
                        <li class="flex items-start gap-1.5">
                            <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span class="text-gray-500 dark:text-gray-400">Reseñas verificadas</span>
                        </li>
                        <li class="flex items-start gap-1.5">
                            <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span class="text-gray-500 dark:text-gray-400">Estadísticas de visitas</span>
                        </li>
                    </ul>

                    <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-teal-100 text-teal-800 dark:bg-teal-500/20 dark:text-teal-400 mt-3">SELECCIONAR</span>
                </a>

                <!-- Opción: Clínica -->
                <!--
                <a href="{{ route('clinic.register') }}" class="group bg-white p-8 rounded-3xl shadow-sm border-2 border-transparent hover:border-blue-500 hover:shadow-xl transition-all duration-300 text-center">
                    <div class="bg-purple-100 text-purple-600 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Soy Gerente</h3>
                    <p class="text-sm text-slate-500">Administra múltiples doctores, sedes y servicios hospitalarios.</p>
                </a>-->

            </div>
            
            <div class="bg-white border-gray-100 dark:border-gray-700 rounded-2xl p-1 my-5">
                <!-- User Intent: Context: Tailwind CSS UI components | Constraints: Responsive design | Intent: Recreate section from image -->
                <section class="w-full max-w-5xl mx-auto p-4 sm:p-6 bg-gray-600">
                    <div class="bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700 rounded-2xl p-6 sm:p-8 shadow-xs">
                        
                        <!-- Encabezado superior: USO REAL -->
                        <div class="flex items-center gap-2 text-xs font-semibold tracking-wider text-gray-500 dark:text-gray-400 uppercase mb-5">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                        </svg>
                        <span>USO REAL</span>
                        </div>

                        <!-- Contenedor principal: Métricas + Testimonio -->
                        <div class="flex flex-col lg:flex-row lg:items-center gap-6 lg:gap-8">
                        
                            <!-- Tarjetas de métricas (Grid de 2 columnas en móvil) -->
                            <div class="grid grid-cols-2 gap-3 sm:gap-4 shrink-0">
                                
                                <!-- Tarjeta 1: +130% -->
                                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl p-4 sm:px-6 sm:py-5 shadow-sm flex flex-col items-center justify-center text-center min-w-[130px] sm:min-w-[145px]">
                                <span class="text-2xl sm:text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 tracking-tight">
                                    +130%
                                </span>
                                <span class="mt-1.5 text-[10px] sm:text-[11px] font-medium text-gray-500 dark:text-gray-400 uppercase leading-tight">
                                    CRECIMIENTO<br>ÚLTIMO MES
                                </span>
                                </div>

                                <!-- Tarjeta 2: 98% -->
                                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl p-4 sm:px-6 sm:py-5 shadow-sm flex flex-col items-center justify-center text-center min-w-[130px] sm:min-w-[145px]">
                                <span class="text-2xl sm:text-3xl font-extrabold text-blue-600 dark:text-blue-400 tracking-tight">
                                    98%
                                </span>
                                <span class="mt-1.5 text-[10px] sm:text-[11px] font-medium text-gray-500 dark:text-gray-400 uppercase leading-tight">
                                    SATISFACCIÓN
                                </span>
                                </div>

                            </div>

                            <!-- Bloque de Testimonio -->
                            <div class="flex-1 lg:border-l lg:border-gray-200/70 dark:lg:border-gray-700 lg:pl-8">
                                
                                <!-- 5 Estrellas -->
                                <div class="flex items-center gap-1 text-amber-400 mb-2.5" aria-label="Calificación de 5 de 5 estrellas">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                </div>

                                <!-- Cita -->
                                <blockquote class="text-sm sm:text-base italic text-gray-700 dark:text-gray-200 leading-relaxed mb-4">
                                "OpenDoctor ha revolucionado mi consulta. La gestión de pacientes es mucho más eficiente y mis pacientes valoran mucho poder reservar citas online."
                                </blockquote>

                                <!-- Autor -->
                                <div class="flex items-center gap-3">
                                <img 
                                    src="https://unavatar.io/github/maria" 
                                    alt="Perfil medico de profesional institucional de OpenDoctorOnline" 
                                    class="w-10 h-10 rounded-full object-cover border border-gray-200 dark:border-gray-600 shrink-0"
                                />
                                <div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white leading-snug">
                                    Natalia Chiva Montoya
                                    </div>
                                    <div class="flex flex-wrap items-center gap-x-1.5 text-[11px] font-medium tracking-wide text-gray-500 dark:text-gray-400 uppercase">
                                    <span>PSICÓLOGA</span>
                                    <span aria-hidden="true">&middot;</span>
                                    <a href="/partner-profile/institucional-opendoctoronline" class="inline-flex items-center gap-1 text-gray-500 hover:text-blue-600 dark:hover:text-blue-400 transition-colors" target="_blank">
                                        <span>VER PERFIL</span>
                                        <span aria-hidden="true">&rarr;</span>
                                    </a>
                                    </div>
                                </div>
                                </div>

                            </div>
                        </div>

                        <!-- Pie de sección -->
                        <p class="mt-6 pt-4 border-t border-gray-200/60 dark:border-gray-700/60 text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                        Únete a la plataforma de salud digital que más crece en Colombia. Profesionales verificados incorporándose cada semana.
                        </p>

                    </div>
                </section>           
            </div>

            <div class="mt-6 text-center mt-5">
                <p class="text-slate-500">¿Ya tienes una cuenta? <a href="{{ route('login') }}" class="text-blue-600 font-bold hover:underline">Inicia sesión</a></p>
            </div>
        </div>
    </div>
</x-guest-layout>
