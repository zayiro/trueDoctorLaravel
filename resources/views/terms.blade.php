<x-guest-layout>
    <div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8 mt-8">
        <div class="max-w-7xl mx-auto bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            
            <!-- Encabezado Principal Estilo Doctoralia -->
            <div class="bg-gradient-to-r from-teal-600 to-cyan-700 px-6 py-12 text-center sm:px-12">
                <span class="badge bg-teal-500/20 text-white text-xs px-3 py-1 rounded-pill mb-2 inline-block font-semibold">
                    Documento Legal SaaS
                </span>
                <h1 class="text-3xl font-extrabold text-white sm:text-4xl tracking-tight">
                    Términos y Condiciones de Uso
                </h1>
                <p class="mt-3 max-w-2xl mx-auto text-teal-100 text-sm">
                    Última actualización: {{ now()->format('d de F, Y') }}
                </p>
            </div>

            <!-- Estructura de Rejilla Fluida (Grid) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 border-t border-slate-100">
                
                <!-- Columna Izquierda: Menú de Navegación Fijo (Ocupa 3 de 12 columnas) -->
                <aside class="lg:col-span-3 bg-slate-50/60 p-6 border-b lg:border-b-0 lg:border-r border-slate-100">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Navegación rápida</p>
                    <nav class="space-y-2 sticky top-24">
                        <a href="#aceptacion" class="block px-3 py-2 text-sm font-medium text-teal-600 bg-teal-50 rounded-lg border-l-2 border-teal-600">
                            1. Aceptación del Servicio
                        </a>
                        <a href="#datos-personales" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">
                            2. Datos Personales
                        </a>
                        <a href="#historia-clinica" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">
                            3. Historia Clínica Digital
                        </a>
                        <a href="#citas" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">
                            4. Citas Presenciales y Virtuales
                        </a>
                        <a href="#responsabilidad" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">
                            5. Limitación de Responsabilidad
                        </a>
                    </nav>
                </aside>

                <!-- Columna Derecha: Contenido Expandido y Legible (Ocupa 9 de 12 columnas) -->
                <main class="lg:col-span-9 p-6 sm:p-10 lg:p-12 bg-white">
                    <div class="text-slate-700 space-y-10 text-base sm:text-lg leading-relaxed">
                        
                        <p class="text-slate-500 font-medium pb-4 border-b border-slate-100">
                            Bienvenido a nuestra plataforma SaaS de gestión de citas médicas. Antes de registrarse como paciente o profesional de la salud, por favor lea atentamente los presentes Términos y Condiciones que regulan el uso de nuestros servicios.
                        </p>

                        <!-- Sección 1 -->
                        <section id="aceptacion" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">1.</span> Aceptación de los Términos
                            </h2>
                            <p class="text-slate-600">
                                Al crear una cuenta en nuestro ecosistema, usted declara ser mayor de edad y acepta de manera expresa y sin reservas estar sujeto a este acuerdo contractual. Si no está de acuerdo con las cláusulas aquí descritas, deberá abstenerse de utilizar la plataforma y registrar cualquier tipo de cuenta.
                            </p>
                        </section>

                        <!-- Sección 2: Datos Personales -->
                        <section id="datos-personales" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">2.</span> Tratamiento de Datos Personales
                            </h2>
                            <p class="text-slate-600 mb-4">
                                Recopilamos datos esenciales de identificación, contacto y facturación con el único fin de procesar el agendamiento médico de manera oportuna y notificar los detalles correspondientes al usuario.
                            </p>
                            
                            <!-- Caja informativa destacada -->
                            <div class="bg-teal-50/50 border-l-4 border-teal-600 p-5 rounded-r-xl my-4">
                                <h4 class="text-sm font-bold uppercase tracking-wider text-teal-900 mb-1">
                                    🔒 Protección de Datos Confidenciales
                                </h4>
                                <p class="text-sm text-teal-800 leading-relaxed">
                                    Garantizamos el pleno ejercicio de sus derechos de acceso, rectificación y cancelación bajo las leyes de protección de datos vigentes. Sus canales de contacto nunca serán vendidos ni expuestos a terceros con fines comerciales o publicitarios.
                                </p>
                            </div>
                        </section>

                        <!-- Sección 3: Historia Clínica -->
                        <section id="historia-clinica" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">3.</span> Resguardo de la Historia Clínica Digital
                            </h2>
                            <p class="text-slate-600 mb-4">
                                Nuestra plataforma proporciona un entorno de software cifrado exclusivo para que los profesionales de la salud debidamente registrados diligencien y consulten los antecedentes clínicos de los pacientes.
                            </p>
                            
                            <div class="bg-slate-50 rounded-xl p-5 border border-slate-100 space-y-3">
                                <div class="d-flex align-items-start">
                                    <p class="text-sm text-slate-700">
                                        • <strong>Propiedad del dato:</strong> La información médica consignada en los registros pertenece de forma exclusiva al paciente.
                                    </p>
                                </div>
                                <div class="d-flex align-items-start">
                                    <p class="text-sm text-slate-700">
                                        • <strong>Responsabilidad médica:</strong> El doctor es el único responsable legal del diagnóstico, notas de evolución y recetas emitidas dentro del sistema.
                                    </p>
                                </div>
                                <div class="d-flex align-items-start">
                                    <p class="text-sm text-slate-700">
                                        • <strong>Cifrado y Custodia:</strong> Implementamos protocolos de seguridad avanzados para garantizar que ningún personal administrativo o externo tenga acceso a sus datos de salud.
                                    </p>
                                </div>
                            </div>
                        </section>

                        <!-- Sección 4: Citas Virtuales y Presenciales -->
                        <section id="citas" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">4.</span> Modalidades de Citas y Pagos
                            </h2>
                            <p class="text-slate-600 mb-3">
                                El SaaS permite a los usuarios agendar citas médicas tanto para atención presencial en el consultorio del especialista como para consultas virtuales por medio de nuestro sistema integrado de telemedicina en tiempo real.
                            </p>
                            <p class="text-slate-600">
                                <strong>Políticas de cancelación:</strong> Los pacientes podrán cancelar o reprogramar sus citas de forma gratuita hasta con 24 horas de anticipación. Si la cancelación se realiza fuera de ese rango, el cobro o penalización quedará sujeto a la política individual del profesional médico.
                            </p>
                        </section>

                        <!-- Sección 5 -->
                        <section id="responsabilidad" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">5.</span> Limitación de Responsabilidad
                            </h2>
                            <p class="text-slate-600">
                                Nuestro SaaS actúa estrictamente como un canal tecnológico de intermediación y agendamiento. No brindamos asesoría médica directa, no validamos diagnósticos ni formamos parte del personal de salud de los centros médicos. En caso de una emergencia de salud crítica, el paciente debe comunicarse inmediatamente con las líneas de urgencia de su localidad.
                            </p>
                        </section>

                    </div>
                </main>

            </div>
        </div>
    </div>
</x-guest-layout>
