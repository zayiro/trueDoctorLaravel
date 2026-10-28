@php
    $user = auth()->user();
    $isProfessional = in_array($user->role, ['doctor', 'clinic'], true);

    // Hora de inicio de la cita: antes de ella, la sala solo muestra la espera.
    $startsAt = \Carbon\Carbon::parse($appointment->date)->setTimeFromTimeString($appointment->start_time);
    $notStartedYet = now()->isBefore($startsAt);

    // A dónde va cada usuario cuando la consulta termina.
    $roleName = $user->roles->first()?->name ?? 'patient';
    $exitUrl = in_array($roleName, ['doctor', 'admin', 'clinic'], true)
        ? route('admin.dashboard')
        : '/appointments/history';

    // Solo el paciente sube archivos a la reunión (el doctor y el administrador solo los ven).
    $isPatient = !$isProfessional && $roleName !== 'admin';

    // Consulta finalizada: por estado de la cita o porque ya pasó su hora de término.
    $statusValue = $appointment->status instanceof \BackedEnum
        ? $appointment->status->value
        : (string) $appointment->status;

    $endsAt = $startsAt->copy()->addMinutes((int) $appointment->duration);

    $consultationEnded = in_array($statusValue, ['completed', 'cancelled'], true)
        || now()->greaterThanOrEqualTo($endsAt);

    // Configuración que lee el componente Alpine (@js escapa los valores de forma segura)
    $cfg = [
        'date' => \Carbon\Carbon::parse($appointment->date)->format('Y-m-d'),
        'startTime' => \Carbon\Carbon::parse($appointment->start_time)->format('H:i:s'),
        'duration' => (int) $appointment->duration,
        'appointmentId' => (int) $appointment->id,
        'exitUrl' => $exitUrl,
        'startUrl' => route('appointments.start-zoom', $appointment->id),
        'endUrl' => "/api/appointments/{$appointment->id}/end-zoom",
        'statusUrl' => "/api/appointments/{$appointment->id}/status",
        'csrf' => csrf_token(),
        'zoom' => [
            'sdkKey' => $sdkKey,
            'signature' => $signature,
            'meetingNumber' => (string) $meetingId,
            'passWord' => $password,
            'userName' => $user->name,
            'userEmail' => $user->email,
        ],
    ];
@endphp

<x-guest-layout
    meta-title-medical-analysis="Consulta Virtual en Vivo {{ $appointment->reference }} | OpenDoctorOnline"
    meta-description-medical-analysis="Accede a tu consulta médica virtual en tiempo real. Conecta con tu especialista de forma segura a través de nuestro consultorio digital."
