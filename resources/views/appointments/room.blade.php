<x-guest-layout>
    <!-- Inicializamos Alpine.js con los datos de la cita -->
    <div class="max-w-7xl mx-auto py-12 px-4 mt-6" 
        x-data="telemedicineRoom({
            date: '{{ \Carbon\Carbon::parse($appointment->date)->format('Y-m-d') }}',
            startTime: '{{ \Carbon\Carbon::parse($appointment->start_time)->format('H:i:s') }}',
            duration: {{ (int) $appointment->duration }},
            appointmentId: {{ (int) $appointment->id }}
        })"
        x-init="initRoom()">
        
        <!-- Encabezado con datos y botón de salida -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white p-4 rounded-lg shadow-sm border border-gray-100 mb-6 gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Consultorio Virtual en Vivo</h1>
                <p class="text-sm text-gray-500">Ref: {{ $appointment->reference }} | Paciente: {{ $appointment->patient->user->name }}</p>
            </div>
            
            <!-- Contador Regresivo -->
            <div class="flex items-center space-x-2 font-semibold text-sm transition-all duration-300"
                :class="timerClass">
                
                <svg class="w-5 h-5 flex-shrink-0" 
                    :class="(new Date() >= this.startTime && minutesRemaining < 5) ? 'animate-pulse' : ''" 
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                
                <span x-text="timerText" x-cloak>Calculando tiempo...</span>
            </div>

            @if(auth()->user()->role === 'doctor' || auth()->user()->role === 'clinic')
                <button @click="if(confirm('¿Estás seguro de que deseas dar por terminada la teleconsulta? Esto cerrará la videollamada para el paciente.')) forceCloseZoomMeeting()" 
                    class="px-4 py-2 bg-red-600 text-white hover:bg-red-700 text-sm font-medium rounded-lg transition shadow-sm">
                    Finalizar Consulta
                </button>
            @else
                <a href="{{ route('admin.dashboard') }}" 
                    class="px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 text-sm font-medium rounded-lg transition">
                    Salir de la Sala
                </a>
            @endif
        </div>

        <!-- GRID PRINCIPAL: Video + Panel Lateral (Formulario SOAP) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- VIDEO DE ZOOM (Más grande: 2/3 del ancho) -->
            <div class="lg:col-span-2 bg-black rounded-xl overflow-hidden shadow-lg relative min-h-[600px] flex items-center justify-center">
                <div id="meetingSDKElement" class="w-full h-full absolute inset-0" x-ref="zoomContainer"></div>
            </div>

            <!-- PANEL LATERAL: Grabación SOAP + Formulario (1/3 del ancho) -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 h-fit flex flex-col overflow-y-auto max-h-[700px]">
                
                <!-- Información de la Cita -->
                <div class="p-5 border-b border-gray-100">
                    <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wider mb-3">Ficha de Consulta</h3>
                    <div class="space-y-2 text-xs">
                        <p><span class="text-gray-400">Médico:</span> <span class="font-medium text-gray-800">{{ $appointment->doctor->user->name }}</span></p>
                        <p><span class="text-gray-400">Servicio:</span> <span class="text-gray-800">{{ $appointment->service->name }}</span></p>
                        <p><span class="text-gray-400">Fecha:</span> <span class="text-gray-800">{{ ucfirst(\Carbon\Carbon::parse($appointment->date)->locale('es')->isoFormat('dddd D [de] MMMM')) }}</span></p>
                        
                        <!-- Enlace a Historia Clínica (Solo para doctores) -->
                        @if(auth()->user()->role === 'doctor' || auth()->user()->role === 'clinic')
                            <p class="pt-2">
                                <a href="{{ route('partner.patients.show', $appointment->patient->id) }}" 
                                   target="_blank" 
                                   class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700 font-medium text-xs underline">
                                    📋 Abrir Historia Clínica
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            </p>
                        @endif
                    </div>
                </div>

                <!-- GRABACIÓN SOAP (Solo para doctores) -->
                @if(auth()->user()->role === 'doctor' || auth()->user()->role === 'clinic')
                <div class="p-5 border-b border-gray-100"
                    x-data="consultationScribe({
                        patientId: {{ $appointment->patient->id }},
                        appointmentId: {{ $appointment->id }},
                        uploadUrl: '{{ route('partner.patients.consultation-audio.upload', $appointment->patient->id) }}',
                        statusUrlBase: '{{ url('partner/consultation-audio') }}',
                        notifyPendingUrl: '{{ route('partner.patients.consultation-audio.notify-pending', $appointment->patient->id) }}',
                        recordingId: 'rec_{{ $appointment->patient->id }}_{{ $appointment->id }}',
                    })">
                    
                    <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wider mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-14 0m7 7v3m-3.5 0h7M12 14a3 3 0 003-3V5a3 3 0 10-6 0v6a3 3 0 003 3z"/></svg>
                        Asistente IA
                    </h3>

                    <!-- Estado: Idle -->
                    <template x-if="state === 'idle'">
                        <button type="button" @click="startRecording()"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold text-md py-2 px-3 rounded-lg transition inline-flex items-center justify-center gap-2 shadow-lg">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/></svg>
                            Iniciar Grabación
                        </button>
                    </template>

                    <!-- Estado: Recording -->
                    <template x-if="state === 'recording'">
                        <div class="space-y-2">
                            <p class="text-xs text-gray-700 flex items-center gap-2 font-medium">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                                </span>
                                Grabando: <span x-text="elapsedLabel" class="font-mono"></span>
                            </p>
                            <button type="button" @click="stopRecording()"
                                class="w-full bg-red-600 hover:bg-red-700 text-white font-bold text-xs py-2 px-3 rounded-lg transition">
                                Detener
                            </button>
                        </div>
                    </template>

                    <!-- Estado: Processing -->
                    <template x-if="state === 'uploading' || state === 'transcribing' || state === 'structuring'">
                        <div class="flex items-center gap-2 text-xs text-gray-600">
                            <svg class="animate-spin h-3 w-3 text-blue-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="statusLabel"></span>
                        </div>
                    </template>

                    <!-- Estado: Ready -->
                    <template x-if="state === 'ready'">
                        <div class="rounded-lg bg-green-50 border border-green-200 p-2 text-xs text-green-800 font-medium">
                            ✓ Nota generada — Los campos SOAP se rellenaron automáticamente abajo.
                        </div>
                    </template>

                    <!-- Estado: Error/Upload Failed -->
                    <template x-if="state === 'error' || state === 'upload_failed'">
                        <div class="rounded-lg bg-red-50 border border-red-200 p-2 text-xs text-red-700 space-y-2">
                            <p x-text="errorMessage"></p>
                            <button type="button" @click="state === 'upload_failed' ? retryUpload() : reset()"
                                class="w-full bg-red-600 hover:bg-red-700 text-white text-xs py-1 px-2 rounded transition">
                                Reintentar
                            </button>
                        </div>
                    </template>
                </div>
                @endif

                <!-- FORMULARIO SOAP (Solo para doctores) -->
                @if(auth()->user()->role === 'doctor' || auth()->user()->role === 'clinic')
                <div class="p-5">
                    <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wider mb-3">Nota de Evolución</h3>
                    
                    <form id="evolution-note-form" action="{{ route('partner.patients.store-history', $appointment->patient->id) }}" method="POST" class="space-y-3">
                        @csrf
                        <input type="hidden" name="appointment_id" value="{{ $appointment->id }}">

                        <!-- Tipo de Entrada -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Tipo de Entrada</label>
                            <select name="entry_type" class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                <option value="consultation">Consulta</option>
                                <option value="follow_up">Seguimiento</option>
                                <option value="procedure">Procedimiento</option>
                            </select>
                        </div>

                        <!-- Código CIE-10 -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Código CIE-10</label>
                            <input type="text" name="cie10_code" placeholder="ej: J45.9" class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>

                        <!-- Subjetivo (S) -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Subjetivo <span class="font-bold text-red-700">(S)</span></label>
                            <textarea name="soap_subjective" rows="3" placeholder="Síntomas reportados por el paciente..." class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none"></textarea>
                        </div>

                        <!-- Objetivo (O) -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Objetivo <span class="font-bold text-red-700">(O)</span></label>
                            <textarea name="soap_objective" rows="3" placeholder="Hallazgos clínicos observados..." class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none"></textarea>
                        </div>

                        <!-- Evaluación (A) -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Evaluación <span class="font-bold text-red-700">(A)</span></label>
                            <textarea name="soap_assessment" rows="3" placeholder="Diagnóstico e impresión clínica..." class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none"></textarea>
                        </div>

                        <!-- Plan (P) -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Plan <span class="font-bold text-red-700">(P)</span></label>
                            <textarea name="soap_plan" rows="3" placeholder="Tratamiento recomendado y seguimiento..." class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none"></textarea>
                        </div>

                        <!-- Botón Guardar -->
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold text-md py-2 px-3 rounded-lg transition shadow-lg">
                            Guardar Nota
                        </button>
                    </form>
                </div>
                @endif

                <!-- VISTA PACIENTE -->
                @if(auth()->user()->role !== 'doctor' && auth()->user()->role !== 'clinic')
                <div class="p-5">
                    <div class="p-3 bg-green-50 text-green-800 rounded-lg text-xs border border-green-200">
                        <p class="font-semibold mb-1">Sala de Espera Virtual</p>
                        <p class="text-gray-600">Por seguridad de datos médicos, permanecerás aquí hasta que el doctor autorice tu ingreso a la videollamada.</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Motores oficiales Web por CDN de Zoom -->
    <script src="https://source.zoom.us/3.11.0/lib/vendor/react.min.js"></script>
    <script src="https://source.zoom.us/3.11.0/lib/vendor/react-dom.min.js"></script>
    <script src="https://source.zoom.us/3.11.0/lib/vendor/redux.min.js"></script>
    <script src="https://source.zoom.us/3.11.0/lib/vendor/redux-thunk.min.js"></script>
    <script src="https://source.zoom.us/3.11.0/lib/vendor/lodash.min.js"></script>
    <script src="https://source.zoom.us/3.11.0/zoom-meeting-embedded-3.11.0.min.js"></script>
    
    <!-- AIScribeStorage y consultationScribe (del script que pasaste) -->
    @include('partner.patients.partials.consultation-scribe-script')

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('telemedicineRoom', (config) => ({
                notes: '{{ addslashes($appointment->notes) }}',
                saving: false,
                saved: false,
                minutesRemaining: config.duration,
                timerText: 'Calculando...',
                timerClass: 'bg-blue-50 text-blue-700',
                endTime: null,
                
                initRoom() {
                    const cleanDate = String(config.date).trim();
                    const cleanTime = String(config.startTime).trim();
                    const isoString = `${cleanDate}T${cleanTime}`;

                    this.startTime = new Date(isoString);
                    
                    this.endTime = new Date(this.startTime.getTime());
                    this.endTime.setMinutes(this.endTime.getMinutes() + parseInt(config.duration));
                    
                    this.startCountdown();
                    this.listenToMeetingEnd();

                    const checkZoomLoaded = setInterval(() => {
                        if (typeof ZoomMtg !== 'undefined') {
                            clearInterval(checkZoomLoaded);
                            const now = new Date();
                            if (now >= this.startTime) {
                                this.initZoomSDK();
                            }                            
                        }
                    }, 2000);
                },
                
                initZoomSDK() {
                    ZoomMtg.setZoomJSLib('https://source.zoom.us', '/av');
                    
                    const client = ZoomMtg.createClient();

                    client.init({
                        zoomAppRoot: this.$refs.zoomContainer,
                        language: 'es-ES',
                        patchJsMedia: true
                    }).then(() => {
                        client.join({
                            sdkKey: "{{ $sdkKey }}", 
                            signature: "{{ $signature }}", 
                            meetingNumber: "{{ $meetingId }}", 
                            passWord: "{{ $password }}", 
                            userName: "{{ Auth::user()->name }}", 
                            userEmail: "{{ Auth::user()->email }}"
                        }).then(() => {
                            console.log("Conexión de video establecida con el SDK.");
                        }).catch((err) => console.error("Error al unirse a Zoom:", err));
                    }).catch((err) => console.error("Error inicializando el motor:", err));
                },

                startCountdown() {
                    let interval = null;

                    const updateTimer = () => {
                        const now = new Date();

                        if (now < this.startTime) {
                            this.minutesRemaining = config.duration;
                            const timeToStart = this.startTime - now;
                            const oneDayInMs = 24 * 60 * 60 * 1000;

                            if (timeToStart > oneDayInMs) {
                                const days = Math.ceil(timeToStart / oneDayInMs);
                                this.timerText = `La reunión aún no empieza (Faltan ${days} ${days === 1 ? 'día' : 'días'})`;
                                this.timerClass = "bg-blue-50 text-blue-700 border border-blue-200 font-medium px-4 py-2 rounded-full";
                                return;
                            }

                            const totalSeconds = Math.floor(timeToStart / 1000);
                            const hours = Math.floor(totalSeconds / 3600);
                            const minutes = Math.floor((totalSeconds % 3600) / 60);
                            const seconds = totalSeconds % 60;

                            const pad = (num) => String(num).padStart(2, '0');
                            this.timerText = `Inicia en: ${pad(hours)}h ${pad(minutes)}m ${pad(seconds)}s`;
                            this.timerClass = "bg-blue-50 text-blue-600 border border-blue-100 animate-pulse font-semibold px-4 py-2 rounded-full";
                            return;
                        }

                        const difference = this.endTime - now;

                        if (difference <= 0) {
                            if (interval) clearInterval(interval);
                            this.timerText = "Consulta finalizada";
                            this.timerClass = "bg-red-100 text-red-700 border border-red-300 font-bold px-4 py-2 rounded-full";
                            this.minutesRemaining = 0;
                            
                            if (typeof ZoomMtg !== 'undefined') {
                                this.forceCloseZoomMeeting();
                            }
                            return;
                        }

                        const totalSeconds = Math.floor(difference / 1000);
                        const minutes = Math.floor(totalSeconds / 60);
                        const seconds = totalSeconds % 60;
                        
                        this.minutesRemaining = minutes;

                        const paddedMinutes = String(minutes).padStart(2, '0');
                        const paddedSeconds = String(seconds).padStart(2, '0');
                        this.timerText = `Tiempo restante: ${paddedMinutes}m ${paddedSeconds}s`;

                        if (minutes < 5) {
                            this.timerClass = "bg-red-50 text-red-600 font-bold animate-pulse border border-red-200 px-4 py-2 rounded-full";
                        } else if (minutes < 10) {
                            this.timerClass = "bg-yellow-50 text-yellow-700 border border-yellow-200 px-4 py-2 rounded-full";
                        } else {
                            this.timerClass = "bg-green-50 text-green-700 border border-green-200 font-medium px-4 py-2 rounded-full";
                        }
                    };

                    updateTimer();
                    interval = setInterval(updateTimer, 1000);
                },

                forceCloseZoomMeeting() {
                    fetch(`/api/appointments/${config.appointmentId}/end-zoom`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(res => res.json())
                    .catch(err => console.error('Error al cerrar reunión:', err));
                },

                listenToMeetingEnd() {
                    const now = new Date();
                    if (now < this.startTime) {
                        console.log("Polling en pausa: Sala en fase de espera futura.");
                        return; 
                    }

                    const checkStatusInterval = setInterval(() => {
                        fetch(`/api/appointments/${config.appointmentId}/status`)
                            .then(res => res.json())
                            .then(data => {
                                if (data.status === 'completed' || data.status === 'cancelled') {
                                    clearInterval(checkStatusInterval);
                                    
                                    this.timerText = "Consulta terminada";
                                    this.timerClass = "bg-red-600 text-white font-bold animate-pulse px-4 py-2 rounded-full";
                                    
                                    setTimeout(() => {
                                        const userRole = "{{ Auth::user()->roles->first()?->name ?? 'patient' }}";
                                        
                                        if (userRole === 'doctor' || userRole === 'admin' || userRole === 'clinic') {
                                            window.location.href = "{{ route('admin.dashboard') }}";
                                        } else {
                                            window.location.href = "/appointments/history"; 
                                        }
                                    }, 3000);
                                }
                            })
                            .catch(err => console.error('Error al verificar estado:', err));
                    }, 5000);
                }
            }));
        });
    </script>
</x-guest-layout>