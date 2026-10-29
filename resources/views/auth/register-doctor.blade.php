@php
    $invalid = fn (string $field) => $errors->has($field);

    $specialtyOptions = $specialties
        ->map(fn ($s) => ['id' => (int) $s->id, 'name' => $s->name])
        ->values();

    // old() devuelve strings; los ids de las opciones son enteros
    $selectedSpecialties = array_map('intval', (array) old('specialties', []));

    // Beneficios de la columna derecha (mismo contenido que la home)
    $benefits = [
        [
            'title' => 'Mayor visibilidad local',
            'text'  => 'Los pacientes que buscan tu especialidad en tu ciudad te encuentran.',
            'paths' => [
                'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z',
                'M15 12a3 3 0 11-6 0 3 3 0 016 0z',
            ],
        ],
        [
            'title' => 'Gestión de citas inteligente',
            'text'  => 'Agenda automatizada para reducir el ausentismo y organizar tus horarios.',
            'paths' => [
                'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
            ],
        ],
        [
            'title' => 'Perfil médico verificado',
            'text'  => 'Genera confianza mostrando tus certificaciones y opiniones reales.',
            'paths' => [
                'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
        ],
    ];
@endphp
<x-guest-layout
    meta_title="Registro de Doctores | Únete a OpenDoctorOnline"
    meta_description="Crea tu cuenta profesional en OpenDoctor de forma gratuita. Digitaliza tu consultorio, automatiza recordatorios por WhatsApp y gestiona tu agenda médica sin esfuerzo."
>
    <section class="relative z-40 bg-white overflow-hidden py-16 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-12">
                <!-- Etiqueta superior -->
                    <span class="inline-flex self-center lg:self-start items-center rounded-full bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10 px-4 py-2 pt-2.5 text-xs font-semibold uppercase tracking-wider text-blue-800 mb-6">
                    Para Profesionales de la Salud
                    </span>
                    
                    <!-- Título Principal -->
                    <h2 class="text-center text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-950 sm:text-4xl lg:text-5xl">
                    Digitaliza tu consulta y expande tu <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">reputación médica</span>
                    </h2>
                    
                    <p class="text-center mt-4 text-base sm:text-lg text-slate-600 max-w-5xl mx-auto lg:mx-0">
                    Únete al directorio de especialistas de <span class="text-lg font-black text-slate-900 tracking-tight">
                        Open<span class="text-indigo-600">Doctor</span><span class="text-emerald-500">Online</span></span>. Permite que cientos de pacientes encuentren tu perfil, agenden citas en línea y conozcan tu trayectoria profesional.
                    </p>
            </div>
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-2 lg:items-center">
            
                <!-- Columna Izquierda: Contenido y Propuesta de Valor -->
                <div class="flex flex-col">                    
                    @if ($errors->any())
                        <div role="alert" class="mt-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                            </svg>
                            <p>Revisa los campos marcados para continuar.</p>
                        </div>
                    @endif

                    @if (session('error'))
                        <div role="alert" class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-800">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('partner.register.store') }}" method="POST" class="space-y-4" x-data="{ showPass: false, showConfirm: false, loading: false }" @submit="loading = true">
                        @csrf

                        <div>
                            <x-label for="name" value="{{ __('Nombre completo') }}" />
                            <x-input id="name" class="block mt-1 w-full rounded-xl border-slate-200 focus:border-[#1F6E63] focus:ring-[#1F6E63]" type="text" name="name" :value="old('name')" required autofocus placeholder="Nombre y apellido" />
                        </div>

                        <div class="mt-4">
                            <x-label for="email" value="{{ __('Correo electrónico') }}" />
                            <x-input id="email" class="block mt-1 w-full rounded-xl border-slate-200 focus:border-[#1F6E63] focus:ring-[#1F6E63]" type="email" name="email" :value="old('email')" required placeholder="micuenta@correo.com" />
                        </div>

                        <div class="grid grid-cols-2 gap-3 mt-4">
                            <div>
                                <x-label for="identification" value="{{ __('Identificación') }}" />
                                <x-input id="identification" class="block mt-1 w-full rounded-xl border-slate-200 focus:border-[#1F6E63] focus:ring-[#1F6E63]" type="text" name="identification" :value="old('identification')" required placeholder="169447522" />
                            </div>
                            <div>
                                <x-label for="medical_license" value="{{ __('Tarjeta profesional') }}" />
                                <x-input id="medical_license" class="block mt-1 w-full rounded-xl border-slate-200 focus:border-[#1F6E63] focus:ring-[#1F6E63]" type="text" name="medical_license" :value="old('medical_license')" required placeholder="Número de licencia" />
                            </div>
                        </div>

                        <!-- SELECTOR DE ESPECIALIDADES -->
                        <div class="mt-4 relative" x-data="specialtiesSelect()" @click.away="open = false">
                            <select name="specialties[]" multiple class="hidden">
                                <template x-for="id in selected" :key="id">
                                    <option :value="id" selected></option>
                                </template>
                            </select>

                            <x-label for="med-search" value="{{ __('Especialidades (puedes elegir una o varias)') }}" />
                            <div class="w-full min-h-[50px] flex flex-wrap gap-2 items-center rounded-xl border border-slate-200 p-2 bg-white focus-within:ring-2 focus-within:ring-[#1F6E63] focus-within:border-[#1F6E63] cursor-text mt-1"
                                @click="document.getElementById('med-search').focus(); open = true">

                                <template x-for="item in selectedLabels()" :key="item.id">
                                    <span class="inline-flex items-center gap-1 text-sm px-3 py-1 rounded-full font-medium" style="background:#E7F0EC; color:#134744; border:1px solid #CFE0D6;">
                                        <span x-text="item.name"></span>
                                        <button type="button" @click.stop="toggle(item.id)" class="font-bold ml-1" style="color:#1F6E63;">&times;</button>
                                    </span>
                                </template>

                                <input x-ref.searchInput
                                    id="med-search"
                                    type="text"
                                    x-model="search"
                                    @focus="open = true"
                                    placeholder="Buscar especialidades..."
                                    class="flex-1 min-w-[150px] outline-none border-none p-1 text-sm text-slate-700 focus:ring-0">
                            </div>

                            <div x-show="open && filteredOptions().length > 0"
                                x-transition
                                class="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-y-auto"
                                style="display: none;">

                                <template x-for="option in filteredOptions()" :key="option.id">
                                    <div @click="toggle(option.id); search = ''; document.getElementById('med-search').focus();"
                                        class="px-4 py-2.5 text-sm text-slate-700 hover:bg-[#F1F7F4] cursor-pointer transition-colors"
                                        x-text="option.name">
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- CELULAR -->
                        <div class="mt-4">
                            <x-label for="phone" value="{{ __('Celular') }}" />
                            <div class="flex mt-1 rounded-xl shadow-sm border border-slate-200 bg-white focus-within:ring-2 focus-within:ring-[#1F6E63] focus-within:border-[#1F6E63] overflow-hidden">                                
                                <div class="flex items-center gap-2 bg-slate-50 border-0 border-r border-slate-200 rounded-l-xl px-4 text-sm text-slate-700">
                                    <img src="https://flagcdn.com/w20/co.png" alt="Colombia" class="w-5 h-auto rounded-sm">
                                    <span>+57</span>
                                    <input type="hidden" name="country_code" value="+57">
                                </div>

                                <x-input
                                    id="phone"
                                    class="block w-full border-0 focus:ring-0 p-2.5 text-sm text-slate-900 rounded-r-xl"
                                    type="tel"
                                    name="phone"
                                    :value="old('phone')"
                                    required
                                    maxlength="10"
                                    pattern="[0-9]{10}"
                                    placeholder="3026433874"
                                />
                            </div>
                        </div>

                        <!-- PASSWORD -->
                        <div class="mt-4">
                            <x-label for="password" value="{{ __('Contraseña') }}" />
                            <div class="relative mt-1 rounded-xl shadow-sm">
                                <x-input
                                    id="password"
                                    class="block w-full pr-10 rounded-xl border-slate-200 focus:border-[#1F6E63] focus:ring-[#1F6E63]"
                                    ::type="showPass ? 'text' : 'password'"
                                    name="password"
                                    required
                                    autocomplete="new-password"
                                />
                                <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none">
                                    <svg x-show="showPass" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="!showPass" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-cloak>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 014.132-5.411m0 0L4 3m1.37 1.37L21 21m-2.13-2.13l-1.37-1.37M9.88 9.88a3 3 0 104.24 4.24m-1.07-4.24L12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- CONFIRM PASSWORD -->
                        <div class="mt-4">
                            <x-label for="password_confirmation" value="{{ __('Confirmar contraseña') }}" />
                            <div class="relative mt-1 rounded-xl shadow-sm">
                                <x-input
                                    id="password_confirmation"
                                    class="block w-full pr-10 rounded-xl border-slate-200 focus:border-[#1F6E63] focus:ring-[#1F6E63]"
                                    ::type="showConfirm ? 'text' : 'password'"
                                    name="password_confirmation"
                                    required
                                    autocomplete="new-password"
                                />
                                <button type="button" @click="showConfirm = !showConfirm" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none">
                                    <svg x-show="showConfirm" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="!showConfirm" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-cloak>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 014.132-5.411m0 0L4 3m1.37 1.37L21 21m-2.13-2.13l-1.37-1.37M9.88 9.88a3 3 0 104.24 4.24m-1.07-4.24L12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-col items-center mt-7 pt-6 space-y-6">
                            <button type="submit" ::disabled="loading"
                                class="group w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-8 rounded-xl transition-all duration-200 shadow-md shadow-blue-600/15 flex items-center justify-center gap-2.5 text-base transform active:scale-[0.98]">                                                                
                                <span x-text="loading ? 'Validando...' : 'Registrarme como Doctor'"></span> 
                                <svg x-show="loading" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg x-show="!loading" class="w-4 h-4 text-blue-200 group-hover:text-white group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>                               
                            </button>
                                
                            <a class="text-sm text-slate-500 hover:text-[#12302E] underline-offset-2 hover:underline" href="{{ route('login') }}">
                                    {{ __('¿Ya tienes cuenta?') }}
                            </a>                            
                        </div>
                    </form>
                </div>

                <!-- Columna Derecha: Composición de Imagen Estilizada -->
                <div class="relative mx-auto w-full max-w-md lg:max-w-none lg:mx-0 flex justify-center">
                    <!-- Elementos decorativos de fondo -->
                    <div class="absolute -top-4 -left-4 w-72 h-72 bg-blue-400 rounded-full filter blur-3xl opacity-20 animate-pulse"></div>
                    <div class="absolute -bottom-4 -right-4 w-72 h-72 bg-indigo-400 rounded-full filter blur-3xl opacity-20"></div>
                    
                    <!-- Contenedor de Imagen -->
                    <div class="relative overflow-hidden transform lg:rotate-1 hover:rotate-0 transition duration-300">
                    <img 
                        src="{{ asset('images/professional-directory-register-medical.png') }}" 
                        alt="Médico especialista usando plataforma OpenDoctor" 
                        class="h-[350px] w-full object-cover sm:h-[450px] md:w-[500px]"
                        style="mask-image: linear-gradient(to bottom, black 75%, transparent 100%); -webkit-mask-image: linear-gradient(to bottom, black 75%, transparent 100%);"
                        loading="lazy"
                    />
                    </div>                
                </div>

            </div>
        </div>
    </section>

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('specialtiesSelect', () => ({
            open: false,
            search: '',
            selected: {{ json_encode(old('specialties', [])) }},
            options: @json($specialties->map(fn($s) => ['id' => $s->id, 'name' => $s->name])),

            toggle(id) {
                id = parseInt(id);
                if (this.selected.includes(id)) {
                    this.selected = this.selected.filter(item => item !== id);
                } else {
                    this.selected.push(id);
                }
            },
            filteredOptions() {
                return this.options.filter(option =>
                    option.name.toLowerCase().includes(this.search.toLowerCase()) &&
                    !this.selected.includes(option.id)
                );
            },
            selectedLabels() {
                return this.options.filter(option => this.selected.includes(option.id));
            }
        }));
    });
    </script>
</x-guest-layout>