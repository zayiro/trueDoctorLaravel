@php
    // BORRADOR para revisión de abogado. Los puntos marcados «REVISAR CON ABOGADO» dependen de criterio legal.
    $retentionDays = max(1, (int) (\App\Models\Setting::get('ai_chat_retention_days', 30) ?: 30));
@endphp
<x-guest-layout
    meta_title="Términos y Condiciones | Citas Médicas en Línea - OpenDoctor"
    meta_description="Consulta los términos legales de uso de nuestra plataforma de citas médicas en línea y servicios con IA. Condiciones de servicio, pagos, promociones y responsabilidades."
    keywords="términos y condiciones médicas, condiciones de uso citas médicas, términos legales plataforma médica, citas médicas en línea, responsabilidad médica online"
>
    <main class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8 mt-8">
        <article class="max-w-7xl mx-auto bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">

            <header class="bg-gradient-to-r from-teal-600 to-cyan-700 px-6 py-12 text-center sm:px-12">
                <span class="badge bg-teal-500/20 text-white text-xs px-3 py-1 rounded-pill mb-2 inline-block font-semibold">
                    Documento Legal SaaS
                </span>
                <h1 class="text-3xl font-extrabold text-white sm:text-4xl tracking-tight">
                    Términos y Condiciones de Uso
                </h1>
                <p class="mt-3 max-w-2xl mx-auto text-teal-100 text-sm">
                    Última actualización: 24 de noviembre de 2026
                </p>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-12 border-t border-slate-100">

                <aside class="lg:col-span-3 bg-slate-50/60 p-6 border-b lg:border-b-0 lg:border-r border-slate-100">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Navegación rápida</p>
                    <nav class="space-y-2 sticky top-24">
                        <a href="#aceptacion" class="block px-3 py-2 text-sm font-medium text-teal-600 bg-teal-50 rounded-lg border-l-2 border-teal-600">1. Aceptación del Servicio</a>
                        <a href="#datos-personales" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">2. Datos Personales</a>
                        <a href="#historia-clinica" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">3. Historia Clínica Digital</a>
                        <a href="#citas" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">4. Citas y Pagos</a>
                        <a href="#servicios-ia" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">5. Servicios con IA</a>
                        <a href="#promociones" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">6. Promociones y Códigos</a>
                        <a href="#responsabilidad" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">7. Limitación de Responsabilidad</a>
                        <a href="#cambios" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">8. Cambios y Contacto</a>
                    </nav>
                </aside>

                <section class="lg:col-span-9 p-6 sm:p-10 lg:p-12 bg-white">
                    <div class="text-slate-700 space-y-10 text-base sm:text-lg leading-relaxed">

                        <p class="text-slate-500 font-medium pb-4 border-b border-slate-100">
                            Bienvenido a nuestra plataforma SaaS de gestión de citas médicas. Antes de registrarse como paciente o profesional de la salud, por favor lea atentamente los presentes Términos y Condiciones que regulan el uso de nuestros servicios.
                        </p>

                        <section id="aceptacion" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">1.</span> Aceptación de los Términos
                            </h2>
                            <p class="text-slate-600">
                                Al crear una cuenta en nuestro ecosistema, usted declara ser mayor de edad y acepta de manera expresa estar sujeto a este acuerdo contractual. Si no está de acuerdo con las cláusulas aquí descritas, deberá abstenerse de utilizar la plataforma y registrar cualquier tipo de cuenta.
                            </p>
                            <p class="text-slate-600 mt-3">
                                Algunos servicios tienen condiciones adicionales propias, como el <a href="{{ route('ai-chat.terms') }}" class="text-teal-600 underline">Chat de Salud con IA</a>. Si hay diferencias, prevalece la condición específica de ese servicio.
                            </p>
                        </section>

                        <section id="datos-personales" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">2.</span> Tratamiento de Datos Personales
                            </h2>
                            <p class="text-slate-600 mb-4">
                                Recopilamos datos de identificación, contacto y facturación para prestar los servicios de la plataforma: agendar citas, procesar pagos, enviar notificaciones y ofrecer los servicios con inteligencia artificial que usted decida usar. El detalle de qué datos tratamos, para qué, con quién los compartimos y cómo ejercer sus derechos está en nuestra <a href="{{ route('privacy.show') }}" class="text-teal-600 underline">Política de Privacidad</a>.
                            </p>

                            <div class="bg-teal-50/50 border-l-4 border-teal-600 p-5 rounded-r-xl my-4">
                                <h4 class="text-sm font-bold uppercase tracking-wider text-teal-900 mb-1">
                                    🔒 Protección de Datos Confidenciales
                                </h4>
                                <p class="text-sm text-teal-800 leading-relaxed">
                                    Usted puede ejercer sus derechos sobre sus datos personales conforme a la Ley 1581 de 2012. Sus canales de contacto no serán vendidos ni cedidos a terceros con fines comerciales o publicitarios.
                                </p>
                            </div>
                        </section>

                        <section id="historia-clinica" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">3.</span> Resguardo de la Historia Clínica Digital
                            </h2>
                            <p class="text-slate-600 mb-4">
                                Nuestra plataforma proporciona un entorno de software para que los profesionales de la salud debidamente registrados diligencien y consulten los antecedentes clínicos de los pacientes que atienden.
                            </p>

                            <div class="bg-slate-50 rounded-xl p-5 border border-slate-100 space-y-3">
                                <p class="text-sm text-slate-700">
                                    • <strong>Propiedad del dato:</strong> La información médica consignada en los registros pertenece al paciente.
                                </p>
                                <p class="text-sm text-slate-700">
                                    • <strong>Responsabilidad médica:</strong> El doctor es el único responsable del diagnóstico, las notas de evolución y las recetas emitidas dentro del sistema.
                                </p>
                                {{-- REVISAR CON ABOGADO: la frase anterior decía que NINGÚN personal administrativo o externo accede a los datos de salud. No es exacta (hay vistas administrativas y acceso técnico). --}}
                                <p class="text-sm text-slate-700">
                                    • <strong>Seguridad y acceso:</strong> Aplicamos medidas de seguridad técnicas y organizativas razonables. El acceso del personal de OpenDoctor a información médica de los pacientes se limita a fines estrictamente técnicos y de soporte (por ejemplo, resolver una falla del servicio o atender una solicitud del propio paciente), y solo cuando es necesario.
                                </p>
                            </div>
                        </section>

                        <section id="citas" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">4.</span> Modalidades de Citas y Pagos
                            </h2>
                            <p class="text-slate-600 mb-3">
                                El SaaS permite a los usuarios agendar citas médicas tanto para atención presencial en el consultorio del especialista como para consultas virtuales por medio de nuestro sistema integrado de telemedicina.
                            </p>
                            <p class="text-slate-600 mb-3">
                                <strong>Pagos:</strong> Los pagos en línea se procesan a través de Wompi. Antes de pagar, usted ve el desglose del valor: el valor del servicio y la comisión de la plataforma. Los datos de su tarjeta los trata la pasarela de pagos, no OpenDoctor.
                            </p>
                            <p class="text-slate-600">
                                <strong>Políticas de cancelación:</strong> Los pacientes podrán cancelar o reprogramar sus citas de forma gratuita hasta con 24 horas de anticipación. Si la cancelación se realiza fuera de ese rango, el cobro o penalización quedará sujeto a la política individual del profesional médico.
                            </p>
                        </section>

                        <section id="servicios-ia" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">5.</span> Servicios con Inteligencia Artificial
                            </h2>
                            <p class="text-slate-600 mb-3">
                                La plataforma ofrece servicios apoyados en inteligencia artificial, como el análisis de exámenes, el análisis de piel, la sugerencia de especialidades y el <a href="{{ route('ai-chat.landing') }}" class="text-teal-600 underline">Chat de Salud con IA</a>.
                            </p>
                            <div class="bg-slate-50 rounded-xl p-5 border border-slate-100 space-y-3">
                                <p class="text-sm text-slate-700">
                                    • <strong>Son orientativos:</strong> sus resultados son informativos. No constituyen diagnóstico, formulación ni consulta médica, y no reemplazan a un profesional de la salud.
                                </p>
                                <p class="text-sm text-slate-700">
                                    • <strong>Proveedores externos:</strong> para generar los resultados, el contenido que usted envía se procesa con proveedores externos de modelos de inteligencia artificial. Evite incluir datos que no desee compartir.
                                </p>
                                <p class="text-sm text-slate-700">
                                    • <strong>Chat de Salud con IA:</strong> tiene sesiones de tiempo limitado, plazo de vencimiento, política de pagos y conservación de la conversación por {{ $retentionDays }} días. Todo está en sus <a href="{{ route('ai-chat.terms') }}" class="text-teal-600 underline">términos específicos</a>.
                                </p>
                                <p class="text-sm text-slate-700">
                                    • <strong>Emergencias:</strong> ante una urgencia, no use estos servicios: llame a la línea 123 o acuda al servicio de urgencias más cercano.
                                </p>
                            </div>
                        </section>

                        <section id="promociones" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">6.</span> Promociones y Códigos de Descuento
                            </h2>
                            {{-- REVISAR CON ABOGADO: condiciones de promociones y facultad de desactivar códigos por uso indebido. --}}
                            <ul class="list-disc list-inside text-slate-600 space-y-2 text-base pl-2">
                                <li>El código de bienvenida es <strong>personal e intransferible</strong>, de un solo uso, y aplica solo a los servicios que se indiquen en él.</li>
                                <li>Para verlo y usarlo debe <strong>confirmar su correo electrónico</strong>.</li>
                                <li>Un código solo puede usarlo la cuenta a la que pertenece.</li>
                                <li>OpenDoctor puede desactivar códigos y suspender cuentas cuando detecte uso indebido, como la creación de cuentas múltiples para obtener descuentos.</li>
                            </ul>
                        </section>

                        <section id="responsabilidad" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">7.</span> Limitación de Responsabilidad
                            </h2>
                            {{-- REVISAR CON ABOGADO: alcance de la limitación de responsabilidad. --}}
                            <p class="text-slate-600">
                                Nuestro SaaS actúa como un canal tecnológico de intermediación y agendamiento. No brindamos asesoría médica directa por medio de las citas, no validamos diagnósticos ni formamos parte del personal de salud de los centros médicos. Los servicios con inteligencia artificial pueden contener errores u omisiones y no reemplazan la valoración de un profesional. En caso de una emergencia de salud, el paciente debe comunicarse inmediatamente con las líneas de urgencia de su localidad.
                            </p>
                        </section>

                        <section id="cambios" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">8.</span> Cambios y Contacto
                            </h2>
                            {{-- REVISAR CON ABOGADO: ley aplicable y jurisdicción. --}}
                            <p class="text-slate-600">
                                Podemos actualizar estos términos; la versión vigente es la publicada en esta página. Estos términos se rigen por las leyes de la República de Colombia. Para dudas o solicitudes, escríbanos a <a href="mailto:support@opendoctor.online" class="text-teal-600 underline">support@opendoctor.online</a>.
                            </p>
                        </section>

                    </div>
                </section>

            </div>
        </article>
    </main>
</x-guest-layout>
