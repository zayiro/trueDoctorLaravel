@php
    // BORRADOR para revisión de abogado. Los puntos marcados «REVISAR CON ABOGADO» dependen de criterio legal.
    $retentionDays = max(1, (int) (\App\Models\Setting::get('ai_chat_retention_days', 30) ?: 30));
@endphp
<x-guest-layout
    meta_title="Política de Privacidad | Citas Médicas en Línea - OpenDoctor"
    meta_description="Cómo OpenDoctor trata tus datos personales y de salud conforme a la Ley 1581 de 2012: qué recolectamos, para qué, con quién los compartimos y cómo ejercer tus derechos."
    keywords="política de privacidad médica, protección de datos de salud, habeas data, Ley 1581 de 2012, citas médicas en línea, confidencialidad médica, privacidad datos salud"
>
    <main class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8 mt-8">
        <article class="max-w-7xl mx-auto bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">

            <header class="bg-gradient-to-r from-teal-600 to-cyan-700 px-6 py-12 text-center sm:px-12">
                <span class="badge bg-teal-500/20 text-white text-xs px-3 py-1 rounded-pill mb-2 inline-block font-semibold">
                    Privacidad y Confidencialidad
                </span>
                <h1 class="text-3xl font-extrabold text-white sm:text-4xl tracking-tight">
                    Política de Privacidad
                </h1>
                <p class="mt-3 max-w-2xl mx-auto text-teal-100 text-sm">
                    Última actualización: 24 de noviembre de 2026
                </p>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-12 border-t border-slate-100">

                <aside class="lg:col-span-3 bg-slate-50/60 p-6 border-b lg:border-b-0 lg:border-r border-slate-100">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Navegación rápida</p>
                    <nav class="space-y-2 sticky top-24">
                        <a href="#responsable" class="block px-3 py-2 text-sm font-medium text-teal-600 bg-teal-50 rounded-lg border-l-2 border-teal-600">1. Responsable del Tratamiento</a>
                        <a href="#datos-recolectados" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">2. Datos que Recolectamos</a>
                        <a href="#datos-sensibles" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">3. Datos de Salud Sensibles</a>
                        <a href="#uso-informacion" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">4. Uso de la Información</a>
                        <a href="#chat-ia" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">5. Chat de Salud con IA</a>
                        <a href="#terceros" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">6. Terceros y Transferencia</a>
                        <a href="#conservacion" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">7. Conservación</a>
                        <a href="#derechos" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">8. Sus Derechos</a>
                    </nav>
                </aside>

                <section class="lg:col-span-9 p-6 sm:p-10 lg:p-12 bg-white">
                    <div class="text-slate-700 space-y-10 text-base sm:text-lg leading-relaxed">

                        <p class="text-slate-500 font-medium pb-4 border-b border-slate-100">
                            Para nuestra plataforma SaaS, la privacidad de los pacientes y de los profesionales de la salud es una prioridad. Este documento explica cómo recopilamos, protegemos, almacenamos y tratamos su información personal, conforme a la Ley 1581 de 2012 y sus normas reglamentarias.
                        </p>

                        <section id="responsable" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">1.</span> Responsable del Tratamiento
                            </h2>
                            {{-- REVISAR CON ABOGADO: la Ley 1581 pide identificar al responsable con razón social/nombre, domicilio, correo y teléfono. Completar aquí NIT/razón social, dirección y teléfono. --}}
                            <p class="text-slate-600">
                                El responsable del tratamiento de sus datos personales recolectados a través de esta plataforma es <strong>OpenDoctorOnline</strong>. Para consultas, reclamos o solicitudes sobre sus datos puede escribirnos a <a href="mailto:support@opendoctor.online" class="text-teal-600 underline">support@opendoctor.online</a>.
                            </p>
                        </section>

                        <section id="datos-recolectados" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">2.</span> Datos que Recolectamos
                            </h2>
                            <p class="text-slate-600 mb-3">
                                Dependiendo de su perfil de usuario y de los servicios que use, tratamos los siguientes datos:
                            </p>
                            <ul class="list-disc list-inside text-slate-600 space-y-2 text-sm pl-2">
                                <li><strong>Pacientes:</strong> nombre completo, documento de identidad, correo electrónico, número telefónico, fecha de nacimiento y género.</li>
                                <li><strong>Médicos y profesionales:</strong> nombre completo, tarjeta o licencia profesional verificable, especialidad, dirección de consulta, teléfono y datos bancarios o fiscales de facturación.</li>
                                <li><strong>Pagos:</strong> el valor, la fecha y el estado de cada transacción. Los datos de su tarjeta los trata la pasarela de pagos (Wompi), no OpenDoctor.</li>
                                <li><strong>Servicios con IA:</strong> el contenido que usted escribe o carga (síntomas, mensajes del chat, documentos o fotografías) y, si usted lo autoriza, información mínima de su perfil de salud.</li>
                                <li><strong>Cuenta y seguridad:</strong> confirmación del correo electrónico y registros técnicos necesarios para operar y proteger el servicio.</li>
                            </ul>
                        </section>

                        <section id="datos-sensibles" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">3.</span> Tratamiento Especial de Datos de Salud
                            </h2>
                            <p class="text-slate-600 mb-4">
                                Los datos de salud (antecedentes médicos, diagnósticos, síntomas descritos a la IA, recetas y notas de evolución) son <strong>datos sensibles</strong>. Su tratamiento es voluntario: usted no está obligado a autorizarlo, y solo los usamos para prestarle los servicios que solicita.
                            </p>

                            <div class="bg-teal-50/50 border-l-4 border-teal-600 p-5 rounded-r-xl my-4">
                                <h4 class="text-sm font-bold uppercase tracking-wider text-teal-900 mb-1">
                                    🛡️ Protocolo de Confidencialidad
                                </h4>
                                {{-- REVISAR CON ABOGADO: antes decía «cifrado en reposo y en tránsito» y «el personal tiene prohibido por diseño y contrato acceder». No se puede sostener tal cual. Ajustar a lo que se pueda demostrar. --}}
                                <p class="text-sm text-teal-800 leading-relaxed">
                                    Aplicamos medidas de seguridad técnicas y organizativas razonables para proteger su información, incluida la conexión cifrada en tránsito. La historia clínica digital es accesible para el paciente titular y para el médico con quien se agende la cita. El acceso del personal de OpenDoctor a información médica de los pacientes se limita a fines estrictamente técnicos y de soporte (por ejemplo, resolver una falla del servicio o atender una solicitud del propio paciente), y solo cuando es necesario.
                                </p>
                            </div>
                        </section>

                        <section id="uso-informacion" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">4.</span> Finalidad del Uso de los Datos
                            </h2>
                            <p class="text-slate-600 mb-3">
                                Utilizamos su información para los siguientes fines:
                            </p>
                            <ul class="list-none text-slate-600 space-y-2 text-sm">
                                <li>📌 Confirmar, reprogramar, cancelar y gestionar citas presenciales o virtuales.</li>
                                <li>🤖 Procesar con inteligencia artificial la información que usted envía voluntariamente, para sugerir una especialidad, analizar exámenes o piel, o mantener una conversación de orientación y generar su informe.</li>
                                <li>💳 Procesar pagos y aplicar códigos promocionales.</li>
                                <li>🔔 Enviar recordatorios y avisos por correo electrónico o mensaje de texto (por ejemplo, el vencimiento de una sesión).</li>
                                <li>🔐 Verificar su identidad y su correo, y prevenir el uso indebido del servicio.</li>
                            </ul>
                        </section>

                        <section id="chat-ia" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">5.</span> Chat de Salud con IA
                            </h2>
                            <div class="bg-slate-50 rounded-xl p-5 border border-slate-100 space-y-3">
                                <p class="text-sm text-slate-700">• <strong>Qué se envía a la IA:</strong> lo que usted escribe en la conversación y, solo si lo autoriza al comprar, información mínima de su perfil (edad, sexo, alergias, condiciones y medicamentos).</p>
                                <p class="text-sm text-slate-700">• <strong>Qué no enviamos:</strong> OpenDoctor no envía a la IA su nombre registrado, documento, teléfono ni correo. Si usted escribe esos datos en la conversación, sí llegarían al proveedor. Le recomendamos no escribirlos.</p>
                                <p class="text-sm text-slate-700">• <strong>Acceso a su sesión:</strong> para ver su conversación e informe se le pide validar su identificación.</p>
                                <p class="text-sm text-slate-700">• <strong>Informe y copia:</strong> al terminar recibe un informe en pantalla y por correo. Si lo marca, también una copia completa de la conversación.</p>
                                <p class="text-sm text-slate-700">• <strong>Conservación:</strong> la conversación y el informe se conservan {{ $retentionDays }} días después de terminar la sesión y luego se eliminan de forma definitiva. Puede descargar su copia antes.</p>
                            </div>
                            <p class="text-slate-600 mt-3">
                                Las condiciones del servicio están en los <a href="{{ route('ai-chat.terms') }}" class="text-teal-600 underline">términos del Chat de Salud con IA</a>.
                            </p>
                        </section>

                        <section id="terceros" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">6.</span> Terceros que Participan y Transferencia de Datos
                            </h2>
                            {{-- REVISAR CON ABOGADO: transferencia/transmisión internacional de datos (Ley 1581, art. 26), contratos de transmisión con encargados y condiciones de cada proveedor de IA sobre uso de datos. --}}
                            <p class="text-slate-600 mb-3">
                                Para prestar el servicio nos apoyamos en proveedores que tratan datos por nuestra cuenta, solo para los fines descritos:
                            </p>
                            <ul class="list-disc list-inside text-slate-600 space-y-2 text-sm pl-2">
                                <li>Procesamiento de pagos: <strong>Wompi</strong>.</li>
                                <li>Videollamadas de telemedicina: <strong>Zoom</strong>.</li>
                                <li>Mensajes de texto: <strong>Twilio</strong>.</li>
                                <li>Modelos de inteligencia artificial: proveedores externos de modelos de IA.</li>
                                <li>Correo electrónico e infraestructura en la nube.</li>
                            </ul>
                            <p class="text-slate-600 mt-3">
                                Algunos de estos proveedores pueden procesar información fuera de Colombia. No vendemos sus datos ni los cedemos con fines comerciales o publicitarios.
                            </p>
                        </section>

                        <section id="conservacion" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">7.</span> Conservación de los Datos
                            </h2>
                            {{-- REVISAR CON ABOGADO: plazos de conservación de historia clínica según normativa colombiana y de datos contables/tributarios. --}}
                            <ul class="list-disc list-inside text-slate-600 space-y-2 text-sm pl-2">
                                <li><strong>Chat de Salud con IA:</strong> {{ $retentionDays }} días después de terminar la sesión.</li>
                                <li><strong>Historia clínica:</strong> durante los plazos que establezca la normativa colombiana aplicable a los profesionales de la salud.</li>
                                <li><strong>Datos de cuenta y de pagos:</strong> mientras la cuenta esté activa y por el tiempo que exijan las obligaciones legales, contables y tributarias.</li>
                            </ul>
                        </section>

                        <section id="derechos" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">8.</span> Sus Derechos como Titular
                            </h2>
                            {{-- REVISAR CON ABOGADO: derechos de la Ley 1581 (art. 8), plazos de respuesta (arts. 14 y 15) y procedimiento de reclamos. Se reemplazó «ARCO» por los derechos de habeas data colombiano. --}}
                            <p class="text-slate-600 mb-3">
                                Conforme a la Ley 1581 de 2012, usted puede:
                            </p>
                            <ul class="list-disc list-inside text-slate-600 space-y-2 text-sm pl-2">
                                <li>Conocer, actualizar y rectificar sus datos.</li>
                                <li>Solicitar prueba de la autorización que nos dio.</li>
                                <li>Ser informado sobre el uso que damos a sus datos.</li>
                                <li>Revocar la autorización y solicitar la supresión de sus datos, siempre que no exista un deber legal o contractual de conservarlos, como el resguardo de la historia clínica por parte del profesional de la salud.</li>
                                <li>Presentar quejas ante la Superintendencia de Industria y Comercio.</li>
                            </ul>
                            <p class="text-slate-600 mt-3">
                                Para ejercer estos derechos escríbanos a <a href="mailto:support@opendoctor.online" class="text-teal-600 underline">support@opendoctor.online</a>, indicando su nombre, su documento y lo que solicita.
                            </p>
                        </section>

                    </div>
                </section>

            </div>
        </article>
    </main>
</x-guest-layout>
