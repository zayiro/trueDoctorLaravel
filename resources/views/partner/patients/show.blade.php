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
                <!-- Envolvemos la sección con x-data para manejar el estado del formulario -->
                <div x-data="{ open: false, nuevoDato: '{{ $patient->permanent_conditions }}', enviando: false, mensaje: '' }">
                    
                    <!-- Encabezado con título a la izquierda y botón a la derecha -->
                    <div class="border-b pb-2 mb-3 flex items-center justify-between">
                        <h3 class="text-gray-900 font-bold flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Condiciones permanentes
                        </h3>

                        <!-- Botón de agregar estilizado y compacto -->
                        <button @click="open = !open" 
                                class="flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-md transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Editar</span>
                        </button>
                    </div>

                    <!-- Div desplegable con el formulario AJAX -->
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
                            .catch(() => { enviar = false; mensaje = 'Error de conexión'; })
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

                    <!-- Texto descriptivo que se actualiza en tiempo real gracias a x-text -->
                    <div class="text-sm text-gray-400 italic" 
                        x-text="nuevoDato.trim() ? nuevoDato : 'No hay condiciones permanentes registradas.'">
                        {{ $patient->permanent_conditions ?: 'No hay condiciones permanentes registradas.' }}
                    </div>
                </div>

                <!-- Alergias -->
                <div x-data="{ 
                    open: false, 
                    enviando: false, 
                    mensaje: '',
                    // Formulario reactivo
                    form: { name: '', type: 'other', severity: 'mild', reaction: '' },
                    // Listado inicial cargado desde el servidor en formato JSON
                    allergies: {{ json_encode($patient->allergies) }},
                    
                    resetForm() {
                        this.form = { name: '', type: 'other', severity: 'mild', reaction: '' };
                    }
                }" class="mt-6">

                    <!-- Encabezado con título y Botón -->
                    <div class="border-b pb-2 mb-3 flex items-center justify-between">
                        <h3 class="text-gray-900 font-bold flex items-center gap-2">
                            <!-- Icono de Alergia/Escudo/Advertencia -->
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

                    <!-- Formulario Desplegable para nueva Alergia -->
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
                                    allergies.push(data.allergy); // Se inserta dinámicamente en el listado visual
                                    resetForm();
                                    open = false;
                                } else {
                                    mensaje = 'Error: ' + data.message;
                                }
                            })
                            .catch(() => { enviando = false; mensaje = 'Error en el servidor.'; })
                        " class="space-y-3">
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <!-- Nombre -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">ALERGIA / SUSTANCIA</label>
                                    <input type="text" x-model="form.name" required placeholder="Ej. Penicilina" 
                                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:outline-none">
                                </div>

                                <!-- Tipo (Enum) -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">TIPO</label>
                                    <select x-model="form.type" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:outline-none">
                                        <option value="drug">Medicamento</option>
                                        <option value="food">Alimento</option>
                                        <option value="environment">Ambiental</option>
                                        <option value="other">Otro</option>
                                    </select>
                                </div>

                                <!-- Severidad (Enum) -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">SEVERIDAD</label>
                                    <select x-model="form.severity" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:outline-none">
                                        <option value="mild">Leve</option>
                                        <option value="moderate">Moderada</option>
                                        <option value="severe">Severa</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Reacción (Texto opcional) -->
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

                    <!-- Listado Reactivo de Alergias -->
                    <div class="space-y-2">
                        <!-- Mensaje de lista vacía en tiempo real -->
                        <template x-if="allergies.length === 0">
                            <div class="text-sm text-gray-400 italic">No hay alergias registradas.</div>
                        </template>

                        <!-- Bucle para iterar las alergias añadidas -->
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

                    <!-- Encabezado con título y Botón -->
                    <div class="border-b pb-2 mb-3 flex items-center justify-between">
                        <h3 class="text-gray-900 font-bold flex items-center gap-2">
                            <!-- Icono de Bisturí/Cirugías -->
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

                    <!-- Formulario Desplegable -->
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
                                <!-- Nombre de la cirugía -->
                                <div class="md:col-span-3">
                                    <label class="block text-xs font-bold text-gray-500 mb-1">NOMBRE DE LA CIRUGÍA</label>
                                    <input type="text" x-model="form.name" required placeholder="Ej. Colecistectopía" 
                                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                </div>

                                <!-- Año -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">AÑO</label>
                                    <input type="number" x-model="form.year" placeholder="Ej. 2021" min="1900" max="{{ date('Y') }}"
                                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                </div>
                            </div>

                            <!-- Observaciones -->
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">OBSERVACIONES / DETALLES</label>
                                <input type="text" x-model="form.observations" placeholder="Ej. Sin hallazgos adicionales, recuperación normal" 
                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            </div>

                            <!-- Complicaciones de Anestesia (Switch/Select) -->
                            <div class="bg-white p-3 border border-gray-200 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-gray-600">¿PRESENTÓ COMPLICACIONES CON LA ANESTESIA?</label>
                                    <select x-model.number="form.anesthesia_complications" 
                                            class="px-2 py-1 text-xs border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                        <option value="0">No</option>
                                        <option value="1">Sí</option>
                                    </select>
                                </div>

                                <!-- Detalles de Complicación (Aparece dinámicamente si elige 'Sí') -->
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

                    <!-- Listado Reactivo -->
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
                                
                                <!-- Alerta visual si tuvo problemas de anestesia -->
                                <div x-show="surgery.anesthesia_complications" class="mt-1 p-1.5 bg-red-50 border border-red-100 rounded text-xs text-red-700" x-cloak>
                                    <strong>Complicación anestesia:</strong> <span x-text="surgery.anesthesia_details"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Antecedentes familiares -->
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

                    <!-- Encabezado con título y Botón -->
                    <div class="border-b pb-2 mb-3 flex items-center justify-between">
                        <h3 class="text-gray-900 font-bold flex items-center gap-2">
                            <!-- Icono de Árbol Genealógico / Usuarios -->
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

                    <!-- Formulario Desplegable -->
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
                                <!-- Condición Médica -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">CONDICIÓN / ENFERMEDAD</label>
                                    <input type="text" x-model="form.condition" required placeholder="Ej. Diabetes Mellitus Tipo 2, Infarto" 
                                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:outline-none">
                                </div>

                                <!-- Parentesco / Relación -->
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

                            <!-- Notas Adicionales -->
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

                    <!-- Listado Reactivo -->
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
                                this.fillForm(data);  // ← pasa el objeto completo
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

                    const set = (name, value) => {
                        const field = form.querySelector(`[name="${name}"]`);
                        if (field && value) field.value = value;
                    };

                    // ── Metadatos (no encriptados) ───────────────────────────────
                    set('entry_type',  data.soap?.entry_type ?? 'consultation');
                    set('cie10_code',  data.soap?.cie10_code ?? '');

                    // ── Campos SOAP (se encriptarán en el modelo) ────────────────
                    set('soap_subjective', data.soap?.subjective  ?? '');
                    set('soap_objective',  data.soap?.objective   ?? '');
                    set('soap_assessment', data.soap?.assessment  ?? '');
                    set('soap_plan',       data.soap?.plan        ?? '');

                    // ── Medicamento sugerido (si la IA detectó uno) ──────────────
                    const med = data.medication_suggestion;
                    if (med?.name) {
                        set('medication_name',      med.name);
                        set('medication_dosage',    med.dosage);
                        set('medication_frequency', med.frequency);
                        set('medication_notes',     med.notes);

                        // Activar el checkbox de Alpine para mostrar los campos
                        const checkbox = form.querySelector('[x-model="addMedication"]');
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