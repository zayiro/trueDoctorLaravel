<x-guest-layout
    meta_title="Registro de Pacientes | Únete a OpenDoctorOnline"
    meta_description="Encuentra a tus especialistas, recibe recordatorios automáticos por WhatsApp y gestiona tus citas médicas desde un solo lugar y sin esfuerzo."
>
    <section class="relative z-40 bg-white overflow-hidden py-16 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-12">
                <!-- Etiqueta superior -->
                    <span class="inline-flex self-center lg:self-start items-center rounded-full bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10 px-4 py-2 pt-2.5 text-xs font-semibold uppercase tracking-wider text-blue-800 mb-6">
                    Para Pacientes
                    </span>
                    
                    <!-- Título Principal -->
                    <h2 class="text-center text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-950 sm:text-4xl lg:text-5xl">
                        Accede a los mejores especialistas y <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">cuida tu bienestar</span>
                    </h2>
                    
                    <p class="text-center mt-4 text-base sm:text-lg text-slate-600 max-w-5xl mx-auto lg:mx-0">
                        Encuentra al especialista ideal en <span class="text-lg font-black text-slate-900 tracking-tight">
                        Open<span class="text-indigo-600">Doctor</span><span class="text-emerald-500">Online</span></span>. Explora perfiles de cientos de médicos, conoce su trayectoria profesional y agenda tu cita en línea de forma rápida y segura.<span class="text-lg font-black text-slate-900 tracking-tight">                        
                    </p>
            </div>
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-2 lg:items-center">
            
                <!-- Columna Izquierda: Contenido y Propuesta de Valor -->
                <div class="flex flex-col">                    
                    <x-validation-errors class="mb-4" />

                    <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{ showPass: false, showConfirm: false, loading: false }" @submit="loading = true">
                        @csrf

                        <div>
                            <x-label for="name" value="{{ __('Nombre completo') }}" />
                            <x-input id="name" class="block mt-1 w-full rounded-xl border-slate-200 focus:border-[#1F6E63] focus:ring-[#1F6E63]" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Nombre y apellido" />
                        </div>

                        <div class="mt-4">
                            <x-label for="email" value="{{ __('Correo electrónico') }}" />
                            <x-input id="email" class="block mt-1 w-full rounded-xl border-slate-200 focus:border-[#1F6E63] focus:ring-[#1F6E63]" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="micuenta@correo.com" />
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

                        @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                            <div class="mt-5">
                                <x-label for="terms">
                                    <div class="flex items-start gap-2">
                                        <x-checkbox name="terms" id="terms" required class="mt-0.5 rounded border-slate-300 text-[#1F6E63] focus:ring-[#1F6E63]" />
                                        <div class="text-sm text-slate-600">
                                            {!! __('Acepto los :terms_of_service y la :privacy_policy', [
                                                    'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="underline hover:text-[#12302E] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1F6E63] rounded-md">'.__('Términos de Servicio').'</a>',
                                                    'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="underline hover:text-[#12302E] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1F6E63] rounded-md">'.__('Política de Privacidad').'</a>',
                                            ]) !!}
                                        </div>
                                    </div>
                                </x-label>
                            </div>
                        @endif
                        
                        <div class="flex flex-col items-center mt-7 pt-6 space-y-6">
                            <button type="submit" ::disabled="loading"
                                class="group w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-8 rounded-xl transition-all duration-200 shadow-md shadow-blue-600/15 flex items-center justify-center gap-2.5 text-base transform active:scale-[0.98]">                                                                
                                <span x-text="loading ? 'Validando...' : 'Registrarme como Paciente'"></span> 
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
                        src="{{ asset('images/doctor-and-patient-consultation-isometric-illustration-png.webp') }}" 
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
</x-guest-layout>