>
    <style>[x-cloak] { display: none !important; }</style>

    <div class="max-w-7xl mx-auto py-12 px-4 mt-6"
         x-data="telemedicineRoom(@js($cfg))"
         x-init="initRoom()">

        {{-- Encabezado con datos y botón de salida --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white p-4 rounded-lg shadow-sm border border-gray-100 mb-6 gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Consultorio Virtual en Vivo</h1>
                <p class="text-sm text-gray-500">Paciente: {{ $appointment->patient->user->name }}</p>
            </div>

            {{-- Contador regresivo --}}
            <div class="flex items-center space-x-2 font-semibold text-sm transition-all duration-300" :class="timerClass">
                <svg class="w-5 h-5 flex-shrink-0"
                     :class="isEnding ? 'animate-pulse' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span x-text="timerText" x-cloak>Calculando tiempo...</span>
            </div>

            @if ($isProfessional)
                <button @click="if (confirm('¿Estás seguro de que deseas dar por terminada la teleconsulta? Esto cerrará la videollamada para el paciente.')) forceCloseZoomMeeting()"
                        class="px-4 py-2 bg-red-600 text-white hover:bg-red-700 text-sm font-medium rounded-lg transition shadow-sm">
                    Finalizar Consulta
                </button>
            @else
                <a href="{{ route('admin.dashboard') }}"
                   class="px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 text-sm font-medium rounded-lg transition">
                    Salir de la sala
                </a>
            @endif
        </div>

        {{-- GRID PRINCIPAL: video (2/3) + columna lateral (1/3) --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- VIDEO DE ZOOM --}}
            <div class="lg:col-span-2 bg-black rounded-xl overflow-hidden shadow-lg relative min-h-[600px] flex items-center justify-center">
                @if (!$zoomStarted)
                    @if ($isProfessional)
                        <div class="text-center">
                            <p class="text-white mb-4 text-lg font-semibold">Consulta lista para iniciar</p>
                            <button @click="startConsultation()"
                                    class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg transition shadow-lg">
                                Permitir acceso al paciente / Iniciar videollamada
                            </button>
                        </div>
                    @else
                        <div class="text-center text-white">
                            <p class="text-lg font-semibold">Esperando a que el doctor inicie...</p>
                        </div>
                    @endif
                @elseif ($notStartedYet)
                    {{-- Activada, pero aún no es la hora --}}
                    <div class="text-center text-white">
                        <p class="text-lg font-semibold">
                            {{ $isProfessional
                                ? 'Videollamada permitida, esperando que comience el horario...'
                                : 'El doctor ha permitido el acceso, esperando que comience el horario...' }}
                        </p>
                        <p class="text-sm text-gray-300 mt-2" x-text="timerText"></p>
                    </div>
                @else
                    {{-- Ya es la hora: se monta el video --}}
                    <div id="meetingSDKElement" class="w-full h-full absolute inset-0" x-ref="zoomContainer"></div>
                @endif
            </div>

            {{-- COLUMNA LATERAL --}}
            <div class="space-y-6">

                {{-- 1. Ficha de consulta --}}
                <div class="bg-white rounded-xl shadow-md border border-gray-100 p-5">
                    <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wider mb-3">Ficha de Consulta</h3>
                    <div class="space-y-2 text-xs">
                        <p><span class="text-gray-400">Referencia:</span> <span class="font-medium text-gray-800">{{ $appointment->reference }}</span></p>
                        <p><span class="text-gray-400">Médico:</span> <span class="font-medium text-gray-800">{{ $appointment->doctor->user->name }}</span></p>
                        <p><span class="text-gray-400">Servicio:</span> <span class="text-gray-800">{{ $appointment->service->name }}</span></p>
                        <p><span class="text-gray-400">Fecha:</span> <span class="text-gray-800">{{ ucfirst(\Carbon\Carbon::parse($appointment->date)->locale('es')->isoFormat('dddd D [de] MMMM')) }}</span></p>

                        {{-- Enlace a la historia clínica (solo profesionales) --}}
                        @if ($isProfessional)
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

                {{-- Archivos de la reunión (debajo de la ficha) --}}
                <section x-data class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="font-semibold text-gray-900">Archivos de la reunión</h3>

                        @if ($isPatient)
                            <button type="button" x-show="!finished" x-cloak @click="$store.roomFiles.open()"
                                    class="inline-flex flex-shrink-0 items-center gap-1.5 whitespace-nowrap rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-blue-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0l-4 4m4-4l4 4M4 20h16"/>
                                </svg>
                                Subir archivo
                            </button>
                            <span x-show="finished" x-cloak class="text-xs text-gray-400">Consulta finalizada</span>
                        @endif
                    </div>

                    <button x-show="$store.roomFiles.newCount > 0" x-cloak
                            @click="$store.roomFiles.markSeen()"
                            class="mt-3 rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700">
                        <span x-text="$store.roomFiles.newCount"></span> nuevo(s) · marcar como visto
                    </button>

                    <p x-show="$store.roomFiles.files.length === 0" class="mt-3 text-sm text-gray-500">
                        Aún no se han subido archivos.
                    </p>

                    <ul class="mt-3 max-h-72 divide-y divide-gray-100 overflow-y-auto">
                        <template x-for="f in $store.roomFiles.files" :key="f.id">
                            <li class="flex items-start gap-3 py-3"
                                :class="$store.roomFiles.isNew(f.id) ? 'bg-green-50 -mx-2 px-2 rounded-lg' : ''">
                                <template x-if="f.is_image">
                                    <img :src="f.url" class="h-12 w-12 flex-shrink-0 rounded-lg object-cover" alt="">
                                </template>
                                <template x-if="!f.is_image">
                                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-red-50 text-xs font-bold text-red-600">PDF</div>
                                </template>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="truncate text-sm font-medium text-gray-900" x-text="f.name"></p>
                                        <span x-show="$store.roomFiles.isNew(f.id)" x-cloak
                                            class="flex-shrink-0 rounded-full bg-green-600 px-2 py-0.5 text-xs font-medium text-white">Nuevo</span>
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        <span x-text="f.uploader_name"></span> · <span x-text="f.time"></span> · <span x-text="f.size"></span>
                                    </p>
                                    <div class="mt-1 flex gap-3 text-xs">
                                        <a :href="f.url" target="_blank" class="text-blue-600 hover:underline">Ver</a>
                                        <a :href="f.download_url" class="text-gray-500 hover:underline">Descargar</a>
                                    </div>
                                </div>
                            </li>
                        </template>
                    </ul>
                    <div class="border-4 border-indigo-200 border-2 border-t-indigo-500"></div>                    
                    <p class="mt-4">
                        <div class="text-sm text-gray-500 font-bold mb-3">¿Tienes estudios o imágenes médicas?</div>
                        <div class="text-sm text-gray-500">
                            Tienes la opción de <span class="text-indigo-500 font-bold">subir archivos</span> como resultados de exámenes de laboratorio, radiografías o fotografías de la zona afectada para que el doctor pueda revisarlos detalladamente durante tu consulta.
                        <div>
                    </p>
                </section>

                @if ($isProfessional)
                    {{-- 3. Asistente IA (grabación de la consulta) --}}
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-5"
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

                        {{-- Estado: Idle --}}
                        <template x-if="state === 'idle'">
                            <button type="button" @click="startRecording()"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold text-md py-2 px-3 rounded-lg transition inline-flex items-center justify-center gap-2 shadow-lg">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/></svg>
                                Iniciar grabación
                            </button>
                        </template>

                        {{-- Estado: Recording --}}
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

                        {{-- Estado: Processing --}}
                        <template x-if="state === 'uploading' || state === 'transcribing' || state === 'structuring'">
                            <div class="flex items-center gap-2 text-xs text-gray-600">
                                <svg class="animate-spin h-3 w-3 text-blue-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="statusLabel"></span>
                            </div>
                        </template>

                        {{-- Estado: Ready --}}
                        <template x-if="state === 'ready'">
                            <div class="rounded-lg bg-green-50 border border-green-200 p-2 text-xs text-green-800 font-medium">
                                ✓ Nota generada — Los campos SOAP se rellenaron automáticamente abajo.
                            </div>
                        </template>

                        {{-- Estado: Error / subida fallida --}}
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

                    {{-- 4. Formulario SOAP --}}
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-5">
                        <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wider mb-3">Nota de Evolución (SOAP)</h3>

                        <form id="evolution-note-form" action="{{ route('partner.patients.store-history', $appointment->patient->id) }}" method="POST" class="space-y-3">
                            @csrf
                            <input type="hidden" name="appointment_id" value="{{ $appointment->id }}">

                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Tipo de Entrada</label>
                                <select name="entry_type" class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    <option value="consultation">Consulta</option>
                                    <option value="follow_up">Seguimiento</option>
                                    <option value="procedure">Procedimiento</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Código CIE-10</label>
                                <input type="text" name="cie10_code" placeholder="ej: J45.9" class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            </div>

                            @foreach ([
                                'soap_subjective' => ['Subjetivo', 'S', 'Síntomas reportados por el paciente...'],
                                'soap_objective' => ['Objetivo', 'O', 'Hallazgos clínicos observados...'],
                                'soap_assessment' => ['Evaluación', 'A', 'Diagnóstico e impresión clínica...'],
                                'soap_plan' => ['Plan', 'P', 'Tratamiento recomendado y seguimiento...'],
                            ] as $field => [$label, $letter, $placeholder])
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">{{ $label }} <span class="font-bold text-red-700">({{ $letter }})</span></label>
                                    <textarea name="{{ $field }}" rows="3" placeholder="{{ $placeholder }}" class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none"></textarea>
                                </div>
                            @endforeach

                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold text-md py-2 px-3 rounded-lg transition shadow-lg">
                                Guardar Nota
                            </button>
                        </form>
                    </div>
                @else
                    {{-- Vista del paciente --}}
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-5">
                        <div class="p-3 bg-green-50 text-green-800 rounded-lg text-xs border border-green-200">
                            <p class="font-semibold mb-1">Sala de Espera Virtual</p>
                            <p class="text-gray-600">Por seguridad de datos médicos, permanecerás aquí hasta que el doctor autorice tu ingreso a la videollamada.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal para subir archivos a la reunión (dentro del layout, no antes del <html>) --}}
    @include('appointments.partials.room-files-modal', ['appointment' => $appointment])

    {{-- Motores oficiales Web por CDN de Zoom --}}
    <script src="https://source.zoom.us/3.11.0/lib/vendor/react.min.js"></script>
    <script src="https://source.zoom.us/3.11.0/lib/vendor/react-dom.min.js"></script>
    <script src="https://source.zoom.us/3.11.0/lib/vendor/redux.min.js"></script>
    <script src="https://source.zoom.us/3.11.0/lib/vendor/redux-thunk.min.js"></script>
    <script src="https://source.zoom.us/3.11.0/lib/vendor/lodash.min.js"></script>
    <script src="https://source.zoom.us/3.11.0/zoom-meeting-embedded-3.11.0.min.js"></script>

    {{-- AIScribeStorage y consultationScribe --}}
    @include('partner.patients.partials.consultation-scribe-script')

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('telemedicineRoom', (config) => ({
                minutesRemaining: config.duration,
                timerText: 'Calculando...',
                timerClass: 'bg-blue-50 text-blue-700',
                startTime: null,
                endTime: null,
                finished: config.ended,

                // Parpadea el reloj durante los últimos 5 minutos de la consulta
                get isEnding() {
                    return this.startTime !== null && new Date() >= this.startTime && this.minutesRemaining < 5;
                },

                initRoom() {
                    const cleanDate = String(config.date).trim();
                    const cleanTime = String(config.startTime).trim();

                    this.startTime = new Date(`${cleanDate}T${cleanTime}`);

                    this.endTime = new Date(this.startTime.getTime());
                    this.endTime.setMinutes(this.endTime.getMinutes() + parseInt(config.duration));

                    this.startCountdown();
                    this.listenToMeetingEnd();

                    const checkZoomLoaded = setInterval(() => {
                        if (typeof ZoomMtg !== 'undefined') {
                            clearInterval(checkZoomLoaded);
                            if (new Date() >= this.startTime) {
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
                        client.join(config.zoom)
                            .then(() => console.log('Conexión de video establecida con el SDK.'))
                            .catch((err) => console.error('Error al unirse a Zoom:', err));
                    }).catch((err) => console.error('Error inicializando el motor:', err));
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
                                this.timerClass = 'bg-blue-50 text-blue-700 border border-blue-200 font-medium px-4 py-2 rounded-full';
                                return;
                            }

                            const totalSeconds = Math.floor(timeToStart / 1000);
                            const hours = Math.floor(totalSeconds / 3600);
                            const minutes = Math.floor((totalSeconds % 3600) / 60);
                            const seconds = totalSeconds % 60;

                            const pad = (num) => String(num).padStart(2, '0');
                            this.timerText = `Inicia en: ${pad(hours)}h ${pad(minutes)}m ${pad(seconds)}s`;
                            this.timerClass = 'bg-blue-50 text-blue-600 border border-blue-100 animate-pulse font-semibold px-4 py-2 rounded-full';
                            return;
                        }

                        const difference = this.endTime - now;

                        if (difference <= 0) {
                            if (interval) clearInterval(interval);
                            this.timerText = 'Consulta finalizada';
                            this.timerClass = 'bg-red-100 text-red-700 border border-red-300 font-bold px-4 py-2 rounded-full';
                            this.minutesRemaining = 0;
                            this.finished = true;

                            if (typeof ZoomMtg !== 'undefined') {
                                this.forceCloseZoomMeeting();
                            }
                            return;
                        }

                        const totalSeconds = Math.floor(difference / 1000);
                        const minutes = Math.floor(totalSeconds / 60);
                        const seconds = totalSeconds % 60;

                        this.minutesRemaining = minutes;

                        this.timerText = `Tiempo restante: ${String(minutes).padStart(2, '0')}m ${String(seconds).padStart(2, '0')}s`;

                        if (minutes < 5) {
                            this.timerClass = 'bg-red-50 text-red-600 font-bold animate-pulse border border-red-200 px-4 py-2 rounded-full';
                        } else if (minutes < 10) {
                            this.timerClass = 'bg-yellow-50 text-yellow-700 border border-yellow-200 px-4 py-2 rounded-full';
                        } else {
                            this.timerClass = 'bg-green-50 text-green-700 border border-green-200 font-medium px-4 py-2 rounded-full';
                        }
                    };

                    updateTimer();
                    interval = setInterval(updateTimer, 1000);
                },

                forceCloseZoomMeeting() {
                    fetch(config.endUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': config.csrf
                        }
                    })
                    .then(res => res.json())
                    .catch(err => console.error('Error al cerrar reunión:', err));
                },

                listenToMeetingEnd() {
                    if (new Date() < this.startTime) {
                        console.log('Polling en pausa: Sala en fase de espera futura.');
                        return;
                    }

                    const checkStatusInterval = setInterval(() => {
                        fetch(config.statusUrl)
                            .then(res => res.json())
                            .then(data => {
                                if (data.status === 'completed' || data.status === 'cancelled') {
                                    clearInterval(checkStatusInterval);
                                    this.finished = true;

                                    this.timerText = 'Consulta terminada';
                                    this.timerClass = 'bg-red-600 text-white font-bold animate-pulse px-4 py-2 rounded-full';

                                    setTimeout(() => { window.location.href = config.exitUrl; }, 3000);
                                }
                            })
                            .catch(err => console.error('Error al verificar estado:', err));
                    }, 5000);
                },

                startConsultation() {
                    fetch(config.startUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': config.csrf
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        }
                    })
                    .catch(err => console.error('Error:', err));
                }
            }));
        });
    </script>
</x-guest-layout>