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

    $doctorSettings = $doctor->settings;
    $hasIndividualPlan = $doctorSettings && $doctorSettings->plan_id !== null;
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

            @if ($hasIndividualPlan)
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
                                        @if($doctor->settings->plan_id === $plan->id)
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
                        @else
            <div class="mt-5 md:mt-0 md:col-span-2">
                <div class="shadow overflow-hidden sm:rounded-2xl bg-white p-8 border border-gray-100 text-center max-w-xl mx-auto">
                    <!-- Icono Corporativo Premium -->
                    <div class="mx-auto w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mb-4 ring-4 ring-blue-100/50">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h18"/>
                        </svg>
                    </div>

                    <!-- Mensaje Principal -->
                    <h3 class="text-lg font-black text-gray-900 mb-1">Perfil Médico Institucional</h3>
                    <p class="text-sm text-gray-500 max-w-md mx-auto mb-6">
                        Tu cuenta está vinculada a una institución de salud. Actualmente tus beneficios de cuotas, sedes y citas están cubiertos de forma corporativa.
                    </p>

                    <!-- Beneficios Heredados del Plan de la Clínica -->
                    <div class="grid grid-cols-2 gap-3 text-left mb-6">
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 flex items-center gap-2.5">
                            <span class="text-blue-600">✓</span>
                            <span class="text-xs font-bold text-gray-700">Sedes de la Clínica</span>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 flex items-center gap-2.5">
                            <span class="text-blue-600">✓</span>
                            <span class="text-xs font-bold text-gray-700">Servicios Institucionales</span>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 flex items-center gap-2.5">
                            <span class="text-blue-600">✓</span>
                            <span class="text-xs font-bold text-gray-700">Agenda Protegida SaaS</span>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 flex items-center gap-2.5">
                            <span class="text-blue-600">✓</span>
                            <span class="text-xs font-bold text-gray-700">Historial Clínico Seguro</span>
                        </div>
                    </div>

                    <!-- Información Adicional y Acción de Soporte -->
                    <div class="pt-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-left">
                        <div>
                            <h4 class="text-xs font-black text-gray-800">¿Deseas trabajar de forma particular?</h4>
                            <p class="text-[11px] text-gray-500 max-w-xs mt-0.5">Contacta con el soporte de OpenDoctor para evaluar tu migración a un plan independiente.</p>
                        </div>
                        <a href="{{ route('contact.show') }}" 
                           class="w-full sm:w-auto text-center px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white font-bold text-xs rounded-xl transition duration-150 shadow-sm">
                            Contactar Soporte
                        </a>
                    </div>
                </div>
            </div>
            @endif
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
                            
                            <!-- Grid de Datos Base (UX e Identidad Unificada OpenDoctor) -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                
                                <!-- Correo Electrónico del Médico (Con Control Dinámico Readonly) -->
                                <div class="flex flex-col md:col-span-2 mb-2">
                                    <x-label for="email" value="Correo Electrónico Profesional" class="mb-1 text-slate-500 font-bold text-xs" />
                                    
                                    <input type="email" name="email" id="email" 
                                        value="{{ old('email', $user->email ?? '') }}" required
                                        placeholder="doctor@opendoctor.online"
                                        
                                        {{-- 🔒 CAPA 1 HTML: Bloquea la edición si el médico ya está aprobado --}}
                                        {{ $doctor->validation_status === 'approved' ? 'readonly' : '' }}
                                        
                                        {{-- 🎨 CAPA 2 TAILWIND: Estilos premium condicionales para estados de lectura --}}
                                        class="w-full rounded-2xl py-4 px-5 text-sm shadow-inner transition-colors
                                        {{ $doctor->validation_status === 'approved' 
                                            ? 'bg-slate-100 text-slate-400 cursor-not-allowed border-slate-200 focus:ring-0 focus:border-slate-200' 
                                            : 'border-slate-200 text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500' 
                                        }}">
                                        
                                    {{-- Micro-nota explicativa de seguridad institucional --}}
                                    @if($doctor->validation_status === 'approved')
                                        <span class="text-[10px] text-emerald-600 font-semibold mt-1 flex items-center gap-1">
                                            <!-- SVG Nativo: Lock-Closed de Heroicons -->
                                            <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"></path>
                                            </svg>
                                            Canal de acceso verificado. Cambios de correo requieren validación de soporte.
                                        </span>
                                    @endif
                                </div>

                                <!-- Documento de Identidad (Con Control Dinámico Readonly) -->
                                <div class="flex flex-col mb-2">
                                    <x-label for="identification" value="Documento de Identidad" class="mb-1 text-slate-500 font-bold text-xs" />
                                    
                                    <input type="text" name="identification" id="identification" 
                                        value="{{ old('identification', $doctor->identification ?? '') }}" required
                                        placeholder="Número de cédula o ID"
                                        
                                        {{-- 🔒 CAPA 1 HTML: Bloquea la escritura si el médico ya está aprobado --}}
                                        {{ $doctor->validation_status === 'approved' ? 'readonly' : '' }}
                                        
                                        {{-- 🎨 CAPA 2 TAILWIND: Cambia los estilos visuales en cascada según el estado del Perfil --}}
                                        class="w-full rounded-2xl py-4 px-5 text-sm shadow-inner transition-colors
                                        {{ $doctor->validation_status === 'approved' 
                                            ? 'bg-slate-100 text-slate-400 cursor-not-allowed border-slate-200 focus:ring-0 focus:border-slate-200' 
                                            : 'border-slate-200 text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500' 
                                        }}">
                                        
                                    {{-- Nota de auditoría sutil si el campo es de solo lectura --}}
                                    @if($doctor->validation_status === 'approved')
                                        <span class="text-[10px] text-emerald-600 font-semibold mt-1 flex items-center gap-1">
                                            <!-- SVG Nativo: Lock-Closed de Heroicons -->
                                            <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"></path>
                                            </svg>
                                            Identificación verificada. Cambios requieren revisión de soporte.
                                        </span>
                                    @endif
                                </div>

                                <!-- Licencia Médica (Con Control Dinámico Readonly) -->
                                <div class="flex flex-col mb-2">
                                    <x-label for="medical_license" value="Licencia Médica / Registro Oficial" class="mb-1 text-slate-500 font-bold text-xs" />
                                    
                                    <input type="text" name="medical_license" id="medical_license" 
                                        value="{{ old('medical_license', $doctor->medical_license ?? '') }}" required
                                        placeholder="Ej: Registro ReTHUS"
                                        
                                        {{-- 🔒 CAPA 1 HTML: Bloquea la edición si el médico ya está aprobado --}}
                                        {{ $doctor->validation_status === 'approved' ? 'readonly' : '' }}
                                        
                                        {{-- 🎨 CAPA 2 TAILWIND: Estilos visuales condicionales según el estado de verificación --}}
                                        class="w-full rounded-2xl py-4 px-5 text-sm shadow-inner transition-colors
                                        {{ $doctor->validation_status === 'approved' 
                                            ? 'bg-slate-100 text-slate-400 cursor-not-allowed border-slate-200 focus:ring-0 focus:border-slate-200' 
                                            : 'border-slate-200 text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500' 
                                        }}">
                                        
                                    {{-- Micro-nota explicativa de seguridad --}}
                                    @if($doctor->validation_status === 'approved')
                                        <span class="text-[10px] text-emerald-600 font-semibold mt-1 flex items-center gap-1">
                                            <!-- SVG Nativo: Lock-Closed de Heroicons -->
                                            <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"></path>
                                            </svg>
                                            Registro habilitado. Cambios regulatorios requieren validación de soporte.
                                        </span>
                                    @endif
                                </div>

                                <!-- Número Celular Expandido (Corregido sin ciclos indexados) -->
                                <div class="flex flex-col mb-2">
                                    <x-label for="phone" value="Número celular de notificación" class="mb-1 text-slate-500 font-bold text-xs" />
                                    <div class="flex rounded-2xl border border-slate-200 bg-white focus-within:ring-2 focus-within:ring-indigo-500 overflow-hidden shadow-inner">
                                        <select name="country_code" required class="bg-slate-50 text-slate-600 text-xs border-0 border-r border-slate-200 focus:ring-0 px-7 cursor-pointer outline-none">
                                            <option value="+57" {{ old('country_code', substr($doctor->phone, 0, 3)) == '+57' ? 'selected' : '' }}>🇨🇴 +57</option>
                                            <option value="+54" {{ old('country_code', substr($doctor->phone, 0, 3)) == '+54' ? 'selected' : '' }}>🇦🇷 +54</option>
                                            <option value="+591" {{ old('country_code', substr($doctor->phone, 0, 4)) == '+591' ? 'selected' : '' }}>🇧🇴 +591</option>
                                            <option value="+55" {{ old('country_code', substr($doctor->phone, 0, 3)) == '+55' ? 'selected' : '' }}>🇧🇷 +55</option>
                                            <option value="+56" {{ old('country_code', substr($doctor->phone, 0, 3)) == '+56' ? 'selected' : '' }}>🇨🇱 +56</option>
                                            <option value="+593" {{ old('country_code', substr($doctor->phone, 0, 4)) == '+593' ? 'selected' : '' }}>🇪🇨 +593</option>
                                        </select>
                                        {{-- Limpiamos el número quitando el indicativo si ya viene guardado en la BD --}}
                                        <input type="tel" name="phone" id="phone" required maxlength="10" pattern="[0-9]{10}" 
                                            placeholder="3001234567" 
                                            value="{{ old('phone', strlen($doctor->phone) > 10 ? substr($doctor->phone, -10) : $doctor->phone) }}"
                                            class="w-full border-0 focus:ring-0 p-4 text-sm text-slate-800 rounded-r-2xl">
                                    </div>
                                </div>

                                <!-- Años de Experiencia -->
                                <div class="flex flex-col mb-2">
                                    <x-label for="experience_years" value="Años de Experiencia Profesional" class="mb-1 text-slate-500 font-bold text-xs" />
                                    <input type="number" name="experience_years" id="experience_years" min="0" max="100"
                                        value="{{ old('experience_years', $doctor->experience_years) }}" required
                                        placeholder="Ej: 8"
                                        class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner text-slate-800">
                                </div>

                                <!-- Género (Expandido a ancho completo para balancear la grilla) -->
                                <div class="flex flex-col md:col-span-2">
                                    <x-label for="gender" value="Género" class="mb-1 text-slate-500 font-bold text-xs" />
                                    <select id="gender" name="gender" required 
                                        class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm text-slate-500 bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner">
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

                            <!-- Cuadro de Biografía Clínica (UX e Identidad Unificada OpenDoctor) -->
                            <div class="pt-6 border-t border-slate-100 mt-5">
                                <x-label for="bio" value="Perfil / Biografía Médica" class="mb-2 text-slate-700 font-bold text-sm tracking-tight" />
                                <textarea id="bio" name="bio" rows="5" 
                                    placeholder="Escribe sobre tus enfoques clínicos, estudios o un saludo de confianza para los pacientes..."
                                    class="w-full rounded-[2rem] border-slate-200 p-5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner text-slate-800 placeholder-slate-400/80 leading-relaxed">{{ old('bio', $doctor->bio) }}</textarea>
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
