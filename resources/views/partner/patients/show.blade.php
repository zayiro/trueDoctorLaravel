@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Historia clínica',
    ]
];

switch ($patient->gender) {
    case 'male': $gender = 'Masculino'; break;
    case 'female': $gender = 'Femenino'; break;
    default: $gender = 'No especificado';
}

// Cargar historiales con relación a cita y servicio
$histories = $patient->histories()->with(['appointment.service'])->orderBy('created_at', 'desc')->get();

// Agrupar notas por appointment_id para visualización por consulta
$notesByAppointment = $histories->groupBy('appointment_id');
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    {{-- Alerta de Éxito --}}
    @if (session('success'))
        <div id="alert-success" class="flex items-center p-4 mb-4 text-green-800 rounded-2xl bg-green-50 border border-green-100 shadow-sm" role="alert">
            <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L8 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
            </svg>
            <div class="ms-3 text-sm font-medium">
                {{ session('success') }}
            </div>
            <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8" onclick="document.getElementById('alert-success').remove()">
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                </svg>
            </button>
        </div>
    @endif

    {{-- Alerta de Error --}}
    @if (session('error'))
        <div class="flex items-center p-4 mb-4 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm" role="alert">
            <div class="text-sm font-medium">{{ session('error') }}</div>
        </div>
    @endif

    <div>
        {{-- Botón Volver --}}
        <a href="{{ route('partner.appointments.index') }}" class="text-sm text-gray-500 hover:text-blue-600 flex items-center gap-2 mb-6">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
            Volver a la agenda
        </a>

        {{-- Header del Paciente --}}
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

        {{-- Cuerpo del Perfil --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            {{-- Información Básica --}}
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
                {{-- Condiciones permanentes --}}
                <div x-data="{ open: false, nuevoDato: '{{ $patient->permanent_conditions }}', enviando: false, mensaje: '' }">
                    <div class="border-b pb-2 mb-3 flex items-center justify-between">
                        <h3 class="text-gray-900 font-bold flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Condiciones permanentes
                        </h3>
                        <button @click="open = !open" 
                                class="flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-md transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Editar</span>
                        </button>
                    </div>
                    <div x-show="open" 
                        x-transition
                        x-cloak 
                        class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                        <form @submit.prevent="
                            enviando = true;
                            fetch('{{ route('partner.patients.update-condition', $patient) }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({ permanent_conditions: nuevoDato })
                            })
                            .then(res => res.json())
                            .then(data => {
                                enviando = false;
                                if(data.success) {
                                    mensaje = 'Guardado con éxito';
                                    setTimeout(() => { open = false; mensaje = ''; }, 1200);
                                } else {
                                    mensaje = 'Error al guardar';
                                }
                            })
                            .catch(() => { enviando = false; mensaje = 'Error de conexión'; })
                        ">
                            <div class="flex gap-2">
                                <input type="text" 
                                    x-model="nuevoDato" 
                                    placeholder="Ej. Hipertensión, Diabetes..." 
                                    class="flex-1 px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <button type="submit" 
                                        :disabled="enviando"
                                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-md transition disabled:opacity-50">
                                    <span x-text="enviando ? '...' : 'Guardar'"></span>
                                </button>
                            </div>
                            <p x-show="mensaje" x-text="mensaje" class="text-xs mt-1.5 font-medium text-emerald-600" x-cloak></p>
                        </form>
                    </div>
                    <div class="text-sm text-gray-400 italic" 
                        x-text="nuevoDato.trim() ? nuevoDato : 'No hay condiciones permanentes registradas.'">
                        {{ $patient->permanent_conditions ?: 'No hay condiciones permanentes registradas.' }}
                    </div>
                </div>

                {{-- Alergias --}}
                <div x-data="{ 
                    open: false, 
                    enviando: false, 
                    mensaje: '',
                    form: { name: '', type: 'other', severity: 'mild', reaction: '' },
                    allergies: {{ json_encode($patient->allergies) }},
                    resetForm() {
                        this.form = { name: '', type: 'other', severity: 'mild', reaction: '' };
                    }
                }" class="mt-6">
                    <div class="border-b pb-2 mb-3 flex items-center justify-between">
                        <h3 class="text-gray-900 font-bold flex items-center gap-2">
                            <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            Alergias registradas
                        </h3>
                        <button @click="open = !open; if(!open) resetForm()" 
                                class="flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-md transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span x-text="open ? 'Cancelar' : 'Agregar'"></span>
                        </button>
                    </div>
                    <div x-show="open" x-transition x-cloak class="mb-4 p-4 bg-gray-50 border border-gray-200 rounded-xl shadow-inner">
                        <form @submit.prevent="
                            enviando = true;
                            mensaje = '';
                            fetch('{{ route('partner.patients.store-allergy', $patient->id) }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                body: JSON.stringify(form)
                            })
                            .then(res => res.json())
                            .then(data => {
                                enviando = false;
                                if(data.success) {
                                    allergies.push(data.allergy);
                                    resetForm();
                                    open = false;
                                } else {
                                    mensaje = 'Error: ' + data.message;
                                }
                            })
                            .catch(() => { enviando = false; mensaje = 'Error en el servidor.'; })
                        " class="space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">ALERGIA / SUSTANCIA</label>
                                    <input type="text" x-model="form.name" required placeholder="Ej. Penicilina" 
                                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">TIPO</label>
                                    <select x-model="form.type" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:outline-none">
                                        <option value="drug">Medicamento</option>
                                        <option value="food">Alimento</option>
                                        <option value="environment">Ambiental</option>
                                        <option value="other">Otro</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">SEVERIDAD</label>
                                    <select x-model="form.severity" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:outline-none">
                                        <option value="mild">Leve</option>
                                        <option value="moderate">Moderada</option>
                                        <option value="severe">Severa</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">REACCIÓN (OPCIONAL)</label>
                                <input type="text" x-model="form.reaction" placeholder="Ej. Erupción cutánea, Anafilaxia" 
                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:outline-none">
                            </div>
                            <div class="flex items-center justify-between pt-1">
                                <p x-show="mensaje" x-text="mensaje" class="text-xs text-red-600 font-medium" x-cloak></p>
                                <button type="submit" :disabled="enviando"
                                        class="ml-auto px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-md transition disabled:opacity-50">
                                    <span x-text="enviando ? 'Guardando...' : 'Guardar Alergia'"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="space-y-2">
                        <template x-if="allergies.length === 0">
                            <div class="text-sm text-gray-400 italic">No hay alergias registradas.</div>
                        </template>
                        <template x-for="allergy in allergies" :key="allergy.id">
                            <div class="flex items-center justify-between p-2.5 bg-white border border-gray-100 rounded-lg shadow-sm text-sm">
                                <div>
                                    <span class="font-semibold text-gray-800" x-text="allergy.name"></span>
                                    <span class="text-xs px-2 py-0.5 rounded-full ml-2 font-medium"
                                        :class="{
                                            'bg-green-100 text-green-700': allergy.severity === 'mild',
                                            'bg-amber-100 text-amber-700': allergy.severity === 'moderate',
                                            'bg-red-100 text-red-700': allergy.severity === 'severe'
                                        }"
                                        x-text="allergy.severity === 'mild' ? 'Leve' : (allergy.severity === 'moderate' ? 'Mod' : 'Grave')">
                                    </span>
                                    <p x-show="allergy.reaction" x-text="allergy.reaction" class="text-xs text-gray-500 mt-0.5"></p>
                                </div>
                                <span class="text-xs text-gray-400 capitalize" x-text="allergy.type"></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Medicamentos --}}
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

                {{-- Antecedentes Quirúrgicos --}}
                <div x-data="{ 
                    open: false, 
                    enviando: false, 
                    mensaje: '',
                    form: { name: '', year: '', observations: '', anesthesia_complications: 0, anesthesia_details: '' },
                    surgeries: {{ json_encode($patient->surgeries) }},
                    resetForm() {
                        this.form = { name: '', year: '', observations: '', anesthesia_complications: 0, anesthesia_details: '' };
                    }
                }" class="mt-6">
                    <div class="border-b pb-2 mb-3 flex items-center justify-between">
                        <h3 class="text-gray-900 font-bold flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.4 14.4-4.2 4.2H6v-4.2l4.2-4.2m4.2 4.2 6.1-6.1a2.1 2.1 0 0 0-3-3l-6.1 6.1m3 3-3-3"/>
                            </svg>
                            Antecedentes Quirúrgicos
                        </h3>
                        <button @click="open = !open; if(!open) resetForm()" 
                                class="flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-emerald-600 bg-emerald-50 hover:bg-emerald-100 rounded-md transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span x-text="open ? 'Cancelar' : 'Agregar'"></span>
                        </button>
                    </div>
                    <div x-show="open" x-transition x-cloak class="mb-4 p-4 bg-gray-50 border border-gray-200 rounded-xl shadow-inner">
                        <form @submit.prevent="
                            enviando = true;
                            mensaje = '';
                            fetch('{{ route('partner.patients.store-surgery', $patient->id) }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                body: JSON.stringify(form)
                            })
                            .then(res => res.json())
                            .then(data => {
                                enviando = false;
                                if(data.success) {
                                    surgeries.push(data.surgery);
                                    resetForm();
                                    open = false;
                                } else {
                                    mensaje = 'Error: ' + data.message;
                                }
                            })
                            .catch(() => { enviando = false; mensaje = 'Error en el servidor.'; })
                        " class="space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                <div class="md:col-span-3">
                                    <label class="block text-xs font-bold text-gray-500 mb-1">NOMBRE DE LA CIRUGÍA</label>
                                    <input type="text" x-model="form.name" required placeholder="Ej. Colecistectopía" 
                                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">AÑO</label>
                                    <input type="number" x-model="form.year" placeholder="Ej. 2021" min="1900" max="{{ date('Y') }}"
                                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">OBSERVACIONES / DETALLES</label>
                                <input type="text" x-model="form.observations" placeholder="Ej. Sin hallazgos adicionales, recuperación normal" 
                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            </div>
                            <div class="bg-white p-3 border border-gray-200 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-gray-600">¿PRESENTÓ COMPLICACIONES CON LA ANESTESIA?</label>
                                    <select x-model.number="form.anesthesia_complications" 
                                            class="px-2 py-1 text-xs border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                        <option value="0">No</option>
                                        <option value="1">Sí</option>
                                    </select>
                                </div>
                                <div x-show="form.anesthesia_complications === 1" x-transition class="mt-2.5" x-cloak>
                                    <label class="block text-xs font-bold text-red-500 mb-1">DETALLES DE LA COMPLICACIÓN</label>
                                    <textarea x-model="form.anesthesia_details" :required="form.anesthesia_complications === 1" rows="2" placeholder="Describa qué ocurrió..."
                                            class="w-full px-3 py-1.5 text-sm border border-red-300 rounded-md focus:ring-2 focus:ring-red-500 focus:outline-none"></textarea>
                                </div>
                            </div>
                            <div class="flex items-center justify-between pt-1">
                                <p x-show="mensaje" x-text="mensaje" class="text-xs text-red-600 font-medium" x-cloak></p>
                                <button type="submit" :disabled="enviando"
                                        class="ml-auto px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-md transition disabled:opacity-50">
                                    <span x-text="enviando ? 'Guardando...' : 'Guardar Cirugía'"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="space-y-2">
                        <template x-if="surgeries.length === 0">
                            <div class="text-sm text-gray-400 italic">No hay cirugías registradas.</div>
                        </template>
                        <template x-for="surgery in surgeries" :key="surgery.id">
                            <div class="p-3 bg-white border border-gray-100 rounded-lg shadow-sm text-sm space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-gray-800" x-text="surgery.name"></span>
                                    <span class="text-xs text-gray-400 font-medium" x-text="surgery.year ? 'Año ' + surgery.year : 'Año: N/R'"></span>
                                </div>
                                <p x-show="surgery.observations" x-text="surgery.observations" class="text-xs text-gray-500"></p>
                                <div x-show="surgery.anesthesia_complications" class="mt-1 p-1.5 bg-red-50 border border-red-100 rounded text-xs text-red-700" x-cloak>
                                    <strong>Complicación anestesia:</strong> <span x-text="surgery.anesthesia_details"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Antecedentes familiares --}}
                <div x-data="{ 
                    open: false, 
                    enviando: false, 
                    mensaje: '',
                    form: { condition: '', relationship: '', notes: '' },
                    familyHistories: {{ json_encode($patient->familyHistories) }},
                    resetForm() {
                        this.form = { condition: '', relationship: '', notes: '' };
                    }
                }" class="mt-6">
                    <div class="border-b pb-2 mb-3 flex items-center justify-between">
                        <h3 class="text-gray-900 font-bold flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                            </svg>
                            Antecedentes Familiares
                        </h3>
                        <button @click="open = !open; if(!open) resetForm()" 
                                class="flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-purple-600 bg-purple-50 hover:bg-purple-100 rounded-md transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span x-text="open ? 'Cancelar' : 'Agregar'"></span>
                        </button>
                    </div>
                    <div x-show="open" x-transition x-cloak class="mb-4 p-4 bg-gray-50 border border-gray-200 rounded-xl shadow-inner">
                        <form @submit.prevent="
                            enviando = true;
                            mensaje = '';
                            fetch('{{ route('partner.patients.store-family-history', $patient->id) }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                body: JSON.stringify(form)
                            })
                            .then(res => res.json())
                            .then(data => {
                                enviando = false;
                                if(data.success) {
                                    familyHistories.push(data.history);
                                    resetForm();
                                    open = false;
                                } else {
                                    mensaje = 'Error: ' + data.message;
                                }
                            })
                            .catch(() => { enviando = false; mensaje = 'Error en el servidor.'; })
                        " class="space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">CONDICIÓN / ENFERMEDAD</label>
                                    <input type="text" x-model="form.condition" required placeholder="Ej. Diabetes Mellitus Tipo 2, Infarto" 
                                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">PARENTESCO</label>
                                    <select x-model="form.relationship" required
                                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:outline-none">
                                        <option value="" disabled selected>Seleccione familiar...</option>
                                        <option value="Madre">Madre</option>
                                        <option value="Padre">Padre</option>
                                        <option value="Abuela Materna">Abuela Materna</option>
                                        <option value="Abuelo Materno">Abuelo Materno</option>
                                        <option value="Abuela Paterna">Abuela Paterna</option>
                                        <option value="Abuelo Paterno">Abuelo Paterno</option>
                                        <option value="Hermano/a">Hermano/a</option>
                                        <option value="Tío/a">Tío/a</option>
                                        <option value="Otro">Otro</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">NOTAS / OBSERVACIONES (OPCIONAL)</label>
                                <input type="text" x-model="form.notes" placeholder="Ej. Diagnosticado a los 50 años, controlado con tratamiento" 
                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:outline-none">
                            </div>
                            <div class="flex items-center justify-between pt-1">
                                <p x-show="mensaje" x-text="mensaje" class="text-xs text-red-600 font-medium" x-cloak></p>
                                <button type="submit" :disabled="enviando"
                                        class="ml-auto px-4 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold rounded-md transition disabled:opacity-50">
                                    <span x-text="enviando ? 'Guardando...' : 'Guardar Antecedente'"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="space-y-2">
                        <template x-if="familyHistories.length === 0">
                            <div class="text-sm text-gray-400 italic">No hay antecedentes familiares registrados.</div>
                        </template>
                        <template x-for="history in familyHistories" :key="history.id">
                            <div class="p-3 bg-white border border-gray-100 rounded-lg shadow-sm text-sm space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-gray-800" x-text="history.condition"></span>
                                    <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-medium" x-text="history.relationship"></span>
                                </div>
                                <p x-show="history.notes" x-text="history.notes" class="text-xs text-gray-500 mt-1"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Historial Clínico Agrupado por Consulta --}}
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 mt-8">
            <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Historial Clínico por Consulta</h3>

            <div class="space-y-6">
                @forelse($notesByAppointment as $appointmentId => $notes)
                    {{-- Encabezado de la Cita --}}
                    @if ($appointmentId)
                        @php $appointment = $notes->first()->appointment; @endphp
                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-bold text-blue-800 text-sm">
                                        📅 Cita: {{ $appointment->service->name ?? 'Servicio no disponible' }}
                                    </p>
                                    <p class="text-xs text-blue-600">
                                        {{ \Carbon\Carbon::parse($appointment->date)->translatedFormat('d \d\e F, Y') }} a las {{ $appointment->start_time }}
                                    </p>
                                </div>
                                <span class="text-xs px-2 py-1 rounded-full bg-blue-200 text-blue-800 font-bold">
                                    {{ ucfirst($appointment->status->value) }}
                                </span>
                            </div>
                        </div>
                    @else
                        {{-- Notas Generales (Sin cita vinculada) --}}
                        <div class="bg-gray-50 border-l-4 border-gray-400 p-4 rounded-r-lg">
                            <p class="font-bold text-gray-700 text-sm">📝 Nota General / Seguimiento</p>
                        </div>
                    @endif

                    {{-- Lista de Notas de esa Cita --}}
                    <div class="space-y-3 pl-2">
                        @foreach($notes as $note)
                            <div class="p-4 border border-gray-200 rounded-xl bg-white shadow-sm">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-xs font-bold text-gray-500 uppercase">
                                        {{ $note->entry_type === 'consultation' ? 'Consulta' : ucfirst($note->entry_type) }}
                                    </span>
                                    <span class="text-xs text-gray-400">
                                        {{ $note->created_at->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <p class="font-semibold text-gray-700 text-xs mb-1">Motivo:</p>
                                        <p class="text-gray-600">{{ $note->reason_for_consultation }}</p>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-700 text-xs mb-1">Diagnóstico:</p>
                                        <p class="text-gray-600">{{ $note->diagnosis }}</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <p class="font-semibold text-gray-700 text-xs mb-1">Síntomas / Subjetivo-Objetivo:</p>
                                        <p class="text-gray-600">{{ $note->symptoms }}</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <p class="font-semibold text-gray-700 text-xs mb-1">Plan / Tratamiento:</p>
                                        <p class="text-gray-600">{{ $note->treatment_plan }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <p class="text-sm text-gray-400 italic text-center py-4">No hay notas clínicas registradas.</p>
                @endforelse
            </div>
        </div>

        {{-- Últimas consultas --}}
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 mt-8">
            <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Últimas 3 consultas</h3>
            <div class="space-y-2">
                @forelse($patient->appointments->take(3) as $appointment)
                @php
                $status = match($appointment->status_label ?? $appointment->status->value) {
                    'confirmed' => 'Confirmada',
                    'pending' => 'Pendiente',
                    'cancelled' => 'Cancelada',
                    'completed' => 'Completada',
                    default => ucfirst($appointment->status->value),
                };
                @endphp
                <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-xl transition">
                    <div>
                        <p class="font-bold text-indigo-700 text-sm">{{ $appointment->reference }}</p>
                        <p class="font-bold text-gray-800 text-sm">{{ $appointment->service->name }}</p>
                        @if($appointment->notes)
                        <p class="text-sm text-gray-400">{{ $appointment->notes }}</p>
                        @endif
                        <p class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($appointment->date)->translatedFormat('d \d\e F, Y') }} a las {{ $appointment->start_time }}</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $appointment->status === 'confirmed' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $appointment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $appointment->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                        {{ $appointment->status === 'completed' ? 'bg-blue-100 text-blue-800' : '' }}">
                        {{ $status }}
                    </span>
                </div>
                @empty
                <p class="text-sm text-gray-400 italic">No hay registros previos.</p>
                @endforelse
            </div>
        </div>

        {{-- Nota de Evolución Actual (SOAP con Alpine/AJAX) --}}
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 mt-8"
             x-data="{ enviando: false, mensaje: '', tipo: '' }">
            
            <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Nota de evolución actual</h3>

            {{-- Alertas dinámicas de éxito/error --}}
            <div x-show="mensaje" x-cloak x-transition
                 :class="tipo === 'success' ? 'bg-green-50 text-green-800 border-green-200' : 'bg-red-50 text-red-800 border-red-200'"
                 class="p-4 mb-4 rounded-xl border flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2">
                    <svg x-show="tipo === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <svg x-show="tipo === 'error'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span class="text-sm font-medium" x-text="mensaje"></span>
                </div>
                <button @click="mensaje = ''" class="text-current opacity-60 hover:opacity-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="evolution-note-form"
                  @submit.prevent="
                    enviando = true;
                    mensaje = '';
                    const formData = new FormData($el);
                    
                    fetch('{{ route('partner.patients.store-history', $patient->id) }}', {
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
                            setTimeout(() => { mensaje = ''; }, 5000);
                        }
                    })
                    .catch(err => {
                        enviando = false;
                        tipo = 'error';
                        mensaje = 'Error de conexión o validación. Revisa los campos.';
                        console.error('Error al guardar nota:', err);
                    })
                  " 
                  class="space-y-4">
                @csrf
                @include('partner.patients.partials.history-form')

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            :disabled="enviando"
                            class="bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 disabled:cursor-not-allowed text-white font-bold py-2.5 px-6 rounded-xl transition flex items-center gap-2 shadow-md">
                        <svg x-show="enviando" x-cloak class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="enviando ? 'Guardando...' : 'Guardar nota'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>