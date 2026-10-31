@php

$user = auth()->user();
$isProfessional = in_array($user->role, ['doctor', 'clinic'], true);
$startsAt = \Carbon\Carbon::parse($appointment->date)->setTimeFromTimeString($appointment->start_time);
$notStartedYet = now()->isBefore($startsAt);
$roleName = $user->roles->first()?->name ?? 'patient';
$exitUrl = in_array($roleName, ['doctor', 'admin', 'clinic'], true)
    ? route('partner.appointments.index')
    : route('patient.appointments.index');
$isPatient = !$isProfessional && $roleName !== 'admin';
$statusValue = $appointment->status instanceof \BackedEnum
    ? $appointment->status->value
    : (string) $appointment->status;
$endsAt = $startsAt->copy()->addMinutes((int) $appointment->duration);
$consultationEnded = in_array($statusValue, ['completed', 'cancelled'], true)
    || now()->greaterThanOrEqualTo($endsAt);

$cfg = [
    'date'                => \Carbon\Carbon::parse($appointment->date)->format('Y-m-d'),
    'startTime'           => \Carbon\Carbon::parse($appointment->start_time)->format('H:i:s'),
    'duration'            => (int) $appointment->duration,
    'appointmentId'       => (int) $appointment->id,
    'exitUrl'             => $exitUrl,
    'startUrl'            => route('appointments.start-zoom', $appointment->id),
    'endUrl'              => "/api/appointments/{$appointment->id}/end-zoom",
    'statusUrl'           => "/api/appointments/{$appointment->id}/status",
    'csrf'                => csrf_token(),
    'zoomStarted'         => $appointment->zoom_started,
    'consultationEnded'   => $consultationEnded,
    'isProfessional'      => $isProfessional,
    'zoom'                => [
        'sdkKey'        => $sdkKey,
        'signature'     => $signature,
        'meetingNumber' => (string) $meetingId,
        // Component View usa "password" en minúscula (Client View usaba "passWord")
        'password'      => (string) ($password ?? ''),
        'userName'      => $user->name,
        'userEmail'     => $user->email,
        // ZAK: obligatorio solo para INICIAR la reunión como host (role 1)
        'zak'           => $isProfessional ? ($zak ?? null) : null,
    ],
];
@endphp

<x-guest-layout
    meta_title="Consulta Virtual en Vivo {{ $appointment->reference }} | OpenDoctorOnline"
    meta_description="Accede a tu consulta médica virtual en tiempo real. Conecta con tu especialista de forma segura a través de nuestro consultorio digital."
