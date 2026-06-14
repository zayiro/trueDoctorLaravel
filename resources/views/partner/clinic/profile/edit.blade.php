@php
    $breadcrumbs = [
        [
            'name' => 'Clínica',
            'href' => route('partner.clinic.profile.edit'),
        ],
        [
            'name' => 'Perfil Institucional',
        ]
    ];

    $clinicSettings = $clinic->settings; // Relación inyectada automáticamente vía Observers en tu plan corporativo
    $hasIndividualPlan = $clinicSettings && $clinicSettings->plan_id !== null;
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">  
    {{-- 🔒 ESTADO REACTIVO UNIFICADO: Controla las barras de carga y desactiva dobles clics en botones --}}
    <div class="mt-10 sm:mt-0 max-w-5xl mx-auto py-6" x-data="{ loading: false }">
        
        {{-- 🛡️ MENSAJES DE ÉXITO BLINDADOS DE PRODUCCIÓN --}}
        @if (session('success'))
            <div id="alert-success" class="flex items-center p-4 mb-6 text-green-800 rounded-2xl bg-green-50 border border-green-100 shadow-sm transition-opacity duration-500" role="alert">
                <!-- Heroicons SVG Nativo: Check-Circle -->
                <svg class="flex-shrink-0 w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="ms-3 text-sm font-medium">{{ session('success') }}</div>
                <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8" onclick="document.getElementById('alert-success').remove()">
                    <!-- Heroicons SVG Nativo: X-Mark -->
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        @endif

        {{-- 🛡️ ERRORES DE VALIDACIÓN GLOBAL --}}
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
        
        <!-- SECCIÓN 1: PLAN DE SUSCRIPCIÓN CORPORATIVO -->
        <div class="md:grid md:grid-cols-3 md:gap-6 mb-8 border-b border-gray-100 pb-8 dark:border-gray-800">
            <div class="md:col-span-1">
                <div class="px-4 sm:px-0">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Plan de Suscripción</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Gestiona los límites dinámicos de sedes físicas, especialidades y médicos para tu institución médica.
                    </p>
                </div>
            </div>

            @if ($hasIndividualPlan)
            <div class="mt-5 md:mt-0 md:col-span-2">
                <div class="shadow overflow-hidden sm:rounded-2xl bg-white p-6 border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($plans as $plan)
                            <form action="{{ route('plans.subscribe', $plan->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="plan" value="{{ $plan->plan }}">
                                
                                <button type="submit" 
                                    class="w-full text-left p-4 rounded-xl border-2 transition duration-200 
                                    {{ $clinicSettings->plan_id == $plan->id ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-100' : 'border-gray-200 hover:border-indigo-300 dark:border-gray-600' }}">
                                    
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="font-black text-gray-800 text-xs sm:text-sm dark:text-white">{{ $plan->name }}</span>
                                        @if($clinicSettings->plan_id == $plan->id)
                                            <span class="bg-indigo-600 text-white text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-tighter">Activo</span>
                                        @endif
                                    </div>

                                    <div class="space-y-1">
                                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest dark:text-gray-400">
                                            {{ $plan->max_addresses }} {{ Str::plural('Sede', $plan->max_addresses) }}
                                        </p>
                                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest dark:text-gray-400">
                                            {{ $plan->max_doctors }} {{ Str::plural('Especialista', $plan->max_doctors) }}
                                        </p>
                                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest dark:text-gray-400">
                                            {{ $plan->max_services }} Servicios Globales
                                        </p>
                                        <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                                            <span class="text-sm font-black text-indigo-600 dark:text-indigo-400">
                                                {{ $plan->price > 0 ? '$' . number_format($plan->price, 0) : 'Gratis' }}
                                            </span>
                                        </div>
                                    </div>
                                </button>
                            </form>
                        @endforeach
                    </div>
                    {{-- 🛡️ Nota aclaratoria del plan corporativo --}}
                    <div class="mt-6 p-4 bg-amber-50 rounded-2xl border border-amber-100 flex items-start gap-3">
                        <!-- Heroicons SVG Nativo: Information-Circle -->
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 111.063.852l-.708 2.836a.75.75 0 001.063.852l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"></path>
                        </svg>
                        <p class="text-xs text-amber-800 leading-relaxed">
                            <strong>Nota Corporativa:</strong> Al cambiar a un plan con menores beneficios, el sistema mantendrá activas solo las sedes y servicios más antiguos hasta alcanzar el límite permitido.
                        </p>
                    </div>
                </div>
            </div>
            @else
            {{-- 🛡️ ESTADO DE RESPALDO: CONTRATO CORPORATIVO CENTRALIZADO --}}
            <div class="mt-5 md:mt-0 md:col-span-2">
                <div class="shadow overflow-hidden sm:rounded-2xl bg-white p-8 border border-gray-100 text-center max-w-xl mx-auto dark:bg-gray-800 dark:border-gray-700">
                    
                    <!-- Icono Corporativo de Clínica Premium (Heroicons SVG Nativo: Building-Office-2) -->
                    <div class="mx-auto w-16 h-16 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center mb-4 ring-4 ring-indigo-100/50 dark:bg-gray-700 dark:text-indigo-400">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"></path>
                        </svg>
                    </div>

                    <!-- Mensaje Principal Institucional -->
                    <h3 class="text-lg font-black text-gray-900 mb-1 dark:text-white">Plan Institucional Centralizado</h3>
                    <p class="text-sm text-gray-500 max-w-md mx-auto mb-6 dark:text-gray-400">
                        Los límites de facturación y cuotas operativas de tu clínica están gobernados bajo un contrato corporativo centralizado.
                    </p>

                    <!-- Beneficios Habilitados por el SaaS -->
                    <div class="grid grid-cols-2 gap-3 text-left mb-6">
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 flex items-center gap-2.5 dark:bg-gray-700 dark:border-gray-600">
                            <span class="text-indigo-600 font-bold dark:text-indigo-400">✓</span>
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Multi-Sedes Corporativo</span>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 flex items-center gap-2.5 dark:bg-gray-700 dark:border-gray-600">
                            <span class="text-indigo-600 font-bold dark:text-indigo-400">✓</span>
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Staff de Médicos Ilimitado</span>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 flex items-center gap-2.5 dark:bg-gray-700 dark:border-gray-600">
                            <span class="text-indigo-600 font-bold dark:text-indigo-400">✓</span>
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Catálogo Global de Servicios</span>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 flex items-center gap-2.5 dark:bg-gray-700 dark:border-gray-600">
                            <span class="text-indigo-600 font-bold dark:text-indigo-400">✓</span>
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Historial de Auditoría Seguro</span>
                        </div>
                    </div>

                    <!-- Información Adicional y Acción de Soporte -->
                    <div class="pt-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-left dark:border-gray-700">
                        <div>
                            <h4 class="text-xs font-black text-gray-800 dark:text-white">¿Necesitas expandir tus cuotas de uso?</h4>
                            <p class="text-[11px] text-gray-500 max-w-xs mt-0.5 dark:text-gray-400">Contacta con soporte técnico para evaluar y ajustar las condiciones de tu cuenta corporativa.</p>
                        </div>
                        <a href="{{ route('contact.show') }}" 
                           class="w-full sm:w-auto text-center px-4 py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-black text-xs rounded-xl transition duration-150 shadow-sm dark:bg-indigo-600 dark:hover:bg-indigo-700">
                            Contactar Soporte
                        </a>
                    </div>
                </div>
            </div>
            @endif
        </div>
        <!-- DIVIDER VISUAL -->
        <div class="hidden sm:block" aria-hidden="true">
            <div class="py-5"><div class="border-t border-gray-200 dark:border-gray-800"></div></div>
        </div>

        <!-- 👇 SECCIÓN 2: FORMULARIO DE PERFIL PROFESIONAL INSTITUCIONAL -->
        <div class="mt-10 sm:mt-0 md:grid md:grid-cols-3 md:gap-6">
            <div class="md:col-span-1">
                <div class="px-4 sm:px-0">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Información Institucional</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Completa los identificadores legales, credenciales de habilitación pública y datos de contacto de tu clínica.
                    </p>
                </div>
            </div>

            <div class="mt-5 md:mt-0 md:col-span-2">
                {{-- 🔒 CONEXIÓN BACKEND UNIFICADA: PUT al ProfileClinicController --}}
                <form action="{{ route('partner.clinic.profile.update') }}" method="POST" @submit="loading = true">
                    @csrf
                    @method('PUT')

                    <div class="shadow overflow-hidden sm:rounded-2xl bg-white border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                        <div class="px-4 py-5 bg-white sm:p-6 space-y-6 dark:bg-gray-800">
                            
                            <!-- Grid de Datos Base -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                
                                <!-- Nombre Oficial de la Clínica -->
                                <div class="flex flex-col md:col-span-2">
                                    <x-label for="name" value="Nombre Oficial de la Institución" class="mb-1 text-slate-500 font-bold text-xs" />
                                    <input type="text" name="name" id="name" 
                                        value="{{ old('name', $clinic->user->name ?? $user->name) }}" required
                                        placeholder="Ej: Clínica Metropolitana del Valle"
                                        class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner text-slate-800 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>

                                <!-- Correo Electrónico Institucional -->
                                                                <!-- Correo Electrónico Institucional (Con Control Dinámico Readonly) -->
                                <div class="flex flex-col md:col-span-2">
                                    <x-label for="email" value="Correo Electrónico de Notificaciones" class="mb-1 text-slate-500 font-bold text-xs" />
                                    
                                    <input type="email" name="email" id="email" 
                                        value="{{ old('email', $user->email) }}" required
                                        placeholder="contacto@tuclinica.com"
                                        
                                        {{-- 🔒 CAPA 1 HTML: Bloquea la edición si la cuenta ya está aprobada --}}
                                        {{ $clinic->validation_status === 'approved' ? 'readonly' : '' }}
                                        
                                        {{-- 🎨 CAPA 2 TAILWIND: Estilos premium unificados según estado del Tenant --}}
                                        class="w-full rounded-2xl py-4 px-5 text-sm shadow-inner transition-colors
                                        {{ $clinic->validation_status === 'approved' 
                                            ? 'bg-slate-100 text-slate-400 cursor-not-allowed border-slate-200 focus:ring-0 focus:border-slate-200' 
                                            : 'border-slate-200 text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500' 
                                        }}">
                                        
                                    {{-- Micro-nota explicativa con Heroicon SVG Nativo --}}
                                    @if($clinic->validation_status === 'approved')
                                        <span class="text-[10px] text-emerald-600 font-semibold mt-1 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"></path></svg>
                                            Canal de seguridad verificado. Cambios de correo requieren validación de soporte.
                                        </span>
                                    @endif
                                </div>

                                <!-- NIT de la Clínica (Con Control Dinámico Readonly) -->
                                <div class="flex flex-col">
                                    <x-label for="nit" value="NIT / Número de Identificación Tributaria" class="mb-1 text-slate-500 font-bold text-xs" />
                                    
                                    <input type="text" name="nit" id="nit" 
                                        value="{{ old('nit', $clinic->nit ?? '') }}" required
                                        placeholder="Ej: 900.123.456-7"
                                        
                                        {{-- 🔒 CAPA 1 HTML: Bloquea la escritura si la clínica ya está aprobada --}}
                                        {{ $clinic->validation_status === 'approved' ? 'readonly' : '' }}
                                        
                                        {{-- 🎨 CAPA 2 TAILWIND: Cambia los estilos visuales en cascada según el estado del Tenant --}}
                                        class="w-full rounded-2xl py-4 px-5 text-sm shadow-inner transition-colors
                                        {{ $clinic->validation_status === 'approved' 
                                            ? 'bg-slate-100 text-slate-400 cursor-not-allowed border-slate-200 focus:ring-0 focus:border-slate-200' 
                                            : 'border-slate-200 text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500' 
                                        }}">
                                        
                                    {{-- Nota de auditoría sutil si el campo es de solo lectura --}}
                                    @if($clinic->validation_status === 'approved')
                                        <span class="text-[10px] text-emerald-600 font-semibold mt-1 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://w3.org"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"></path></svg>
                                            Identificador fiscal verificado. Para cambios, contacte a soporte.
                                        </span>
                                    @endif
                                </div>

                                <!-- Código REPS (Habilitación Oficial) -->
                                <div class="flex flex-col">
                                    <x-label for="reps_code" value="Código REPS" class="mb-1 text-slate-400 font-bold text-xs" />
                                    <input type="text" name="reps_code" id="reps_code" 
                                        value="{{ $clinic->reps_code }}" 
                                        
                                        {{-- 🔒 CAPA 1: Atributo HTML que bloquea la escritura en el navegador --}}
                                        readonly 
                                        
                                        {{-- 🎨 CAPA 2: Estilos Tailwind Premium para estados deshabilitados/lectura --}}
                                        class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm bg-slate-100 text-slate-400 cursor-not-allowed shadow-none focus:ring-0 focus:border-slate-200">
                                </div>

                                <!-- Número Celular Corporativo -->
                                <div class="flex flex-col">
                                    <x-label for="phone" value="Número Celular Corporativo" class="mb-1 text-slate-500 font-bold text-xs" />
                                    <div class="flex rounded-2xl border border-slate-200 bg-white focus-within:ring-2 focus-within:ring-indigo-500 overflow-hidden shadow-inner dark:bg-gray-700 dark:border-gray-600">
                                        <select name="country_code" required class="bg-slate-50 text-slate-600 text-xs border-0 border-r border-slate-200 focus:ring-0 px-7 cursor-pointer outline-none dark:bg-gray-600 dark:text-white dark:border-gray-500">
                                            <option value="+57" {{ old('country_code', substr($clinic->phone ?? '', 0, 3)) == '+57' ? 'selected' : '' }}>🇨🇴 +57</option>
                                            <option value="+54" {{ old('country_code', substr($clinic->phone ?? '', 0, 3)) == '+54' ? 'selected' : '' }}>🇦🇷 +54</option>
                                            <option value="+55" {{ old('country_code', substr($clinic->phone ?? '', 0, 3)) == '+55' ? 'selected' : '' }}>🇧🇷 +55</option>
                                            <option value="+56" {{ old('country_code', substr($clinic->phone ?? '', 0, 3)) == '+56' ? 'selected' : '' }}>🇨🇱 +56</option>
                                        </select>
                                        <input type="tel" name="phone" id="phone" required maxlength="10" pattern="[0-9]{10}" 
                                            placeholder="3001234567" 
                                            value="{{ old('phone', (strlen($clinic->phone ?? '') > 10) ? substr($clinic->phone, -10) : ($clinic->phone ?? '')) }}"
                                            class="w-full border-0 focus:ring-0 p-4 text-sm text-slate-800 rounded-r-2xl dark:bg-gray-700 dark:text-white">
                                    </div>
                                </div>

                                <!-- Años de Trayectoria -->
                                <div class="flex flex-col">
                                    <x-label for="experience_years" value="Años de Trayectoria Institucional" class="mb-1 text-slate-500 font-bold text-xs" />
                                    <input type="number" name="experience_years" id="experience_years" min="0" max="150"
                                        value="{{ old('experience_years', $clinic->experience_years ?? '') }}" required
                                        placeholder="Ej: 15"
                                        class="w-full rounded-2xl border-slate-200 py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner text-slate-800 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>

                            </div>
                            <!-- Módulo de Idiomas Corporativos (Matriz de Checkboxes) -->
                            <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1">Idiomas de Atención de la Clínica</label>
                                <span class="text-xs text-gray-400 block mb-3">Marca los idiomas en los que el staff médico realiza las valoraciones de salud.</span>
                                
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    @php
                                    $availableLanguages = [
                                        'es' => 'Español', 'en' => 'Inglés', 'pt' => 'Portugués', 'fr' => 'Francés', 'de' => 'Alemán'
                                    ];

                                    // 🛡️ BLINDAJE ANTI-TEXTO MULTI-TENANT: Decodificación nativa tolerante a fallos
                                    $rawLanguages = $clinic->languages ?? '["es"]';
                                    if (is_string($rawLanguages)) {
                                        $currentLanguages = json_decode($rawLanguages, true) ?? [];
                                    } else {
                                        $currentLanguages = is_array($rawLanguages) ? $rawLanguages : [];
                                    }
                                    @endphp

                                    @foreach($availableLanguages as $code => $name)
                                        <label class="relative flex items-start p-3 rounded-xl border border-gray-200 hover:bg-gray-50 cursor-pointer transition-colors dark:border-gray-600 dark:hover:bg-gray-700">
                                            <div class="flex items-center h-5">
                                                <input type="checkbox" name="languages[]" value="{{ $code }}"
                                                    {{ in_array($code, $currentLanguages) ? 'checked' : '' }}
                                                    class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded dark:bg-gray-700">
                                            </div>
                                            <div class="ml-3 text-xs font-bold text-gray-700 dark:text-gray-300">{{ $name }}</div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <!-- Módulo de Especialidades Institucionales (Portafolio de la Clínica) -->
                            <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1">Especialidades Médicas Habilitadas</label>
                                <span class="text-xs text-gray-400 block mb-3">Selecciona los servicios del portafolio médico que se encuentran activos en la institución.</span>
                                
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    @php
                                    // 🔒 CO-PROPIEDAD: Obtenemos los IDs de las especialidades vinculadas en clinic_specialty
                                    $currentSpecialties = isset($clinic->specialties) ? $clinic->specialties->pluck('id')->toArray() : [];
                                    @endphp

                                    @forelse($allSpecialties as $specialty)
                                        <label class="relative flex items-start p-3 rounded-xl border border-gray-200 hover:bg-gray-50 cursor-pointer transition-colors dark:border-gray-600 dark:hover:bg-gray-700">
                                            <div class="flex items-center h-5">
                                                <input type="checkbox" name="specialties[]" value="{{ $specialty->id }}"
                                                    {{ in_array($specialty->id, $currentSpecialties) ? 'checked' : '' }}
                                                    class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded dark:bg-gray-700">
                                            </div>
                                            <div class="ml-3 text-xs font-bold text-gray-700 dark:text-gray-300">{{ $specialty->name }}</div>
                                        </label>
                                    @empty
                                        <div class="col-span-2 sm:col-span-3 p-4 text-center bg-gray-50 rounded-xl text-xs text-gray-500 border border-dashed border-gray-200 dark:bg-gray-700 dark:border-gray-600">
                                            No hay especialidades registradas en la base de datos global.
                                        </div>
                                    @endforelse
                                </div>
                                @error('specialties') <span class="text-xs text-red-500 mt-2 block">{{ $message }}</span> @enderror
                            </div>
                            <!-- Cuadro de Reseña / Biografía Institucional -->
                            <div class="pt-6 border-t border-slate-100 mt-5 dark:border-gray-700">
                                <x-label for="bio" value="Perfil / Reseña Histórica de la Clínica" class="mb-2 text-slate-700 font-bold text-sm tracking-tight dark:text-gray-200" />
                                <textarea id="bio" name="bio" rows="5" 
                                    placeholder="Describe la infraestructura, misión de la clínica o enfoques de atención para inspirar confianza en los pacientes..."
                                    class="w-full rounded-[2rem] border-slate-200 p-5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-inner text-slate-800 placeholder-slate-400/80 leading-relaxed dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('bio', $clinic->bio ?? '') }}</textarea>
                            </div>

                        </div>

                        <!-- Botón de Envío con Estado de Carga Animado (Heroicons SVG Nativo: Cloud-Arrow-Up) -->
                        <div class="px-4 py-4 bg-gray-50 text-right sm:px-6 border-t border-gray-50 dark:bg-gray-900 dark:border-gray-800 flex justify-end">
                            <button type="submit" :disabled="loading" class="inline-flex justify-center items-center gap-1.5 py-3 px-6 border border-transparent shadow-sm text-xs font-black rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all uppercase tracking-wider cursor-pointer">
                                <!-- Animación Spin de Alpine si está en Loading -->
                                <svg x-show="loading" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" x-cloak>
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <!-- Icono Heroicons SVG Nativo si no está cargando -->
                                <svg x-show="!loading" class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://w3.org" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 17.5 4.5H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25z"></path>
                                </svg>
                                Guardar Perfil Institucional
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-admin-layout>
