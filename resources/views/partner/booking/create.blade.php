@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Agendar Cita Médica']
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="py-8 px-4 max-w-7xl mx-auto">
        <!-- ALERTAS DE ÉXITO O ERROR -->
        @if (session('success'))
            <div id="alert-success" class="flex items-center p-4 mb-4 text-green-800 rounded-2xl bg-green-50 border border-green-100 shadow-sm transition-opacity duration-500" role="alert">
                <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://w3.org" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L8 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                </svg>
                <div class="ms-3 text-sm font-medium">
                    {{ session('success') }}
                </div>
                <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8" onclick="document.getElementById('alert-success').remove()">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://w3.org" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div class="flex items-center p-4 mb-4 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm" role="alert">
                <div class="text-sm font-medium">{{ session('error') }}</div>
            </div>
        @endif

        <section class="relative bg-white overflow-hidden" 
            x-data="{ 
                currentStep: {{ session('appointment_flow.current_step', 1) }}, 
                loading: false, 
                patientFound: {{ session('appointment_flow.patient_found') === true ? 'true' : (session('appointment_flow.patient_found') === false ? 'false' : 'null') }},
                searchId: '{{ session('appointment_flow.search_id', '') }}',
                patientData: { 
                    name: '{{ session('appointment_flow.patient.name', '') }}', 
                    email: '{{ session('appointment_flow.patient.email', '') }}', 
                    phone: '{{ session('appointment_flow.patient.phone', '') }}', 
                    identification: '{{ session('appointment_flow.patient.identification', '') }}' 
                },
                appointmentData: { 
                    branch: '{{ session('appointment_flow.appointment.branch', '') }}', 
                    service: '{{ session('appointment_flow.appointment.service', '') }}', 
                    date: '{{ session('appointment_flow.appointment.date', '') }}', 
                    time: '{{ session('appointment_flow.appointment.time', '') }}', 
                    paymentMethod: '{{ session('appointment_flow.appointment.payment_method', 'online') }}' 
                },
                saveField(group, key, value) {
                    // Envía los cambios al servidor de forma asíncrona usando la ruta unificada del Partner
                    fetch('{{ route('partner.booking.session.update') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ group: group, key: key, value: value })
                    });
                }
            }">
            
            <!-- Destellos de fondo orgánicos estilo Apple Health -->
            <div class="absolute inset-0 pointer-events-none opacity-40">
                <div class="absolute top-[-5%] right-[-5%] w-[400px] h-[400px] bg-blue-200/20 rounded-full blur-[100px]"></div>
                <div class="absolute bottom-[5%] left-[-5%] w-[450px] h-[450px] bg-emerald-200/20 rounded-full blur-[110px]"></div>
            </div>

            <div class="relative mx-auto z-10">
                <!-- Contenedor Principal Flotante -->
                <div class="bg-slate-50/90 backdrop-blur-xl rounded-3xl p-6 sm:p-10 border border-slate-200/80 shadow-[0_8px_30px_rgb(0,0,0,0.02)] min-h-[500px] flex flex-col justify-between">
                    
                    <!-- Barra de Progreso Superior Indicativa -->
                    <div class="w-full flex items-center justify-between mb-8 pb-4 border-b border-slate-200/60 text-xs font-bold text-slate-400 tracking-wider uppercase">
                        <span :class="currentStep === 1 ? 'text-blue-600' : 'text-slate-400 transition-colors'">1. Identificación del Paciente</span>
                        <div class="h-[2px] bg-slate-200 flex-1 mx-4 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-600 transition-all duration-500 ease-out" :style="'width: ' + (currentStep === 1 ? '50%' : '100%')"></div>
                        </div>
                        <span :class="currentStep === 2 ? 'text-blue-600' : 'text-slate-400 transition-colors'">2. Detalles de Agendamiento</span>
                    </div>

                    <!-- ENTORNO DE PASOS -->
                    <div class="relative flex-1">
                        <!-- PASO 1: Identificación y Registro del Paciente -->
                        <div x-show="currentStep === 1" 
                            x-transition:enter="transition ease-out duration-300 transform"
                            x-transition:enter-start="opacity-0 translate-x-[-20px]"
                            x-transition:enter-end="opacity-100 translate-x-0"
                            class="space-y-6">
                            
                            <div class="space-y-1">
                                <h3 class="text-xl font-extrabold text-slate-900 tracking-tight">Buscar o Registrar Paciente</h3>
                                <p class="text-sm text-slate-500">Introduce el documento de identidad para verificar el perfil en OpenDoctor.</p>
                            </div>

                            <!-- Buscador por Identificación -->
                            <div class="flex flex-col sm:flex-row gap-3">
                                <div class="relative flex-1">
                                    <input type="text" x-model="searchId" @input="saveField('root', 'search_id', searchId)" placeholder="Ej. 10402030" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition-all text-sm font-medium">
                                </div>
                                <button @click="
                                    loading = true;
                                    // Simulación de búsqueda en base de datos
                                    setTimeout(() => { 
                                        loading = false; 
                                        if(searchId === '123') { 
                                            patientFound = true;
                                            patientData = { name: 'Carlos Mendoza', email: 'carlos@mail.com', countryCode: '+57', phone: '3001234567', identification: searchId };
                                        } else { 
                                            patientFound = false;
                                            patientData = { name: '', email: '', countryCode: '+57', phone: '', identification: searchId };
                                        }
                                        saveField('root', 'patient_found', patientFound);
                                        saveField('patient', 'all', patientData);
                                    }, 800);
                                " class="bg-slate-900 hover:bg-slate-800 text-white font-semibold px-6 py-3 rounded-xl transition-all duration-200 flex items-center justify-center gap-2 text-sm shadow-sm active:scale-[0.98]">
                                    <svg x-show="loading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span x-text="loading ? 'Buscando...' : 'Verificar Identificación'"></span>
                                </button>
                            </div>

                            <!-- SUB-PASO A: Paciente Encontrado Exitosamente -->
                            <div x-show="patientFound === true" x-transition class="bg-emerald-50 border border-emerald-200/60 p-4 rounded-xl flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-emerald-500/10 text-emerald-600 rounded-lg">
                                        <svg xmlns="http://w3.org" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0110 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043a3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800" x-text="patientData.name"></p>
                                        <p class="text-xs text-slate-500" x-text="'Paciente verificado • ' + patientData.email"></p>
                                    </div>
                                </div>
                                <button @click="currentStep = 2; saveField('root', 'current_step', 2)" class="text-xs font-bold text-blue-600 bg-white border border-slate-200 hover:border-slate-300 shadow-sm px-3 py-2 rounded-lg transition-all">Continuar al agendamiento &rarr;</button>
                            </div>

                            <!-- SUB-PASO B: Formulario Express (Si NO existe) -->
                            <div x-show="patientFound === false" x-transition class="bg-white border border-slate-200/80 p-5 rounded-2xl space-y-4 shadow-sm">
                                <div class="flex items-center gap-2 text-xs font-bold text-amber-600 bg-amber-50 border border-amber-200/40 px-3 py-1.5 rounded-lg w-fit">
                                    ⚠️ Paciente no registrado. Complete los datos para su creación express.
                                </div>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="text-xs font-bold text-slate-500">Nombre Completo *</label>
                                        <input type="text" x-model="patientData.name" @input="saveField('patient', 'name', patientData.name)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition-all">
                                    </div>
                                    
                                    <div class="space-y-1">
                                        <label class="text-xs font-bold text-slate-500">Correo Electrónico *</label>
                                        <input type="email" x-model="patientData.email" @input="saveField('patient', 'email', patientData.email)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition-all">
                                    </div>

                                    <!-- Bloque del Teléfono Móvil Adaptado Reactivamente -->
                                    <div class="space-y-1 sm:col-span-1">
                                        <label for="phone" class="text-xs font-bold text-slate-500">Teléfono móvil *</label>
                                        <div class="flex mt-1 rounded-xl shadow-sm border border-slate-200 bg-white focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-600 overflow-hidden transition-all">
                                            <select name="country_code" id="country_code" x-model="patientData.countryCode" @change="saveField('patient', 'country_code', patientData.countryCode)" required class="bg-slate-50 text-slate-700 text-sm border-0 border-r border-slate-200 rounded-l-xl focus:ring-0 px-6 cursor-pointer">
                                                <option value="+57">🇲🇵 +57</option>
                                                <option value="+54">🇦🇷 +54</option>
                                                <option value="+591">🇧🇴 +591</option>
                                                <option value="+55">🇧🇷 +55</option>
                                                <option value="+56">🇨🇱 +56</option>
                                                <option value="+593">🇪🇨 +593</option>
                                                <option value="+595">🇵🇾 +595</option>
                                                <option value="+51">🇵🇪 +51</option>
                                                <option value="+598">🇺🇾 +598</option>
                                                <option value="+58">🇻🇪 +58</option>
                                            </select>
                                            <input type="tel" name="phone" id="phone" x-model="patientData.phone" @input="saveField('patient', 'phone', patientData.phone)" required maxlength="10" pattern="[0-9]{10}" placeholder="3026433874" class="block w-full border-0 focus:ring-0 p-2.5 text-sm text-slate-900 rounded-r-xl">
                                        </div>    
                                        <p class="text-[10px] font-medium text-slate-400 uppercase tracking-wider mt-1.5 px-1 leading-relaxed">
                                            Se utilizará únicamente si el especialista requiere datos extra o para enviar actualizaciones importantes.
                                        </p>
                                    </div>

                                    <div class="space-y-1">
                                        <label class="text-xs font-bold text-slate-500">Identificación</label>
                                        <input type="text" x-model="patientData.identification" readonly class="w-full bg-slate-100 border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-500 outline-none">
                                    </div>
                                </div>

                                <div class="flex justify-end pt-2">
                                    <button @click="currentStep = 2; saveField('root', 'current_step', 2)" :disabled="!patientData.name || !patientData.email || !patientData.phone" class="disabled:opacity-50 disabled:cursor-not-allowed bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-xl text-sm transition-all shadow-sm">Crear y Continuar &rarr;</button>
                                </div>
                            </div>

                        </div>
                        
                        <!-- PASO 2: Datos de la Cita Médica -->
                        <div x-show="currentStep === 2" 
                            x-transition:enter="transition ease-out duration-300 transform"
                            x-transition:enter-start="opacity-0 translate-x-[20px]"
                            x-transition:enter-end="opacity-100 translate-x-0"
                            class="space-y-6">
                            
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <div class="space-y-1">
                                    <h3 class="text-xl font-extrabold text-slate-900 tracking-tight">Detalles de la Cita</h3>
                                    <p class="text-sm text-slate-500" x-text="'Agendando para: ' + patientData.name"></p>
                                </div>
                                <button @click="currentStep = 1; saveField('root', 'current_step', 1)" class="text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors">&larr; Volver a Paciente</button>
                            </div>

                            <!-- Configuración del Espacio de la Cita -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500">Tipo de Sede *</label>
                                    <select x-model="appointmentData.branch" @change="saveField('appointment', 'branch', appointmentData.branch)" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition-all">
                                        <option value="">Seleccione Sede...</option>
                                        <option value="virtual">Teleconsulta Virtual</option>
                                        <option value="norte">Sede Norte Clínica</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500">Servicio Especialidad *</label>
                                    <select x-model="appointmentData.service" @change="saveField('appointment', 'service', appointmentData.service)" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition-all">
                                        <option value="">Seleccione Servicio...</option>
                                        <option value="general">Medicina General</option>
                                        <option value="derma">Dermatología Asistida</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500">Fecha *</label>
                                    <input type="date" x-model="appointmentData.date" @input="saveField('appointment', 'date', appointmentData.date)" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition-all">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500">Hora de Cita *</label>
                                    <input type="time" x-model="appointmentData.time" @input="saveField('appointment', 'time', appointmentData.time)" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition-all">
                                </div>
                            </div>

                            <!-- Módulo de Gestión de Recaudación / Pago -->
                            <div class="bg-white border border-slate-200/80 p-4 rounded-2xl space-y-3">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Método de Recaudación / Pago</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all" :class="appointmentData.paymentMethod === 'online' ? 'border-blue-600 bg-blue-50/20' : 'border-slate-100 bg-slate-50/50 hover:bg-slate-50'">
                                        <input type="radio" name="pay_method" value="online" x-model="appointmentData.paymentMethod" @change="saveField('appointment', 'payment_method', 'online')" class="mt-1 text-blue-600 focus:ring-blue-500">
                                        <div>
                                            <p class="text-sm font-bold text-slate-800">Enviar link de pago</p>
                                            <p class="text-[11px] text-slate-400">Genera token seguro. Reserva bloqueada por 20 minutos.</p>
                                        </div>
                                    </label>
                                    <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all" :class="appointmentData.paymentMethod === 'reception' ? 'border-emerald-600 bg-emerald-50/20' : 'border-slate-100 bg-slate-50/50 hover:bg-slate-50'">
                                        <input type="radio" name="pay_method" value="reception" x-model="appointmentData.paymentMethod" @change="saveField('appointment', 'payment_method', 'reception')" class="mt-1 text-emerald-600 focus:ring-emerald-500">
                                        <div>
                                            <p class="text-sm font-bold text-slate-800">Pagado en recepción</p>
                                            <p class="text-[11px] text-slate-400">Marca la cita como confirmada. Salta link de pago.</p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Envío Final unificado de Cita -->
                            <div class="flex justify-end pt-2">
                                <button @click="
                                    loading = true;
                                    // CORREGIDO: Se removió el prefijo duplicado partner.
                                    fetch('{{ route('partner.booking.session.clear') }}', {
                                        method: 'POST',
                                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                                    }).then(() => {
                                        loading = false;
                                        alert(appointmentData.paymentMethod === 'online' ? '¡Éxito! Token seguro generado y enviado al correo del paciente.' : '¡Éxito! Cita agendada y confirmada directamente en recepción.');
                                        currentStep = 1; searchId = ''; patientFound = null;
                                        patientData = { name: '', email: '', phone: '', identification: '' };
                                    });
                                " :disabled="!appointmentData.branch || !appointmentData.service || !appointmentData.date || !appointmentData.time" class="disabled:opacity-50 disabled:cursor-not-allowed bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-xl text-sm transition-all shadow-md shadow-blue-600/10 flex items-center justify-center gap-2">
                                    <svg x-show="loading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span x-text="loading ? 'Procesando Cita...' : (appointmentData.paymentMethod === 'online' ? 'Generar Token y Enviar Email' : 'Confirmar y Agendar Cita')"></span>
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </section>

    </div>    
</x-admin-layout>
