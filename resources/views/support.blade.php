<x-guest-layout>
    <div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8 mt-8" x-data="{ activeTab: 'pacientes' }">
        <div class="max-w-7xl mx-auto bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            
            <!-- Encabezado Principal Estilo Doctoralia -->
            <div class="bg-gradient-to-r from-teal-600 to-cyan-700 px-6 py-12 text-center sm:px-12">
                <span class="badge bg-teal-500/20 text-white text-xs px-3 py-1 rounded-pill mb-2 inline-block font-semibold">
                    Centro de Ayuda
                </span>
                <h1 class="text-3xl font-extrabold text-white sm:text-4xl tracking-tight">
                    ¿En qué podemos ayudarte hoy?
                </h1>
                <p class="mt-3 max-w-2xl mx-auto text-teal-100 text-sm">
                    Soporte técnico técnico especializado para Pacientes y Profesionales de la Salud.
                </p>
            </div>

            <!-- Estructura de Rejilla de Soporte (Grid) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 border-t border-slate-100">
                
                <!-- Columna Izquierda: Categorías de Soporte (3 columnas) -->
                <aside class="lg:col-span-3 bg-slate-50/60 p-6 border-b lg:border-b-0 lg:border-r border-slate-100">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Temas de ayuda</p>
                    <nav class="space-y-2 sticky top-24">
                        <button @click="activeTab = 'pacientes'" :class="activeTab === 'pacientes' ? 'text-teal-600 bg-teal-50 border-teal-600' : 'text-slate-600 border-transparent hover:bg-slate-100/50'" class="w-full text-left block px-3 py-2 text-sm font-medium rounded-lg border-l-2 transition-all">
                            🙋‍♂️ Centro para Pacientes
                        </button>
                        <button @click="activeTab = 'doctores'" :class="activeTab === 'doctores' ? 'text-teal-600 bg-teal-50 border-teal-600' : 'text-slate-600 border-transparent hover:bg-slate-100/50'" class="w-full text-left block px-3 py-2 text-sm font-medium rounded-lg border-l-2 transition-all">
                            🩺 Centro para Doctores
                        </button>
                        <button @click="activeTab = 'ticket'" :class="activeTab === 'ticket' ? 'text-teal-600 bg-teal-50 border-teal-600' : 'text-slate-600 border-transparent hover:bg-slate-100/50'" class="w-full text-left block px-3 py-2 text-sm font-medium rounded-lg border-l-2 transition-all">
                            📩 Crear Ticket de Soporte
                        </button>
                    </nav>
                </aside>

                <!-- Columna Derecha: Contenido Dinámico según la pestaña (9 columnas) -->
                <main class="lg:col-span-9 p-6 sm:p-10 lg:p-12 bg-white">
                    
                    <!-- PESTAÑA: PACIENTES -->
                    <div x-show="activeTab === 'pacientes'" x-transition>
                        <h2 class="text-2xl font-bold text-slate-900 mb-2">Ayuda para Pacientes</h2>
                        <p class="text-slate-500 mb-6 small">Encuentra respuestas rápidas para gestionar tus citas y consultas virtuales.</p>
                        
                        <div class="space-y-4">
                            <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/50">
                                <h4 class="font-semibold text-slate-800 text-base">¿Cómo ingreso a mi videocita de telemedicina?</h4>
                                <p class="text-slate-600 text-sm mt-1">Dirígete a tu panel de usuario en la sección 'Mis Citas'. 10 minutos antes de la hora programada se habilitará un botón verde que dice 'Ingresar a la videollamada'. No necesitas instalar software adicional.</p>
                            </div>
                            <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/50">
                                <h4 class="font-semibold text-slate-800 text-base">¿Puedo cancelar o reprogramar una cita?</h4>
                                <p class="text-slate-600 text-sm mt-1">Sí, puedes hacerlo desde tu panel de control con un mínimo de 24 horas de anticipación. Pasado ese tiempo, el sistema congelará la agenda y deberás comunicarte directamente con el consultorio del médico.</p>
                            </div>
                            <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/50">
                                <h4 class="font-semibold text-slate-800 text-base">¿Dónde descargo mi receta médica o historia clínica?</h4>
                                <p class="text-slate-600 text-sm mt-1">Una vez que el médico finalice y cierre tu consulta, recibirás un correo electrónico de notificación. Podrás descargar los archivos en formato PDF ingresando a tu sección de 'Historial Clínico'.</p>
                            </div>
                        </div>
                    </div>

                    <!-- PESTAÑA: DOCTORES -->
                    <div x-show="activeTab === 'doctores'" x-transition>
                        <h2 class="text-2xl font-bold text-slate-900 mb-2">Ayuda para Profesionales Médicos</h2>
                        <p class="text-slate-500 mb-6 small">Optimiza la gestión de tu consultorio virtual y resuelve dudas de configuración.</p>
                        
                        <div class="space-y-4">
                            <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/50">
                                <h4 class="font-semibold text-slate-800 text-base">¿Cómo configuro u oculto mis horarios de atención?</h4>
                                <p class="text-slate-600 text-sm mt-1">Ingresa a tu 'Panel Médico' > 'Configuración de Agenda'. Allí podrás marcar los días de la semana disponibles, las horas de inicio y fin, y bloquear días específicos por vacaciones o emergencias.</p>
                            </div>
                            <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/50">
                                <h4 class="font-semibold text-slate-800 text-base">¿Por qué mi perfil aparece en estado 'Pendiente de Validación'?</h4>
                                <p class="text-slate-600 text-sm mt-1">Para garantizar la seguridad de la plataforma, nuestro equipo legal valida manualmente tu licencia y tarjeta profesional. Este proceso toma entre 12 y 24 horas hábiles. Recibirás un correo cuando seas aprobado.</p>
                            </div>
                            <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/50">
                                <h4 class="font-semibold text-slate-800 text-base">¿Cómo se procesan los pagos de las videocitas virtuales?</h4>
                                <p class="text-slate-600 text-sm mt-1">El dinero pagado por el paciente se retiene de forma segura en la pasarela de pagos del SaaS y se dispersa automáticamente a tu cuenta bancaria registrada todos los viernes hábiles, descontando la comisión de la plataforma.</p>
                            </div>
                        </div>
                    </div>

                    <!-- PESTAÑA: FORMULARIO DE CONTACTO / TICKET -->
                    <div x-show="activeTab === 'ticket'" x-transition>
                        <h2 class="text-2xl font-bold text-slate-900 mb-2">Enviar una Solicitud de Soporte</h2>
                        <p class="text-slate-500 mb-6 small">¿No encontraste la respuesta? Nuestro equipo técnico te responderá en menos de 4 horas.</p>
                        
                        <!-- Reutilizamos de forma inteligente la lógica del formulario de contacto que ya tienes creado -->
                        <form action="{{ route('contact.submit') }}" method="POST" class="space-y-4">
                            @csrf
                            <input type="hidden" name="subject" value="Ticket de Soporte Técnico Especializado">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <x-label for="name" value="Tu Nombre completo" />
                                    <x-input id="name" name="name" type="text" class="block mt-1 w-full" required />
                                </div>
                                <div>
                                    <x-label for="email" value="Tu Correo Registrado" />
                                    <x-input id="email" name="email" type="email" class="block mt-1 w-full" required />
                                </div>
                            </div>

                            <div>
                                <x-label for="message" value="Describe detalladamente el problema técnico o error" />
                                <textarea name="message" id="message" rows="5" class="border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm block mt-1 w-full text-sm" required minlength="10" placeholder="Ej: No logro encender la cámara web dentro de la videollamada con mi paciente..."></textarea>
                            </div>

                            <div class="flex justify-end pt-2">
                                <x-button class="bg-teal-600 hover:bg-teal-700">
                                    🚀 Enviar Ticket de Soporte
                                </x-button>
                            </div>
                        </form>
                    </div>

                </main>

            </div>
        </div>
    </div>
</x-guest-layout>