>
<style>
    [x-cloak] { display: none !important; }
    .toast-slide-in {
        animation: slideInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .toast-slide-out {
        animation: slideOutDown 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    @keyframes slideInUp {
        from { transform: translateY(100px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    @keyframes slideOutDown {
        from { transform: translateY(0); opacity: 1; }
        to { transform: translateY(100px); opacity: 0; }
    }
    .pulse-ring {
        animation: pulseRing 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulseRing {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
</style>

<div class="max-w-7xl mx-auto py-4 md:py-8 px-3 md:px-4 mt-2 md:mt-6"
     x-data="telemedicineRoom(@js($cfg))"
     x-init="initRoom()">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-3 md:p-4 rounded-lg shadow-sm border border-gray-100 mb-4 md:mb-6 gap-3">
        <div class="flex-1 min-w-0">
            <h1 class="text-lg md:text-xl font-bold text-gray-800 truncate">Consultorio Virtual en Vivo</h1>
            <p class="text-xs md:text-sm text-gray-500 truncate">
                Paciente: <span class="font-medium text-gray-700">{{ $appointment->patient->user->name }}</span>
            </p>
        </div>
        <div class="flex items-center space-x-2 font-semibold text-xs md:text-sm transition-all duration-300" :class="timerClass">
            <svg class="w-4 h-4 md:w-5 md:h-5 flex-shrink-0" :class="isEnding ? 'animate-pulse' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span x-text="timerText" x-cloak>Calculando...</span>
        </div>
        @if ($isProfessional)
            <button @click="if (confirm('¿Estás seguro de que deseas dar por terminada la teleconsulta?')) forceCloseZoomMeeting()"
                    class="px-3 md:px-4 py-2 bg-red-600 text-white hover:bg-red-700 text-xs md:text-sm font-medium rounded-lg transition shadow-sm whitespace-nowrap">
                Finalizar Consulta
            </button>
        @else
            <a href="{{ route('admin.dashboard') }}"
               class="px-3 md:px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 text-xs md:text-sm font-medium rounded-lg transition">
                Salir de la sala
            </a>
        @endif
    </div>

    {{-- GRID PRINCIPAL --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">

        {{-- VIDEO DE ZOOM --}}
        <div class="lg:col-span-2 bg-black rounded-xl overflow-hidden shadow-lg relative min-h-[400px] md:min-h-[600px] flex items-center justify-center"
             style="min-height: clamp(400px, 70vh, 640px);">
            @if (!$appointment->zoom_started)
                @if ($isProfessional)
                    <div class="text-center px-4">
                        <div class="w-20 h-20 mx-auto mb-4 bg-green-500 rounded-full flex items-center justify-center animate-pulse">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="text-white mb-4 text-base md:text-lg font-semibold">Consulta lista para iniciar</p>
                        <button @click="startConsultation()"
                                class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg transition shadow-lg">
                            Permitir acceso al paciente / Iniciar videollamada
                        </button>
                    </div>
                @else
                    <div class="text-center text-white px-4 py-4">
                        <div class="w-20 h-20 mx-auto mb-4 bg-gray-700 rounded-full flex items-center justify-center">
                            <svg class="w-10 h-10 text-gray-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-lg font-semibold">Esperando a que el especialista inicie...</p>
                        <p class="text-sm text-gray-300 mt-2">Recibirás una notificación cuando la consulta comience.</p>
                    </div>
                @endif
            @elseif ($consultationEnded)
                <div class="text-center text-white p-8">
                    <svg class="w-16 h-16 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h4 class="text-xl font-semibold mb-2">Consulta Finalizada</h4>
                    <p class="text-sm text-gray-300 mb-4">Esta consulta ha sido completada.</p>
                    <a href="{{ route('partner.patients.show', $appointment->patient->id) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                        📋 Ir a Historia Clínica
                    </a>
                </div>
            @else
                <div id="meetingSDKElement" class="w-full h-full absolute inset-0" x-ref="zoomContainer"></div>
                <div x-show="zoomError" x-cloak
                     class="absolute inset-x-0 bottom-0 z-30 m-3 rounded-lg bg-red-600 bg-opacity-95 p-3 text-xs text-white">
                    <p class="font-semibold" x-text="zoomError"></p>
                    <p class="mt-1 opacity-90 break-words" x-show="zoomErrorDetail" x-text="zoomErrorDetail"></p>
                    <button type="button" @click="retryZoom()"
                            class="mt-2 rounded bg-white bg-opacity-20 px-3 py-1 font-medium hover:bg-opacity-30">
                        Reintentar
                    </button>
                </div>
                <div x-show="zoomJoining && !zoomInitialized && !zoomError" x-cloak
                     class="absolute inset-0 z-10 flex items-center justify-center pointer-events-none">
                    <div class="text-center text-white px-4">
                        <svg class="w-10 h-10 mx-auto mb-3 animate-spin text-blue-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <p class="text-sm font-medium">Conectando con la videollamada…</p>
                    </div>
                </div>
                <div x-show="waitingForHost" x-cloak
                     class="absolute inset-0 z-10 flex items-center justify-center pointer-events-none">
                    <div class="text-center text-white px-4">
                        <svg class="w-10 h-10 mx-auto mb-3 animate-spin text-green-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <p class="text-sm font-semibold">Esperando a que el doctor inicie la videollamada…</p>
                        <p class="text-xs text-gray-300 mt-1">Te conectaremos automáticamente.</p>
                    </div>
                </div>
                <div x-show="notStartedYet" x-cloak
                     class="absolute inset-0 bg-black bg-opacity-90 flex items-center justify-center z-20">
                    <div class="text-center text-white p-6 md:p-8">
                        <svg class="w-16 h-16 mx-auto mb-4 animate-pulse text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-base md:text-lg font-semibold mb-2">
                            {{ $isProfessional ? 'Sala iniciada. Esperando el horario programado...' : 'El doctor ha iniciado la sala. Esperando el horario...' }}
                        </p>
                        <p class="text-sm text-gray-300" x-text="timerText"></p>
                    </div>
                </div>
            @endif
        </div>

        {{-- SIDEBAR --}}
        <div class="space-y-4 md:space-y-6">
            @if ($isProfessional && !$consultationEnded)

                {{-- Ficha de consulta --}}
                <div class="bg-white rounded-xl shadow-md border border-gray-100 p-5">
                    <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wider mb-3">Ficha de Consulta</h3>
                    <div class="space-y-2 text-xs">
                        <p><span class="text-gray-400">Referencia:</span> <span class="font-medium text-gray-800">{{ $appointment->reference }}</span></p>
                        <p><span class="text-gray-400">Médico:</span> <span class="font-medium text-gray-800">{{ $appointment->doctor->user->name }}</span></p>
                        <p><span class="text-gray-400">Servicio:</span> <span class="text-gray-800">{{ $appointment->service->name }}</span></p>
                        <p><span class="text-gray-400">Fecha:</span> <span class="text-gray-800">{{ ucfirst(\Carbon\Carbon::parse($appointment->date)->locale('es')->isoFormat('dddd D [de] MMMM')) }}</span></p>
                        <p class="pt-2">
                            <a href="{{ route('partner.patients.show', $appointment->patient->id) }}"
                               target="_blank"
                               class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700 font-medium text-xs underline">
                                📋 Abrir Historia Clínica
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        </p>
                    </div>
                </div>

                {{-- Archivos --}}
                <section x-data class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="font-semibold text-gray-900 text-sm uppercase">Archivos de la reunión</h3>
                        @if ($isPatient && !$consultationEnded)
                            <button type="button" x-show="!finished" x-cloak @click="$store.roomFiles.open()"
                                    class="inline-flex flex-shrink-0 items-center gap-1.5 whitespace-nowrap rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-blue-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0l-4 4m4-4l4 4M4 20h16"/>
                                </svg>
                                Subir archivo
                            </button>
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
                </section>

                {{-- ============================================= --}}
                {{-- ASISTENTE IA + FORMULARIO SOAP (AJAX)         --}}
                {{-- ============================================= --}}
                <div class="bg-white rounded-xl shadow-md border border-gray-100 p-5 space-y-5">

                    {{-- ── 1. ASISTENTE IA (Consultation Scribe) ── --}}
                    <div x-data="consultationScribe({
                        patientId: {{ $appointment->patient->id }},
                        appointmentId: {{ $appointment->id }},
                        uploadUrl: '{{ route('partner.patients.consultation-audio.upload', $appointment->patient->id) }}',
                        statusUrlBase: '{{ url('partner/consultation-audio') }}',
                        notifyPendingUrl: '{{ route('partner.patients.consultation-audio.notify-pending', $appointment->patient->id) }}',
                        recordingId: 'rec_{{ $appointment->patient->id }}_{{ $appointment->id }}',
                    })">
                        <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wider mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 11a7 7 0 01-14 0m7 7v3m-3.5 0h7M12 14a3 3 0 003-3V5a3 3 0 10-6 0v6a3 3 0 003 3z"/>
                            </svg>
                            Asistente IA
                        </h3>

                        <template x-if="state === 'idle'">
                            <button type="button" @click="startRecording()"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm py-2 px-3 rounded-lg transition inline-flex items-center justify-center gap-2 shadow-lg">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/></svg>
                                Iniciar grabación
                            </button>
                        </template>

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
                                    Detener y generar nota
                                </button>
                            </div>
                        </template>

                        <template x-if="state === 'uploading' || state === 'transcribing' || state === 'structuring'">
                            <div class="flex items-center gap-2 text-xs text-gray-600">
                                <svg class="animate-spin h-3 w-3 text-blue-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="statusLabel"></span>
                            </div>
                        </template>

                        <template x-if="state === 'ready'">
                            <div class="rounded-lg bg-green-50 border border-green-200 p-2 text-xs text-green-800 font-medium flex items-center justify-between">
                                <span>✓ Nota generada — revisa los campos SOAP abajo.</span>
                                <button type="button" @click="reset()" class="text-green-700 underline ml-2">Grabar otra vez</button>
                            </div>
                        </template>

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

                    {{-- ── 2. FORMULARIO SOAP (con AJAX + detección IA) ── --}}
                    <div x-data="{ enviando: false, mensaje: '', tipo: '', iaAsistida: false }" class="border-t border-gray-100 pt-4">

                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wider">Nota de Evolución (SOAP)</h3>
                            {{-- Badge que aparece cuando la IA prellena el formulario --}}
                            <div id="ai-assisted-badge" x-show="iaAsistida" x-cloak
                                 class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Asistido por IA
                            </div>
                        </div>

                        {{-- Alertas dinámicas de éxito/error --}}
                        <div x-show="mensaje" x-cloak x-transition
                             :class="tipo === 'success' ? 'bg-green-50 text-green-800 border-green-200' : 'bg-red-50 text-red-800 border-red-200'"
                             class="p-2 mb-3 rounded-lg border flex items-center justify-between text-xs">
                            <span x-text="mensaje"></span>
                            <button @click="mensaje = ''" class="text-current opacity-60 hover:opacity-100">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <form id="evolution-note-form"
                              @submit.prevent="
                                enviando = true;
                                mensaje = '';
                                const formData = new FormData($el);

                                fetch('{{ route('partner.patients.store-history', $appointment->patient->id) }}', {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    body: formData
                                })
                                .then(res => {
                                    if (!res.ok) throw new Error('Error en el servidor');
                                    return res.json();
                                })
                                .then(data => {
                                    enviando = false;
                                    tipo = data.success ? 'success' : 'error';
                                    mensaje = data.message || (data.success ? 'Nota guardada exitosamente.' : 'No se pudo guardar la nota.');
                                    if (data.success) {
                                        setTimeout(() => { mensaje = ''; }, 4000);
                                    }
                                })
                                .catch(err => {
                                    enviando = false;
                                    tipo = 'error';
                                    mensaje = 'Error de conexión o validación. Revisa los campos.';
                                    console.error('Error al guardar nota:', err);
                                })
                              "
                              class="space-y-3">
                            @csrf

                            {{-- Campos ocultos de contexto --}}
                            <input type="hidden" name="appointment_id" value="{{ $appointment->id }}">
                            <input type="hidden" name="ai_assisted" :value="iaAsistida ? '1' : '0'">
                            <input type="hidden" name="status" value="signed">

                            {{-- Tipo de Entrada --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Tipo de Registro <span class="text-red-600">*</span></label>
                                <select name="entry_type" required
                                        class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    <option value="consultation" {{ old('entry_type', 'consultation') == 'consultation' ? 'selected' : '' }}>Consulta</option>
                                    <option value="follow_up" {{ old('entry_type') == 'follow_up' ? 'selected' : '' }}>Seguimiento</option>
                                    <option value="emergency" {{ old('entry_type') == 'emergency' ? 'selected' : '' }}>Urgencia</option>
                                    <option value="note" {{ old('entry_type') == 'note' ? 'selected' : '' }}>Nota</option>
                                </select>
                                @error('entry_type')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Código CIE-10 --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Código CIE-10</label>
                                <input type="text" name="cie10_code" id="cie10_code"
                                       value="{{ old('cie10_code') }}"
                                       placeholder="Ej: J45.9"
                                       class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                @error('cie10_code')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- S: Subjetivo --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">
                                    Subjetivo <span class="font-bold text-red-700">(S)</span>
                                    <span class="text-gray-400 font-normal">- Síntomas y motivo</span>
                                </label>
                                <textarea id="soap_subjective" name="soap_subjective" rows="2"
                                          placeholder="Síntomas reportados por el paciente..."
                                          class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none">{{ old('soap_subjective') }}</textarea>
                                @error('soap_subjective')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- O: Objetivo --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">
                                    Objetivo <span class="font-bold text-red-700">(O)</span>
                                    <span class="text-gray-400 font-normal">- Hallazgos clínicos</span>
                                </label>
                                <textarea id="soap_objective" name="soap_objective" rows="2"
                                          placeholder="Signos vitales, examen físico..."
                                          class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none">{{ old('soap_objective') }}</textarea>
                                @error('soap_objective')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- A: Evaluación / Diagnóstico (OBLIGATORIO) --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">
                                    Evaluación / Diagnóstico <span class="font-bold text-red-700">(A) *</span>
                                </label>
                                <textarea id="soap_assessment" name="soap_assessment" rows="2" required
                                          placeholder="Impresión diagnóstica..."
                                          class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none">{{ old('soap_assessment') }}</textarea>
                                @error('soap_assessment')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- P: Plan --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">
                                    Plan <span class="font-bold text-red-700">(P)</span>
                                    <span class="text-gray-400 font-normal">- Tratamiento y seguimiento</span>
                                </label>
                                <textarea id="soap_plan" name="soap_plan" rows="3"
                                          placeholder="Tratamiento recomendado y seguimiento..."
                                          class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none">{{ old('soap_plan') }}</textarea>
                                @error('soap_plan')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit"
                                    :disabled="enviando"
                                    class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 disabled:cursor-not-allowed text-white font-bold text-sm py-2 px-3 rounded-lg transition shadow-lg flex items-center justify-center gap-2">
                                <svg x-show="enviando" x-cloak class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="enviando ? 'Guardando...' : 'Guardar Nota'"></span>
                            </button>
                        </form>
                    </div>
                </div>

            @elseif ($isProfessional && $consultationEnded)

                {{-- Vista del doctor cuando la consulta ya terminó --}}
                <div class="bg-gray-50 rounded-xl border border-gray-200 p-5 text-center">
                    <svg class="w-16 h-16 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h4 class="font-semibold text-gray-700 mb-2">Consulta Finalizada</h4>
                    <p class="text-sm text-gray-600 mb-4">
                        Esta consulta ha sido completada. Si necesitas agregar o editar notas clínicas, hazlo desde la historia clínica del paciente.
                    </p>
                    <a href="{{ route('partner.patients.show', $appointment->patient->id) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                         Ir a Historia Clínica
                    </a>
                </div>

            @else

                {{-- Vista del paciente --}}
                <div class="bg-white rounded-xl shadow-md border border-gray-100 p-4 md:p-5">
                    <div class="p-3 bg-green-50 text-green-800 rounded-lg text-xs border border-green-200">
                        <p class="font-semibold mb-1">Sala de Espera Virtual</p>
                        <p class="text-gray-600">Por seguridad de datos médicos, permanecerás aquí hasta que el doctor autorice tu ingreso a la videollamada.</p>
                    </div>
                </div>

            @endif
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- TOAST DE AVISO: 5 MINUTOS ANTES DEL FIN --}}
    {{-- ============================================ --}}
    <div x-show="showTimeWarning"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-8"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-8"
         class="fixed bottom-4 left-1/2 -translate-x-1/2 sm:left-auto sm:right-4 sm:translate-x-0 z-50 w-[calc(100%-2rem)] sm:w-96 max-w-md"
         @click="dismissWarning()">
        <div class="bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-2xl shadow-2xl p-4 flex items-start gap-3 border border-amber-400">
            <div class="flex-shrink-0 w-10 h-10 bg-white/20 rounded-full flex items-center justify-center pulse-ring">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-sm mb-0.5">⏰ Quedan 5 minutos</p>
                <p class="text-xs text-amber-50 leading-tight">
                    La consulta está por finalizar. Aprovecha para resolver las últimas dudas.
                </p>
            </div>
            <button @click.stop="dismissWarning()"
                    class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-white/20 hover:bg-white/30 transition"
                    aria-label="Cerrar aviso">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Modal para subir archivos --}}
    @include('appointments.partials.room-files-modal', ['appointment' => $appointment])

    {{-- ============================================ --}}
    {{-- SDK DE ZOOM (CDN OFICIAL)                    --}}
    {{-- ============================================ --}}
    {{-- Meeting SDK Web (Component View) desde el CDN oficial de Zoom, versión fija.
         Versiones probadas con este código: 4.0.0 y 6.2.0. Para cambiar de versión modifica solo $zoomSdkVersion.
         Hasta 4.x el SDK necesita redux, redux-thunk y lodash; desde 5.x basta con React y React DOM. --}}
    @php
        $zoomSdkVersion = '6.2.0';
        $zoomCdn = "https://source.zoom.us/{$zoomSdkVersion}";
    @endphp
    <script src="{{ $zoomCdn }}/lib/vendor/react.min.js"></script>
    <script src="{{ $zoomCdn }}/lib/vendor/react-dom.min.js"></script>
    @if (version_compare($zoomSdkVersion, '5.0.0', '<'))
        <script src="{{ $zoomCdn }}/lib/vendor/redux.min.js"></script>
        <script src="{{ $zoomCdn }}/lib/vendor/redux-thunk.min.js"></script>
        <script src="{{ $zoomCdn }}/lib/vendor/lodash.min.js"></script>
    @endif
    <script src="{{ $zoomCdn }}/zoom-meeting-embedded-{{ $zoomSdkVersion }}.min.js"></script>

    <script>
        // Component View expone el global "ZoomMtgEmbedded" (NO "ZoomMtg", que es Client View)
        window.__zoomReady = false;
        window.addEventListener('load', () => {
            if (typeof ZoomMtgEmbedded !== 'undefined') {
                window.__zoomReady = true;
                console.log('✅ ZoomMtgEmbedded (Component View) cargado correctamente');
            } else {
                console.error('❌ ZoomMtgEmbedded NO está definido. Revisa en la pestaña Network que source.zoom.us/*/zoom-meeting-embedded-*.min.js responda 200 (CSP, adblock o firewall pueden bloquearlo).');
            }
        });
    </script>

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
            finished: config.consultationEnded,
            notStartedYet: false,
            zoomInitialized: false,
            zoomJoining: false,
            zoomError: null,
            zoomErrorDetail: null,
            waitingForHost: false,
            showTimeWarning: false,
            warningDismissed: false,
            warningAutoHideTimer: null,

            get isEnding() {
                return this.startTime !== null && new Date() >= this.startTime && this.minutesRemaining < 5;
            },

            initRoom() {
                const cleanDate = String(config.date).trim();
                const cleanTime = String(config.startTime).trim();
                this.startTime = new Date(`${cleanDate}T${cleanTime}`);
                this.endTime = new Date(this.startTime.getTime());
                this.endTime.setMinutes(this.endTime.getMinutes() + parseInt(config.duration));
                this.notStartedYet = new Date() < this.startTime;
                
                this.startCountdown();
                this.listenToMeetingEnd();
                
                // ✅ INICIALIZAR ZOOM CON ESPERA INTELIGENTE
                if (config.zoomStarted && !config.consultationEnded) {
                    this.waitForZoomAndInit();
                }
            },

            // Espera a que ZoomMtgEmbedded (Component View) esté definido
            waitForZoomAndInit(maxAttempts = 20) {
                let attempts = 0;
                const tryInit = () => {
                    if (typeof ZoomMtgEmbedded !== 'undefined') {
                        console.log('✅ Zoom SDK (Component View) detectado, inicializando...');
                        this.initZoomSDK();
                    } else if (attempts < maxAttempts) {
                        attempts++;
                        console.log(`⏳ Esperando SDK de Zoom... intento ${attempts}/${maxAttempts}`);
                        setTimeout(tryInit, 500);
                    } else {
                        this.setZoomError(
                            'No se pudo cargar el SDK de Zoom. Recarga la página.',
                            'ZoomMtgEmbedded no está definido tras 10 segundos.'
                        );
                    }
                };
                tryInit();
            },

            setZoomError(message, detail = null) {
                this.zoomError = message;
                this.zoomErrorDetail = detail;
                this.zoomJoining = false;
                console.error('❌ Zoom:', message, detail || '');
            },

            retryZoom() {
                window.location.reload();
            },

            triggerTimeWarning() {
                if (this.warningDismissed) return;
                if (this.showTimeWarning) return;
                this.showTimeWarning = true;
                this.warningAutoHideTimer = setTimeout(() => {
                    this.showTimeWarning = false;
                }, 15000);
            },

            dismissWarning() {
                this.showTimeWarning = false;
                this.warningDismissed = true;
                if (this.warningAutoHideTimer) clearTimeout(this.warningAutoHideTimer);
            },

            initZoomSDK() {
                if (this.zoomInitialized || this.zoomJoining) return;

                if (typeof ZoomMtgEmbedded === 'undefined') {
                    this.setZoomError('SDK de Zoom no disponible', 'ZoomMtgEmbedded no está definido.');
                    return;
                }

                const zoomContainer = this.$refs.zoomContainer || document.getElementById('meetingSDKElement');
                if (!zoomContainer) {
                    this.setZoomError('Contenedor de video no encontrado');
                    return;
                }

                const z = config.zoom || {};
                const missing = ['sdkKey', 'signature', 'meetingNumber', 'userName'].filter(k => !z[k]);
                if (missing.length) {
                    this.setZoomError('Faltan datos para conectar con Zoom', 'Parámetros vacíos: ' + missing.join(', '));
                    return;
                }

                // Parámetros de client.join() según Component View
                const joinParams = {
                    sdkKey: z.sdkKey, // opcional/deprecado desde 4.0.0, requerido en 3.x; inofensivo en 4.x y 6.x
                    signature: z.signature, // debe llevar appKey (Client ID)
                    meetingNumber: String(z.meetingNumber).replace(/\s+/g, ''),
                    password: z.password || '',
                    userName: z.userName,
                    userEmail: z.userEmail || '',
                };
                if (config.isProfessional) {
                    if (z.zak) {
                        joinParams.zak = z.zak; // necesario para INICIAR la reunión como host
                    } else {
                        console.warn('⚠️ Profesional sin ZAK: si la firma es role=1 (host), Zoom rechazará el inicio.');
                    }
                }

                console.log('ℹ️ Zoom join params (sin secretos):', {
                    meetingNumber: joinParams.meetingNumber,
                    hasPassword: !!joinParams.password,
                    hasZak: !!joinParams.zak,
                    isProfessional: !!config.isProfessional,
                    sdkKey: z.sdkKey,
                });

                this.zoomJoining = true;
                this.zoomError = null;
                this.zoomErrorDetail = null;

                // Watchdog: si en 30 s no hay join ni error, se avisa en pantalla en vez de quedar en negro
                let zoomStage = 'init() - cargando el SDK y sus assets';
                const watchdog = setTimeout(() => {
                    if (!this.zoomInitialized && !this.zoomError) {
                        this.setZoomError(
                            'Zoom no respondió después de 30 segundos',
                            `Se quedó en: ${zoomStage}. Revisa en Network si hay peticiones a zoom.us bloqueadas o pendientes (CSP, adblock, firewall) y la consola.`
                        );
                    }
                }, 30000);

                // El cliente se guarda en window para evitar que Alpine lo envuelva en un Proxy
                let client;
                try {
                    client = ZoomMtgEmbedded.createClient();
                } catch (err) {
                    clearTimeout(watchdog);
                    this.setZoomError('No se pudo crear el cliente de Zoom', err && err.message);
                    return;
                }
                window.__zoomClient = client;

                // Component View dibuja video + toolbar dentro de zoomAppRoot: damos un tamaño explícito al video
                // y dejamos ~80px de alto para la barra de controles.
                const rect = zoomContainer.getBoundingClientRect();
                const viewWidth  = Math.max(320, Math.floor(rect.width));
                const viewHeight = Math.max(240, Math.floor(rect.height) - 80);
                console.log('📐 Contenedor Zoom:', { width: rect.width, height: rect.height, viewWidth, viewHeight });

                Promise.resolve()
                .then(() => client.init({
                    zoomAppRoot: zoomContainer,
                    language: 'es-ES',
                    patchJsMedia: true,
                    // Logs detallados del SDK: agrega ?zoomdebug=1 a la URL de la sala
                    debug: new URLSearchParams(window.location.search).has('zoomdebug'),
                    customize: {
                        video: {
                            viewSizes: { default: { width: viewWidth, height: viewHeight } },
                        },
                    },
                }))
                .then(() => {
                    zoomStage = 'join() - conectando con los servidores de Zoom';
                    console.log('✅ client.init() resuelto, iniciando client.join()...');
                    // El listener se registra DESPUÉS de init() y nunca debe impedir el join
                    try {
                        client.on('connection-change', (payload) => {
                            console.log('🔌 Zoom connection-change:', payload);
                        });
                    } catch (err) {
                        console.warn('⚠️ No se pudo registrar connection-change (no es crítico):', err);
                    }
                    return client.join(joinParams);
                })
                .then(() => {
                    clearTimeout(watchdog);
                    console.log('✅ Conexión de video establecida');
                    this.zoomInitialized = true;
                    this.zoomJoining = false;
                    this.zoomError = null;
                    this.zoomErrorDetail = null;
                })
                .catch((err) => {
                    clearTimeout(watchdog);
                    console.error('❌ Error al unirse a Zoom:', err);

                    const hints = {
                        3001: 'La reunión no existe (meetingNumber incorrecto o reunión eliminada).',
                        3003: 'No eres el host de esta reunión (role/ZAK).',
                        3004: 'Contraseña de la reunión incorrecta o vacía.',
                        3008: 'La reunión aún no ha sido iniciada por el host.',
                        3265: 'Error de token (ZAK/OBF inválido o vencido).',
                        3705: 'La firma (signature) expiró.',
                        3708: 'Role incorrecto en la firma.',
                        3709: 'No se encontró al host de la reunión.',
                        3712: 'Firma inválida (Client ID/Secret, payload o meetingNumber no coinciden).',
                        3713: 'Sin permisos para esta reunión.',
                        4003: 'Parámetro inválido en join().',
                        4004: 'La reunión ya terminó.',
                        4011: 'Reunión de una cuenta externa: la app SDK debe estar publicada/autorizada (ZAK/OBF).',
                    };
                    const code = err && (err.errorCode ?? err.code);
                    const reason = err && (err.reason || err.message);
                    const detail = [
                        code !== undefined ? `Código ${code}` : null,
                        reason || null,
                        hints[code] || null,
                    ].filter(Boolean).join(' · ');

                    // 3008 (reunión no iniciada) en un paciente NO es un fallo: el doctor aún no la inicia.
                    // Se recrea el cliente y se reintenta el join cada 8 s hasta que el host la inicie.
                    if (code === 3008 && !config.isProfessional && !this.finished) {
                        console.log('⏳ La reunión aún no ha sido iniciada por el doctor. Reintentando en 8 s...');
                        this.waitingForHost = true;
                        this.zoomJoining = false;
                        this.zoomError = null;
                        this.zoomErrorDetail = null;
                        setTimeout(() => {
                            try { ZoomMtgEmbedded.destroyClient(); } catch (e) { console.warn('destroyClient falló:', e); }
                            this.waitingForHost = false;
                            this.initZoomSDK();
                        }, 8000);
                        return;
                    }

                    this.setZoomError('No se pudo conectar a la videollamada', detail || null);
                });
            },

            startCountdown() {
                let interval = null;
                let warningTriggered = false;

                const updateTimer = () => {
                    const now = new Date();
                    if (now < this.startTime) {
                        this.minutesRemaining = config.duration;
                        this.notStartedYet = true;
                        const timeToStart = this.startTime - now;
                        const oneDayInMs = 24 * 60 * 60 * 1000;
                        if (timeToStart > oneDayInMs) {
                            const days = Math.ceil(timeToStart / oneDayInMs);
                            this.timerText = `Faltan ${days} ${days === 1 ? 'día' : 'días'}`;
                        } else {
                            const totalSeconds = Math.floor(timeToStart / 1000);
                            const hours = Math.floor(totalSeconds / 3600);
                            const minutes = Math.floor((totalSeconds % 3600) / 60);
                            const seconds = totalSeconds % 60;
                            const pad = (num) => String(num).padStart(2, '0');
                            this.timerText = `Inicia en: ${pad(hours)}h ${pad(minutes)}m ${pad(seconds)}s`;
                        }
                        this.timerClass = 'bg-blue-50 text-blue-600 border border-blue-100 animate-pulse font-semibold px-4 py-2 rounded-full';
                        return;
                    }

                    this.notStartedYet = false;
                    const difference = this.endTime - now;

                    if (difference <= 0) {
                        if (interval) clearInterval(interval);
                        this.timerText = 'Consulta finalizada';
                        this.timerClass = 'bg-red-100 text-red-700 border border-red-300 font-bold px-4 py-2 rounded-full';
                        this.minutesRemaining = 0;
                        this.finished = true;
                        this.forceCloseZoomMeeting();
                        return;
                    }

                    const totalSeconds = Math.floor(difference / 1000);
                    const minutes = Math.floor(totalSeconds / 60);
                    const seconds = totalSeconds % 60;
                    this.minutesRemaining = minutes;
                    this.timerText = `Tiempo restante: ${String(minutes).padStart(2, '0')}m ${String(seconds).padStart(2, '0')}s`;

                    if (minutes === 5 && !warningTriggered) {
                        warningTriggered = true;
                        this.triggerTimeWarning();
                    }

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
                .then(data => {
                    this.finished = true;
                    setTimeout(() => { window.location.href = config.exitUrl; }, 2000);
                })
                .catch(err => {
                    console.error('Error al cerrar reunión:', err);
                    window.location.href = config.exitUrl;
                });
            },

            listenToMeetingEnd() {
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
                    if (data.success) location.reload();
                })
                .catch(err => console.error('Error:', err));
            }
        }));
    });
</script>
</x-guest-layout>