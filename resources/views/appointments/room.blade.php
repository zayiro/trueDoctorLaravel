<x-guest-layout>
    <!-- Inicializamos Alpine.js con los datos de la cita -->
    <div class="max-w-7xl mx-auto py-12 px-4 mt-6" 
        x-data="telemedicineRoom({
            date: '{{ substr($appointment->date, 0, 10) }}',
            startTime: '{{ $appointment->start_time }}',
            duration: {{ $appointment->duration }},
            appointmentId: {{ $appointment->id }}
        })"
        x-init="initRoom()">
        
        <!-- Encabezado con datos y botón de salida -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white p-4 rounded-lg shadow-sm border border-gray-100 mb-6 gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Consultorio Virtual en Vivo</h1>
                <p class="text-sm text-gray-500">Ref: {{ $appointment->reference }} | Paciente: {{ $appointment->patient->user->name }}</p>
            </div>
            
            <!-- Contador Regresivo -->
            <div class="flex items-center space-x-2 px-4 py-2 rounded-full font-semibold text-sm transition-all duration-300"
                :class="timerClass">
                <svg class="w-5 h-5" :class="minutesRemaining < 5 ? 'animate-pulse' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span x-text="timerText">Calculando tiempo...</span>
            </div>

            @if(auth()->user()->role === 'doctor' || auth()->user()->role === 'clinic')
                <!-- 🥼 VISTA DOCTOR/CLÍNICA: Termina la llamada en Zoom y expulsa a todos -->
                <button @click="if(confirm('¿Estás seguro de que deseas dar por terminada la teleconsulta? Esto cerrará la videollamada para el paciente.')) forceCloseZoomMeeting()" 
                    class="px-4 py-2 bg-red-600 text-white hover:bg-red-700 text-sm font-medium rounded-lg transition shadow-sm">
                    Finalizar Consulta
                </button>
            @else
                <!-- 👤 VISTA PACIENTE: Solo se sale de la pantalla sin cerrar la sala de Zoom -->
                <a href="{{ route('admin.dashboard') }}" 
                    class="px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 text-sm font-medium rounded-lg transition">
                    Salir de la Sala
                </a>
            @endif

        </div>
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- CONTENEDOR DEL VIDEO DE ZOOM -->
            <div class="lg:col-span-3 bg-black rounded-xl overflow-hidden shadow-lg relative min-h-[600px] flex items-center justify-center">
                <div id="meetingSDKElement" class="w-full h-full absolute inset-0" x-ref="zoomContainer"></div>
            </div>

            <!-- PANEL DE CONTROL LATERAL (Ficha Clínica) -->
            <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100 h-fit flex flex-col justify-between">
                <div>
                    <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wider mb-3">Ficha de Consulta</h3>
                    <div class="space-y-3 text-sm border-b border-gray-100 pb-4 mb-4">
                        <p><span class="text-gray-400">Médico:</span> <span class="font-medium text-gray-800">Dr(a). {{ $appointment->doctor->user->name }}</span></p>
                        <p><span class="text-gray-400">Servicio:</span> <span class="text-gray-800">{{ $appointment->service->name }}</span></p>
                        <p><span class="text-gray-400">Fecha:</span> <span class="text-gray-800">{{ ucfirst(\Carbon\Carbon::parse($appointment->date)->locale('es')->isoFormat('dddd D [de] MMMM [de] Y')) }}
a las {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}
</span></p>
                        <p><span class="text-gray-400">Duración máxima:</span> <span class="text-gray-800">{{ $appointment->duration }} minutos</span></p>
                    </div>
                </div>
                <!-- Sección condicional según el Rol del usuario logueado -->
                @if(auth()->user()->role === 'doctor')
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Evolución Médica (En Vivo)</label>
                        <textarea x-model="notes" 
                                class="w-full text-sm border border-gray-200 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" 
                                rows="8" 
                                placeholder="Escribe el diagnóstico, síntomas o recetas aquí..."></textarea>
                        
                        <button @click="saveNotes()"
                                :disabled="saving"
                                class="w-full mt-3 text-white text-sm py-2 rounded-lg font-medium transition flex items-center justify-center space-x-2"
                                :class="saving ? 'bg-blue-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'">
                            <template x-if="saving">
                                <span class="inline-block animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                            </template>
                            <span x-text="saving ? 'Guardando...' : 'Guardar Ficha Clínica'"></span>
                        </button>
                        
                        <p x-show="saved" x-transition class="text-green-600 text-xs mt-2 font-medium text-center">✓ Notas guardadas exitosamente</p>
                    </div>
                @else
                    <div class="p-3 bg-green-50 text-green-800 rounded-lg text-xs">
                        <p class="font-semibold mb-1">Sala de Espera Virtual</p>
                        <p class="text-gray-600">Por seguridad de datos médicos, permanecerás aquí hasta que el doctor autorice tu ingreso a la videollamada.</p>
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
                    this.endTime = new Date(`${config.date}T${config.startTime}`);
                    this.endTime.setMinutes(this.endTime.getMinutes() + config.duration);
                    
                    this.startCountdown();
                    this.listenToMeetingEnd();

                    // Verificamos de forma segura si el objeto ya existe en el navegador
                    const checkZoomLoaded = setInterval(() => {
                        if (typeof ZoomMtg !== 'undefined') {
                            clearInterval(checkZoomLoaded);
                            this.initZoomSDK(); // Encendemos el SDK solo cuando el archivo terminó de descargar
                        }
                    }, 2000); // Revisa cada 2 segundos
                },
                
                initZoomSDK() {
                    // Obliga al SDK a buscar los archivos de audio/video en el servidor de Zoom, no en el tuyo
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

                saveNotes() {
                    this.saving = true;
                    this.saved = false;

                    fetch(`/api/appointments/${config.appointmentId}/notes`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ notes: this.notes })
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.saving = false;
                        this.saved = true;
                        setTimeout(() => this.saved = false, 3000);
                    })
                    .catch(err => {
                        this.saving = false;
                        alert('Error al guardar el historial médico.');
                    });
                },
                startCountdown() {
                    const updateTimer = () => {
                        const now = new Date();
                        const difference = this.endTime - now;

                        if (difference <= 0) {
                            clearInterval(interval);
                            this.timerText = "Consulta finalizada";
                            this.timerClass = "bg-red-100 text-red-700";
                            this.minutesRemaining = 0;
                            this.forceCloseZoomMeeting();
                            return;
                        }

                        const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((difference % (1000 * 60)) / 1000);
                        
                        this.minutesRemaining = minutes;
                        this.timerText = `Tiempo restante: ${minutes}m ${seconds}s`;

                        if (minutes < 5) {
                            this.timerClass = "bg-red-50 text-red-600 font-bold animate-pulse";
                        } else if (minutes < 10) {
                            this.timerClass = "bg-yellow-50 text-yellow-700";
                        }
                    };

                    updateTimer();
                    const interval = setInterval(updateTimer, 1000);
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
                    const checkStatusInterval = setInterval(() => {
                        fetch(`/api/appointments/${config.appointmentId}/status`)
                            .then(res => res.json())
                            .then(data => {
                                if (data.status === 'completed') {
                                    clearInterval(checkStatusInterval);
                                    this.timerText = "Consulta terminada";
                                    this.timerClass = "bg-red-600 text-white font-bold animate-bounce";
                                    
                                    setTimeout(() => {
                                        window.location.href = "{{ route('admin.dashboard') }}";
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
