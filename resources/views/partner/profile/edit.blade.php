@php
$breadcrumbs = [
    [
        'name' => 'Doctor',
        'href' => route('partner.addresses.index'),
    ],
    [
        'name' => 'Perfil',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">  
    <div class="mt-10 sm:mt-0 max-w-5xl mx-auto py-6">
        
        {{-- Mensajes de Éxito --}}
        @if (session('success'))
            <div id="alert-success" class="flex items-center p-4 mb-6 text-green-800 rounded-2xl bg-green-50 border border-green-100 shadow-sm transition-opacity duration-500" role="alert">
                <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://w3.org" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L8 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                </svg>
                <div class="ms-3 text-sm font-medium">{{ session('success') }}</div>
                <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8" onclick="document.getElementById('alert-success').remove()">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://w3.org" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
        @endif

        {{-- Errores de Validación globales --}}
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 p-4 mb-6 shadow-sm rounded-2xl flex items-start gap-3">
                <span class="mt-0.5">❌</span>
                <div>
                    <p class="font-bold text-sm">Por favor, corrige los errores del formulario:</p>
                    <ul class="list-disc list-inside text-xs mt-1 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
        
        <!-- SECCIÓN 1: PLAN DE SUSCRIPCIÓN (Tu código original) -->
        <div class="md:grid md:grid-cols-3 md:gap-6">
            <div class="md:col-span-1">
                <div class="px-4 sm:px-0">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Plan de Suscripción</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Gestiona los límites de sedes y servicios para tu cuenta profesional.
                    </p>
                </div>
            </div>

            <div class="mt-5 md:mt-0 md:col-span-2">
                <div class="shadow overflow-hidden sm:rounded-2xl bg-white p-6 border border-gray-100">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($plans as $plan)
                            <form action="{{ route('plans.subscribe', $plan) }}" method="POST">
                                @csrf
                                <input type="hidden" name="plan" value="{{ $plan->plan }}">
                                
                                <button type="submit" 
                                    class="w-full text-left p-4 rounded-xl border-2 transition duration-200 
                                    {{ $doctor->settings->plan_id == $plan->id ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-100' : 'border-gray-200 hover:border-blue-300' }}">
                                    
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="font-black text-gray-800">{{ $plan->name }}</span>
                                        @if($doctor->settings->plan_id == $plan->id)
                                            <span class="bg-blue-600 text-white text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-tighter">Activo</span>
                                        @endif
                                    </div>

                                    <div class="space-y-1">
                                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">
                                            {{ $plan->max_addresses }} {{ Str::plural('Sede', $plan->max_addresses) }}
                                        </p>
                                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">
                                            {{ $plan->max_services_per_address }} Servicios x Sede
                                        </p>
                                        <div class="mt-3 pt-2 border-t border-gray-100">
                                            <span class="text-sm font-black text-blue-600">
                                                {{ $plan->price > 0 ? '$' . number_format($plan->price, 0) : 'Gratis' }}
                                            </span>
                                        </div>
                                    </div>
                                </button>
                            </form>
                        @endforeach
                    </div>

                    <div class="mt-6 p-4 bg-amber-50 rounded-2xl border border-amber-100 flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xs text-amber-800 leading-relaxed">
                            <strong>Nota:</strong> Al cambiar a un plan con menores beneficios, el sistema mantendrá activas solo las sedes y servicios más antiguos hasta alcanzar el límite permitido.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- DIVIDER VISUAL -->
        <div class="hidden sm:block" aria-hidden="true">
            <div class="py-5"><div class="border-t border-gray-200"></div></div>
        </div>

        <!-- 👇 NUEVA SECCIÓN 2: FORMULARIO DE PERFIL PROFESIONAL -->
        <div class="mt-10 sm:mt-0 md:grid md:grid-cols-3 md:gap-6">
            <div class="md:col-span-1">
                <div class="px-4 sm:px-0">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Información Profesional</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Completa tu información clínica, trayectoria e idiomas para mostrar a tus pacientes.
                    </p>
                </div>
            </div>

            <div class="mt-5 md:mt-0 md:col-span-2">
                <form action="{{ route('partner.profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="shadow overflow-hidden sm:rounded-2xl bg-white border border-gray-100">
                        <div class="px-4 py-5 bg-white sm:p-6 space-y-6">
                            
                            <!-- Grid de Datos Base -->
                            <div class="grid grid-cols-6 gap-6">
                                <div class="col-span-6 sm:col-span-3">
                                    <label for="identification" class="block text-sm font-medium text-gray-700">Documento de Identidad</label>
                                    <input type="text" name="identification" id="identification" value="{{ old('identification', $doctor->identification) }}" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>

                                <div class="col-span-6 sm:col-span-3">
                                    <label for="medical_license" class="block text-sm font-medium text-gray-700">Licencia Médica / Registro</label>
                                    <input type="text" name="medical_license" id="medical_license" value="{{ old('medical_license', $doctor->medical_license) }}" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>

                                <div class="col-span-6 sm:col-span-3">
                                    <label for="phone" class="block text-sm font-medium text-gray-700">Teléfono corporativo, donde recibiras información de reservas y de contacto con el paciente</label>
                                    <input type="text" name="phone" id="phone" value="{{ old('phone', $doctor->phone) }}" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>

                                <div class="col-span-6 sm:col-span-3">
                                    <label for="experience_years" class="block text-sm font-medium text-gray-700">Años de Experiencia</label>
                                    <input type="number" name="experience_years" id="experience_years" value="{{ old('experience_years', $doctor->experience_years) }}" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>

                                <div class="col-span-6">
                                    <label for="gender" class="block text-sm font-medium text-gray-700">Género</label>
                                    <select id="gender" name="gender" class="mt-1 block w-full rounded-md border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm">
                                        <option value="male" {{ old('gender', $doctor->gender) == 'male' ? 'selected' : '' }}>Masculino</option>
                                        <option value="female" {{ old('gender', $doctor->gender) == 'female' ? 'selected' : '' }}>Femenino</option>
                                        <option value="other" {{ old('gender', $doctor->gender) == 'other' ? 'selected' : '' }}>Otro / Prefiero no decirlo</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Módulo de Idiomas (Matriz de Checkboxes) -->
                            <div class="pt-4 border-t border-gray-100">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Idiomas de Atención</label>
                                <span class="text-xs text-gray-400 block mb-3">Marca los idiomas en los que realizas valoraciones médicas.</span>
                                
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    @php
                                    $availableLanguages = [
                                        'es' => 'Español', 'en' => 'Inglés', 'pt' => 'Portugués', 'fr' => 'Francés', 'de' => 'Alemán'
                                    ];

                                    // 👇 SEGURO ANTI-TEXTO: Si llega como string de texto puro, lo decodificamos a mano
                                    $rawLanguages = $doctor->languages;
                                    if (is_string($rawLanguages)) {
                                        $currentLanguages = json_decode($rawLanguages, true) ?? [];
                                    } else {
                                        $currentLanguages = is_array($rawLanguages) ? $rawLanguages : [];
                                    }
                                    @endphp

                                    @foreach($availableLanguages as $code => $name)
                                        <label class="relative flex items-start p-3 rounded-xl border border-gray-200 hover:bg-gray-50 cursor-pointer transition-colors">
                                            <div class="flex items-center h-5">
                                                <input type="checkbox" name="languages[]" value="{{ $code }}"
                                                    {{ in_array($code, $currentLanguages) ? 'checked' : '' }}
                                                    class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                            </div>
                                            <div class="ml-3 text-xs font-bold text-gray-700">{{ $name }}</div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Módulo de Especialidades Médicas (Matriz de Checkboxes) -->
                            <div class="pt-4 border-t border-gray-100">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Especialidades Médicas</label>
                                <span class="text-xs text-gray-400 block mb-3">Selecciona una o más especialidades correspondientes a tu acreditación oficial.</span>
                                
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    @php
                                    // Obtenemos los IDs de las especialidades que el doctor ya tiene vinculadas actualmente
                                    $currentSpecialties = $doctor->specialties->pluck('id')->toArray();
                                    @endphp

                                    @forelse($allSpecialties as $specialty)
                                        <label class="relative flex items-start p-3 rounded-xl border border-gray-200 hover:bg-gray-50 cursor-pointer transition-colors">
                                            <div class="flex items-center h-5">
                                                <!-- Enviamos un arreglo 'specialties[]' con el ID correspondiente -->
                                                <input type="checkbox" name="specialties[]" value="{{ $specialty->id }}"
                                                    {{ in_array($specialty->id, $currentSpecialties) ? 'checked' : '' }}
                                                    class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                            </div>
                                            <div class="ml-3 text-xs font-bold text-gray-700">{{ $specialty->name }}</div>
                                        </label>
                                    @empty
                                        <div class="col-span-2 sm:col-span-3 p-4 text-center bg-gray-50 rounded-xl text-xs text-gray-500 border border-dashed border-gray-200">
                                            No hay especialidades registradas en el sistema por el administrador.
                                        </div>
                                    @endforelse
                                </div>
                                @error('specialties') <span class="text-xs text-red-500 mt-2 block">{{ $message }}</span> @enderror
                            </div>


                            <!-- Cuadro de Biografía Clínica -->
                            <div class="pt-4 border-t border-gray-100">
                                <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">Perfil / Biografía Médica</label>
                                <textarea id="bio" name="bio" rows="4" 
                                    placeholder="Escribe sobre tus enfoques clínicos, estudios o un saludo de confianza para los pacientes..."
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm placeholder-gray-400">{{ old('bio', $doctor->bio) }}</textarea>
                            </div>

                        </div>

                        <!-- Botón de Envío del Formulario -->
                        <div class="px-4 py-3 bg-gray-50 text-right sm:px-6 border-t border-gray-50">
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                Guardar Perfil Profesional
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-admin-layout>
