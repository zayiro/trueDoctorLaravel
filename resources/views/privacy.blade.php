<x-guest-layout>
    <div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8 mt-8">
        <div class="max-w-7xl mx-auto bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            
            <!-- Encabezado Principal -->
            <div class="bg-gradient-to-r from-teal-600 to-cyan-700 px-6 py-12 text-center sm:px-12">
                <span class="badge bg-teal-500/20 text-white text-xs px-3 py-1 rounded-pill mb-2 inline-block font-semibold">
                    Privacidad y Confidencialidad
                </span>
                <h1 class="text-3xl font-extrabold text-white sm:text-4xl tracking-tight">
                    Política de Privacidad
                </h1>
                <p class="mt-3 max-w-2xl mx-auto text-teal-100 text-sm">
                    Última actualización: {{ now()->format('d de F, Y') }}
                </p>
            </div>

            <!-- Estructura de Rejilla Fluida (Grid) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 border-t border-slate-100">
                
                <!-- Columna Izquierda: Menú de Navegación Fijo (3 columnas) -->
                <aside class="lg:col-span-3 bg-slate-50/60 p-6 border-b lg:border-b-0 lg:border-r border-slate-100">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Navegación rápida</p>
                    <nav class="space-y-2 sticky top-24">
                        <a href="#responsable" class="block px-3 py-2 text-sm font-medium text-teal-600 bg-teal-50 rounded-lg border-l-2 border-teal-600">
                            1. Responsable del Tratamiento
                        </a>
                        <a href="#datos-recolectados" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">
                            2. Datos que Recolectamos
                        </a>
                        <a href="#datos-sensibles" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">
                            3. Datos de Salud Sensibles
                        </a>
                        <a href="#uso-informacion" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">
                            4. Uso de la Información
                        </a>
                        <a href="#derechos-arco" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:text-teal-600 hover:bg-slate-100/50 rounded-lg border-l-2 border-transparent transition-all">
                            5. Sus Derechos (ARCO)
                        </a>
                    </nav>
                </aside>

                <!-- Columna Derecha: Contenido Expandido (9 columnas) -->
                <main class="lg:col-span-9 p-6 sm:p-10 lg:p-12 bg-white">
                    <div class="text-slate-700 space-y-10 text-base sm:text-lg leading-relaxed">
                        
                        <p class="text-slate-500 font-medium pb-4 border-b border-slate-100">
                            Para nuestra plataforma SaaS, la privacidad de los pacientes y los profesionales de la salud es nuestra máxima prioridad. Este documento detalla de forma clara cómo recopilamos, protegemos, almacenamos y procesamos su información personal.
                        </p>

                        <!-- Sección 1: Responsable -->
                        <section id="responsable" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">1.</span> Responsable del Tratamiento
                            </h2>
                            <p class="text-slate-600">
                                La entidad legal responsable del tratamiento de sus datos personales recolectados a través de este ecosistema de agendamiento médico es **[Nombre Legal de tu SaaS o Empresa]**, con domicilio principal y correo electrónico de contacto de privacidad gestionado en: `privacidad@tudominio.com`.
                            </p>
                        </section>

                        <!-- Sección 2: Datos Recolectados -->
                        <section id="datos-recolectados" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">2.</span> Datos que Recolectamos
                            </h2>
                            <p class="text-slate-600 mb-3">
                                Dependiendo de su perfil de usuario (Paciente o Doctor), procesamos los siguientes datos de carácter general indispensables para la operación del software:
                            </p>
                            <ul class="list-disc list-inside text-slate-600 space-y-2 text-sm pl-2">
                                <li><strong>Pacientes:</strong> Nombre completo, documento de identidad, correo electrónico, número telefónico, fecha de nacimiento y género.</li>
                                <li><strong>Médicos / Profesionales:</strong> Nombre completo, cédula profesional o licencia médica verificable, especialidad, dirección física de consulta, número de teléfono y datos bancarios o fiscales de facturación.</li>
                            </ul>
                        </section>

                        <!-- Sección 3: Datos de Salud Sensibles (Blindaje Médico Crítico) -->
                        <section id="datos-sensibles" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">3.</span> Tratamiento Especial de Datos de Salud
                            </h2>
                            <p class="text-slate-600 mb-4">
                                Los datos contenidos en el módulo de **Historia Clínica Digital** (antecedentes médicos, diagnósticos, síntomas descritos en el triage de la IA, recetas y notas de evolución) son clasificados legalmente como **Datos Sensibles**.
                            </p>
                            
                            <!-- Caja destacada estilo Doctoralia -->
                            <div class="bg-teal-50/50 border-l-4 border-teal-600 p-5 rounded-r-xl my-4">
                                <h4 class="text-sm font-bold uppercase tracking-wider text-teal-900 mb-1">
                                    🛡️ Protocolo de Confidencialidad Médica
                                </h4>
                                <p class="text-sm text-teal-800 leading-relaxed">
                                    Los datos de la historia clínica digital están estrictamente protegidos mediante cifrado en reposo y en tránsito. El personal operativo, técnico o administrativo de este SaaS **tiene prohibido por diseño y contrato acceder a la información de salud de los pacientes**. Dicha información solo es accesible para el paciente titular y el médico autorizado con quien se agende la cita.
                                </p>
                            </div>
                        </section>

                        <!-- Sección 4: Uso de la Información -->
                        <section id="uso-informacion" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">4.</span> Finalidad del Uso de los Datos
                            </h2>
                            <p class="text-slate-600 mb-3">
                                Utilizamos su información estrictamente para los siguientes fines operativos del negocio:
                            </p>
                            <ul class="list-none text-slate-600 space-y-2 text-sm">
                                <li>📌 Confirmar, reprogramar, cancelar y gestionar la agenda de citas presenciales o virtuales.</li>
                                <li>🤖 Procesar mediante Inteligencia Artificial los síntomas ingresados voluntariamente para sugerir la rama médica adecuada.</li>
                                <li>🔔 Enviar notificaciones de recordatorio automáticas para evitar inasistencias a los consultorios.</li>
                            </ul>
                        </section>

                        <!-- Sección 5: Derechos ARCO -->
                        <section id="derechos-arco" class="scroll-mt-28">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <span class="text-teal-600 me-2">5. Sus Derechos de Control (ARCO)</span>
                            </h2>
                            <p class="text-slate-600">
                                Usted mantiene en todo momento el control absoluto sobre sus datos personales. Puede ejercer sus derechos de **Acceso, Rectificación, Cancelación y Oposición** enviando un correo firmado a `privacidad@tudominio.com`. El paciente puede solicitar la portabilidad o eliminación total de su cuenta y registros, siempre y cuando no exista una obligación legal o normativa médica local que exija el resguardo temporal de los expedientes clínicos por parte del profesional de la salud.
                            </p>
                        </section>

                    </div>
                </main>

            </div>
        </div>
    </div>
</x-guest-layout>
