@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Paciente',
    ]
];

switch ($patient->gender) {
    case 'male': $gender = 'Masculino'; break;
    case 'female': $gender = 'Femenino'; break;
    default: $gender = 'No especificado';
}
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <!-- Alerta de Éxito -->
    @if (session('success'))
        <div id="alert-success" class="flex items-center p-4 mb-4 text-green-800 rounded-2xl bg-green-50 border border-green-100 shadow-sm" role="alert">
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

    <!-- Alerta de Error -->
    @if (session('error'))
        <div class="flex items-center p-4 mb-4 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm" role="alert">
            <div class="text-sm font-medium">{{ session('error') }}</div>
        </div>
    @endif

    <div>
        <!-- Botón Volver -->
        <a href="{{ route('partner.appointments.index') }}" class="text-sm text-gray-500 hover:text-blue-600 flex items-center gap-2 mb-6">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
            Volver a la agenda
        </a>

        <!-- Header del Paciente -->
        <div class="bg-white rounded-3xl p-8 shadow-xl border border-gray-100 mb-8">
            <div class="flex flex-col md:flex-row gap-6 items-center">
                <div class="h-24 w-24 bg-blue-600 rounded-2xl flex items-center justify-center text-white text-3xl font-black shrink-0">
                    {{ substr($patient->user->name, 0, 1) }}
                </div>
                <div class="text-center md:text-left flex-1">
                    <div class="flex flex-wrap items-center gap-3 justify-center md:justify-start">
                        <h1 class="text-3xl font-black text-gray-800">{{ $patient->user->name }}</h1>
                        <span class="bg-blue-50 text-blue-700 text-xs font-bold px-2.5 py-1 rounded-full">
                            ID {{ $patient->identification }}
                        </span>
                    </div>
                    <p class="text-gray-500 font-medium mt-1">
                        {{ $gender }} · {{ $patient->age ? $patient->age . ' años' : 'Edad no especificada' }}
                        @if($patient->blood_type) · RH {{ $patient->blood_type }} @endif
                    </p>
                    <div class="flex flex-wrap gap-3 mt-4 justify-center md:justify-start">
                        <span class="bg-gray-100 px-3 py-1 rounded-full text-xs font-bold text-gray-600 inline-flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            {{ $patient->user->email }}
                        </span>
                        <span class="bg-gray-100 px-3 py-1 rounded-full text-xs font-bold text-gray-600 inline-flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            {{ $patient->phone ?? 'Sin teléfono' }}
                        </span>
                        @if($patient->insurance->name ?? false)
                        <span class="bg-gray-100 px-3 py-1 rounded-full text-xs font-bold text-gray-600">
                            🛡️ {{ $patient->insurance->name }}
                        </span>
                        @endif
                    </div>
                </div>

                @if($plan->can_export_history)
                <a href="{{ route('patient.pdf.clinical-history', $patient) }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-gray-50 hover:bg-gray-100 border border-gray-200 px-4 py-2.5 text-sm font-bold text-gray-700 transition shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Descargar historia clínica
                </a>
                @endif
            </div>
        </div>

        <!-- Cuerpo del Perfil -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

            <!-- Información Básica -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 space-y-6">

                <div>
                    <h3 class="font-bold text-gray-800 mb-3 border-b pb-2 text-sm uppercase tracking-wide">Identificación</h3>
                    <ul class="space-y-2.5 text-sm">
                        <li class="flex justify-between gap-3"><span class="text-gray-400">Localización</span> <span class="font-bold text-gray-700 text-right">{{ ($patient->city->name ?? null) && ($patient->department->name ?? null) ? $patient->city->name . ', ' . $patient->department->name : 'No especificado' }}</span></li>
                        <li class="flex justify-between gap-3"><span class="text-gray-400">Cumpleaños</span> <span class="font-bold text-gray-700">{{ $patient->birth_date ? \Carbon\Carbon::parse($patient->birth_date)->format('Y-m-d') : 'No especificado' }}</span></li>
                        <li class="flex justify-between gap-3"><span class="text-gray-400">Estado civil</span> <span class="font-bold text-gray-700">{{ $patient->civil_status ?? 'No especificado' }}</span></li>
                        <li class="flex justify-between gap-3"><span class="text-gray-400">Etnia</span> <span class="font-bold text-gray-700">{{ $patient->ethnicity ?? 'No especificado' }}</span></li>
                        <li class="flex justify-between gap-3"><span class="text-gray-400">Ocupación</span> <span class="font-bold text-gray-700">{{ $patient->occupation ?? 'No especificado' }}</span></li>
                        <li class="flex justify-between gap-3"><span class="text-gray-400">Zona de residencia</span> <span class="font-bold text-gray-700">{{ $patient->residence_zone ?? 'No especificado' }}</span></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-bold text-gray-800 mb-3 border-b pb-2 text-sm uppercase tracking-wide">Datos clínicos básicos</h3>
                    <ul class="space-y-2.5 text-sm">
                        <li class="flex justify-between gap-3"><span class="text-gray-400">Estatura</span> <span class="font-bold text-gray-700">{{ $patient->height ?? 'No especificado' }}</span></li>
                        <li class="flex justify-between gap-3"><span class="text-gray-400">Peso</span> <span class="font-bold text-gray-700">{{ $patient->weight ?? 'No especificado' }}</span></li>
                        <li class="flex justify-between gap-3"><span class="text-gray-400">IMC</span> <span class="font-bold text-gray-700">{{ $patient->getImcAttribute() ? $patient->getImcAttribute() . ' ' . $patient->getImcStatusAttribute() : 'No especificado' }}</span></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-bold text-gray-800 mb-3 border-b pb-2 text-sm uppercase tracking-wide">Afiliación</h3>
                    <ul class="space-y-2.5 text-sm">
                        <li class="flex justify-between gap-3"><span class="text-gray-400">Seguro médico</span> <span class="font-bold text-gray-700">{{ $patient->insurance->name ?? 'No especificado' }}</span></li>
                        <li class="flex justify-between gap-3"><span class="text-gray-400">Tipo de afiliación</span> <span class="font-bold text-gray-700">{{ $patient->affiliation_type ?? 'No especificado' }}</span></li>
                        <li class="flex justify-between gap-3"><span class="text-gray-400">Tipo de régimen</span> <span class="font-bold text-gray-700">{{ $patient->regime_type ?? 'No especificado' }}</span></li>
                        <li class="flex justify-between gap-3"><span class="text-gray-400">Nivel de SISBEN</span> <span class="font-bold text-gray-700">{{ $patient->sisben_level ?? 'No especificado' }}</span></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-bold text-gray-800 mb-3 border-b pb-2 text-sm uppercase tracking-wide">Contacto de emergencia</h3>
                    <ul class="space-y-2.5 text-sm">
                        <li class="flex justify-between gap-3"><span class="text-gray-400">Nombre</span> <span class="font-bold text-gray-700">{{ $patient->emergency_contact_name ? ucfirst($patient->emergency_contact_name) : 'No especificado' }}</span></li>
                        <li class="flex justify-between gap-3"><span class="text-gray-400">Teléfono</span> <span class="font-bold text-gray-700">{{ $patient->emergency_contact_phone ?? 'No especificado' }}</span></li>
                        <li class="flex justify-between gap-3"><span class="text-gray-400">Relación</span> <span class="font-bold text-gray-700">{{ $patient->emergency_contact_relationship ?? 'No especificado' }}</span></li>
                    </ul>
                </div>
            </div>

            <div class="md:col-span-2 bg-white rounded-3xl p-6 shadow-sm border border-gray-100 space-y-8">

                <!-- Condiciones permanentes -->
                <div>
                    <h3 class="text-gray-900 font-bold border-b pb-2 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Condiciones permanentes
                    </h3>
                    <div class="p-3 rounded-lg border bg-gray-50 border-gray-200 text-sm text-gray-700">
                        {{ $patient->permanent_conditions ?: 'No hay condiciones permanentes registradas.' }}
                    </div>
                </div>

                <!-- Alergias -->
                <div>
                    <h3 class="text-gray-900 font-bold border-b pb-2 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Alergias
                    </h3>
                    <div class="space-y-2">
                        @forelse($patient->allergies as $allergy)
                        @php
                        $severity = match($allergy->severity) {
                            'mild' => 'Leve',
                            'moderate' => 'Moderada',
                            'severe' => 'Severa',
                            default => 'No especificado',
                        };
                        @endphp
                        <div class="p-3 rounded-lg border @if($allergy->severity == 'severe') bg-red-50 border-red-200 @else bg-gray-50 border-gray-200 @endif">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-800 text-sm">{{ $allergy->name }}</span>
                                <span class="text-xs uppercase px-2 py-1 rounded-full @if($allergy->severity == 'severe') bg-red-200 text-red-800 @else bg-gray-200 text-gray-700 @endif">
                                    {{ $severity }}
                                </span>
                            </div>
                            @if($allergy->reaction)
                            <p class="text-xs text-gray-600 mt-1 italic">{{ $allergy->reaction }}</p>
                            @endif
                        </div>
                        @empty
                        <p class="text-sm text-gray-400 italic">No hay alergias registradas.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Medicamentos -->
                <div>
                    <h3 class="text-gray-900 font-bold border-b pb-2 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        Medicamentos
                    </h3>
                    <div class="space-y-2">
                        @forelse($patient->medications as $medication)
                        <div class="p-3 rounded-lg border bg-gray-50 border-gray-200">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-800 text-sm">{{ $medication->name }}</span>
                                <span class="text-xs uppercase px-2 py-1 rounded-full bg-blue-200 text-blue-800">
                                    {{ $medication->dosage }}
                                </span>
                            </div>
                            @if($medication->frequency)
                            <p class="text-xs text-gray-600 mt-1 italic">{{ $medication->frequency }}</p>
                            @endif
                            @if($medication->notes)
                            <p class="text-xs text-gray-600 mt-1 italic">{{ $medication->notes }}</p>
                            @endif
                        </div>
                        @empty
                        <p class="text-sm text-gray-400 italic">No hay medicamentos registrados.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Cirugías -->
                <div>
                    <h3 class="text-gray-900 font-bold border-b pb-2 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m-4-4h8m-9 9h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        Cirugías
                    </h3>
                    <div class="space-y-2">
                        @forelse($patient->surgeries as $surgery)
                        <div class="p-3 rounded-lg border bg-gray-50 border-gray-200">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-800 text-sm">{{ $surgery->name }}</span>
                                <span class="text-xs uppercase px-2 py-1 rounded-full bg-green-200 text-green-800">
                                    {{ \Carbon\Carbon::parse($surgery->date)->format('Y-m-d') }}
                                </span>
                            </div>
                            @if($surgery->observations)
                            <p class="text-xs text-gray-600 mt-1 italic">{{ $surgery->observations }}</p>
                            @endif
                            @if($surgery->anesthesia_complications)
                            <p class="text-xs text-gray-600 mt-1 italic"><span class="font-bold">Complicaciones anestésicas:</span> {{ $surgery->anesthesia_details }}</p>
                            @endif
                            @if($surgery->notes)
                            <p class="text-xs text-gray-600 mt-1 italic">{{ $surgery->notes }}</p>
                            @endif
                        </div>
                        @empty
                        <p class="text-sm text-gray-400 italic">No hay cirugías registradas.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Antecedentes familiares -->
                <div>
                    <h3 class="text-gray-900 font-bold border-b pb-2 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4"/></svg>
                        Antecedentes familiares
                    </h3>
                    <div class="space-y-2">
                        @forelse($patient->familyHistories as $history)
                        <div class="p-3 rounded-lg border bg-gray-50 border-gray-200">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-800 text-sm">{{ $history->condition }}</span>
                                <span class="text-xs uppercase px-2 py-1 rounded-full bg-purple-200 text-purple-800">
                                    {{ $history->relationship }}
                                </span>
                            </div>
                            @if($history->notes)
                            <p class="text-xs text-gray-600 mt-1 italic">{{ $history->notes }}</p>
                            @endif
                        </div>
                        @empty
                        <p class="text-sm text-gray-400 italic">No hay antecedentes familiares registrados.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Últimas consultas -->
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 mt-8">
            <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Últimas consultas</h3>
            <div class="space-y-2">
                @forelse($patient->appointments as $history)
                @php
                $status = match($history->status_label ?? $history->status) {
                    'confirmed' => 'Confirmada',
                    'pending' => 'Pendiente',
                    'cancelled' => 'Cancelada',
                    'completed' => 'Completada',
                    default => ucfirst($history->status),
                };
                @endphp
                <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-xl transition">
                    <div>
                        <p class="font-bold text-gray-800 text-sm">{{ $history->service->name }}</p>
                        @if($history->notes)
                        <p class="text-sm text-gray-400">{{ $history->notes }}</p>
                        @endif
                        <p class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($history->date)->translatedFormat('d \d\e F, Y') }} a las {{ $history->start_time }}</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $history->status === 'confirmed' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $history->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $history->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                        {{ $history->status === 'completed' ? 'bg-blue-100 text-blue-800' : '' }}">
                        {{ $status }}
                    </span>
                </div>
                @empty
                <p class="text-sm text-gray-400 italic">No hay registros previos.</p>
                @endforelse
            </div>
        </div>

        <!-- ============================================= -->
        <!-- Asistente de Consulta (AI Scribe) -->
        <!-- ============================================= -->
        @if ($allowed)
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 mt-8"
            x-data="consultationScribe({
                patientId: {{ $patient->id }},
                appointmentId: {{ $appointmentId ?? 'null' }},
                uploadUrl: '{{ route('partner.patients.consultation-audio.upload', $patient->id) }}',
                statusUrlBase: '{{ url('partner/consultation-audio') }}',
                notifyPendingUrl: '{{ route('partner.patients.consultation-audio.notify-pending', $patient->id) }}',
                recordingId: 'rec_{{ $patient->id }}_{{ $appointmentId ?? 'none' }}',
            })">
            <div class="flex items-center justify-between border-b pb-3 mb-4">
                <h3 class="font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-14 0m7 7v3m-3.5 0h7M12 14a3 3 0 003-3V5a3 3 0 10-6 0v6a3 3 0 003 3z"/></svg>
                    Asistente de consulta (IA)
                </h3>
                <span class="text-xs text-gray-400 font-medium">OpenDoctorOnline</span>
            </div>

            <template x-if="state === 'idle'">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-500">Graba la consulta y deja que la IA prellene la nota de evolución.</p>
                    <button type="button" @click="startRecording()"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm py-2.5 px-5 rounded-xl transition inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/></svg>
                        Iniciar grabación
                    </button>
                </div>
            </template>

            <template x-if="state === 'recording'">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-700 flex items-center gap-2">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                        </span>
                        Grabando consulta — <span x-text="elapsedLabel"></span>
                    </p>
                    <button type="button" @click="stopRecording()"
                        class="bg-gray-800 hover:bg-gray-900 text-white font-bold text-sm py-2.5 px-5 rounded-xl transition">
                        Detener y generar nota
                    </button>
                </div>
            </template>

            <template x-if="state === 'uploading' || state === 'transcribing' || state === 'structuring'">
                <div class="flex items-center gap-3 text-sm text-gray-600">
                    <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="statusLabel"></span>
                </div>
            </template>

            <template x-if="state === 'ready'">
                <div class="rounded-xl bg-green-50 border border-green-200 p-4 flex items-center justify-between">
                    <p class="text-sm text-green-800 font-medium">
                        ✓ Nota generada — revisa y ajusta los campos antes de guardar.
                    </p>
                    <button type="button" @click="reset()" class="text-xs text-green-700 underline">Grabar otra vez</button>
                </div>
            </template>

            <template x-if="state === 'upload_failed'">
                <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 flex items-center justify-between gap-4">
                    <p class="text-sm text-amber-800" x-text="errorMessage"></p>
                    <button type="button" @click="retryUpload()"
                        class="bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs py-2 px-4 rounded-lg transition shrink-0 whitespace-nowrap">
                        Reintentar subida
                    </button>
                </div>
            </template>

            <template x-if="state === 'error'">
                <div class="rounded-xl bg-red-50 border border-red-200 p-4 flex items-center justify-between">
                    <p class="text-sm text-red-700" x-text="errorMessage"></p>
                    <button type="button" @click="reset()" class="text-xs text-red-700 underline">Reintentar</button>
                </div>
            </template>
        </div>
        
        <!-- Nota de Evolución Actual -->
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 mt-6">
            <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Nota de evolución actual</h3>
            <form id="evolution-note-form" action="{{ route('partner.patients.store-history', $patient->id) }}" method="POST">
                @csrf

                @include('partner.patients.partials.history-form')

                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-5 rounded-xl mt-4 transition">
                    Guardar nota
                </button>
            </form>
        </div>
        @else        
        <div class="bg-teal-100 border-t-4 border-teal-500 rounded-b text-teal-900 px-4 py-3 shadow-md mb-4" role="alert">
            <div class="flex">
                <div class="py-1"><svg class="fill-current h-6 w-6 text-teal-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/></svg></div>
                <div>
                <p class="font-bold">Política de Privacidad</p>
                <p class="text-sm">Para generar las <span class="font-bold">Notas de Evolución Actual</span>, el proceso general requiere que accedas directamente desde tu agenda médica</p>
                <p class="text-sm">Las notas solo pueden registrarse el mismo día de la consulta o dentro de las primeras 48 horas.</p>
                </div>
            </div>
        </div>
        @endif
    </div>
  
    <script>
        // Helper de IndexedDB para el AI Scribe.
        // Guarda grabaciones de audio pendientes de subir, identificadas por patientId +
        // appointmentId + uploadUrl, para que sobrevivan recargas de página o cierres
        // accidentales de pestaña mientras no haya conexión.
        const AIScribeStorage = (() => {
            const DB_NAME = 'opendoctor_ai_scribe';
            const STORE_NAME = 'pending_recordings';
            const DB_VERSION = 1;

            function openDb() {
                return new Promise((resolve, reject) => {
                    const req = indexedDB.open(DB_NAME, DB_VERSION);

                    req.onupgradeneeded = (event) => {
                        const db = event.target.result;
                        if (!db.objectStoreNames.contains(STORE_NAME)) {
                            const store = db.createObjectStore(STORE_NAME, { keyPath: 'id' });
                            store.createIndex('patientId', 'patientId', { unique: false });
                        }
                    };

                    req.onsuccess = () => resolve(req.result);
                    req.onerror = () => reject(req.error);
                });
            }

            async function save(record) {
                const db = await openDb();
                return new Promise((resolve, reject) => {
                    const tx = db.transaction(STORE_NAME, 'readwrite');
                    tx.objectStore(STORE_NAME).put(record);
                    tx.oncomplete = () => resolve(record);
                    tx.onerror = () => reject(tx.error);
                });
            }

            async function remove(id) {
                const db = await openDb();
                return new Promise((resolve, reject) => {
                    const tx = db.transaction(STORE_NAME, 'readwrite');
                    tx.objectStore(STORE_NAME).delete(id);
                    tx.oncomplete = () => resolve();
                    tx.onerror = () => reject(tx.error);
                });
            }

            async function getAll() {
                const db = await openDb();
                return new Promise((resolve, reject) => {
                    const tx = db.transaction(STORE_NAME, 'readonly');
                    const req = tx.objectStore(STORE_NAME).getAll();
                    req.onsuccess = () => resolve(req.result || []);
                    req.onerror = () => reject(req.error);
                });
            }

            async function get(id) {
                const db = await openDb();
                return new Promise((resolve, reject) => {
                    const tx = db.transaction(STORE_NAME, 'readonly');
                    const req = tx.objectStore(STORE_NAME).get(id);
                    req.onsuccess = () => resolve(req.result || null);
                    req.onerror = () => reject(req.error);
                });
            }

            return { save, remove, getAll, get };
        })();
        
        document.addEventListener('alpine:init', () => {
            Alpine.data('consultationScribe', ({ patientId, appointmentId, uploadUrl, statusUrlBase, notifyPendingUrl, recordingId }) => ({
                state: 'idle', // idle | recording | uploading | transcribing | structuring | ready | error | upload_failed
                mediaRecorder: null,
                chunks: [],
                startedAt: null,
                elapsedLabel: '00:00',
                timerInterval: null,
                pollInterval: null,
                errorMessage: '',
                uploadAttempts: 0,
                maxAutoRetries: 3,
                recordingId: recordingId,

                get statusLabel() {
                    return {
                        uploading: 'Subiendo audio...',
                        transcribing: 'Transcribiendo la consulta...',
                        structuring: 'Redactando la nota...',
                    }[this.state] || 'Procesando...';
                },

                async init() {
                    const pending = await AIScribeStorage.get(this.recordingId);
                    if (pending && pending.status === 'pending_upload') {
                        // Reconstruimos los Blob a partir de los ArrayBuffers guardados.
                        this.chunks = (pending.chunkBuffers || []).map(
                            buf => new Blob([buf], { type: 'audio/webm' })
                        );
                        this.uploadAttempts = pending.attempts || 0;
                        this.state = 'upload_failed';
                        this.errorMessage = 'Hay una grabación de una consulta anterior que no se pudo subir. Haz clic en reintentar.';
                    }
                },

                async startRecording() {
                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                        this.chunks = [];
                        this.uploadAttempts = 0;
                        this.mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });

                        this.mediaRecorder.ondataavailable = (e) => {
                            if (e.data.size > 0) this.chunks.push(e.data);
                        };

                        this.mediaRecorder.onstop = () => {
                            stream.getTracks().forEach(track => track.stop());
                            this.uploadAudio();
                        };

                        this.mediaRecorder.start(30000);
                        this.startedAt = Date.now();
                        this.state = 'recording';
                        this.timerInterval = setInterval(() => this.updateElapsed(), 1000);
                    } catch (err) {
                        this.errorMessage = 'No se pudo acceder al micrófono. Revisa los permisos del navegador.';
                        this.state = 'error';
                    }
                },

                stopRecording() {
                    clearInterval(this.timerInterval);
                    this.state = 'uploading';
                    this.mediaRecorder.stop();
                },

                updateElapsed() {
                    const seconds = Math.floor((Date.now() - this.startedAt) / 1000);
                    const mm = String(Math.floor(seconds / 60)).padStart(2, '0');
                    const ss = String(seconds % 60).padStart(2, '0');
                    this.elapsedLabel = `${mm}:${ss}`;
                },

                async uploadAudio() {
                    this.state = 'uploading';
                    this.uploadAttempts++;

                    const blob = new Blob(this.chunks, { type: 'audio/webm' });
                    const formData = new FormData();
                    formData.append('audio', blob, 'consultation.webm');

                    try {
                        const res = await fetch(uploadUrl, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: formData,
                        });

                        if (!res.ok) {
                            const data = await res.json().catch(() => ({}));
                            throw new Error(data.message || 'No se pudo subir el audio.');
                        }

                        const data = await res.json();
                        await AIScribeStorage.remove(this.recordingId);

                        this.state = 'transcribing';
                        this.pollStatus(data.job_token);

                    } catch (err) {
                        await this.handleUploadFailure();
                    }
                },

                async handleUploadFailure() {
                    // Los Blob no siempre son clonables de forma confiable por IndexedDB
                    // justo después de generarse desde MediaRecorder. Los convertimos a
                    // ArrayBuffer (sí 100% clonable) antes de persistir.
                    const buffers = await Promise.all(
                        this.chunks.map(chunk => chunk.arrayBuffer())
                    );

                    await AIScribeStorage.save({
                        id: this.recordingId,
                        patientId,
                        appointmentId,
                        uploadUrl,
                        notifyPendingUrl,
                        chunkBuffers: buffers, // ArrayBuffers, no Blobs
                        attempts: this.uploadAttempts,
                        status: 'pending_upload',
                        savedAt: Date.now(),
                    });

                    if (this.uploadAttempts < this.maxAutoRetries) {
                        this.state = 'uploading';
                        setTimeout(() => this.uploadAudio(), 20000);
                    } else {
                        this.state = 'upload_failed';
                        this.errorMessage = 'No se pudo subir el audio por un problema de red. Tu grabación quedó guardada en este dispositivo — haz clic en reintentar cuando tengas conexión.';
                        this.notifyPendingIfOnline();
                    }
                },

                async notifyPendingIfOnline() {
                    if (!navigator.onLine) return;
                    try {
                        await fetch(notifyPendingUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ appointment_id: appointmentId }),
                        });
                    } catch (e) {}
                },

                retryUpload() {
                    this.uploadAttempts = 0;
                    this.uploadAudio();
                },

                pollStatus(jobToken) {
                    this.pollInterval = setInterval(async () => {
                        try {
                            const res = await fetch(`${statusUrlBase}/${jobToken}/status`);
                            const data = await res.json();

                            if (data.status === 'transcribing' || data.status === 'structuring' || data.status === 'queued') {
                                this.state = data.status === 'queued' ? 'transcribing' : data.status;
                                return;
                            }

                            if (data.status === 'ready') {
                                clearInterval(this.pollInterval);
                                this.fillForm(data.data);
                                this.state = 'ready';
                                return;
                            }

                            if (data.status === 'failed' || data.status === 'not_found') {
                                clearInterval(this.pollInterval);
                                this.errorMessage = data.error || 'No se pudo generar la nota.';
                                this.state = 'error';
                            }
                        } catch (err) {
                            clearInterval(this.pollInterval);
                            this.errorMessage = 'Se perdió la conexión mientras se procesaba la nota.';
                            this.state = 'error';
                        }
                    }, 3000);
                },

                fillForm(data) {
                    const form = document.getElementById('evolution-note-form');
                    if (!form) return;

                    const setField = (name, value) => {
                        const field = form.querySelector(`[name="${name}"]`);
                        if (field && value) field.value = value;
                    };

                    setField('entry_type', data.entry_type);
                    setField('reason_for_consultation', data.reason_for_consultation);
                    setField('symptoms', data.symptoms);
                    setField('diagnosis', data.diagnosis);
                    setField('treatment_plan', data.treatment_plan);

                    if (data.medication_suggestion && data.medication_suggestion.name) {
                        const checkbox = form.querySelector('[x-model="addMedication"]');
                        setField('medication_name', data.medication_suggestion.name);
                        setField('medication_dosage', data.medication_suggestion.dosage);
                        setField('medication_frequency', data.medication_suggestion.frequency);
                        setField('medication_notes', data.medication_suggestion.notes);
                        if (checkbox) checkbox.dispatchEvent(new Event('click'));
                    }
                },

                reset() {
                    this.state = 'idle';
                    this.chunks = [];
                    this.errorMessage = '';
                    this.uploadAttempts = 0;
                },
            }));
        });

        window.addEventListener('online', async () => {
            try {
                const pending = await AIScribeStorage.getAll();
                for (const rec of pending) {
                    if (rec.status !== 'pending_upload') continue;
                    try {
                        await fetch(rec.notifyPendingUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ appointment_id: rec.appointmentId }),
                        });
                    } catch (e) {}
                }
            } catch (e) {}
        });
    
    </script>    
</x-admin-layout>