<x-guest-layout>
    <div class="mt-8">
        <div class="mx-auto max-w-6xl px-4 py-8 lg:py-14">
            <div class="grid lg:grid-cols-2 rounded-[28px] overflow-hidden shadow-2xl shadow-black/10 border border-black/5">

                {{-- PANEL IZQUIERDO: mensaje de marca --}}
                <div class="relative px-8 py-10 lg:p-12 flex flex-col justify-between"
                     style="background: linear-gradient(165deg,#0D3937 0%,#134744 55%,#186B62 100%); color:#F1ECE1;">

                    <div>
                        <div class="flex items-center gap-3 mb-12">
                            <svg width="38" height="38" viewBox="0 0 40 40" fill="none">
                                <rect x="6" y="8" width="28" height="26" rx="5" stroke="#D9A441" stroke-width="1.4"/>
                                <line x1="6" y1="15" x2="34" y2="15" stroke="#D9A441" stroke-width="1.4"/>
                                <line x1="13" y1="5" x2="13" y2="11" stroke="#D9A441" stroke-width="1.4" stroke-linecap="round"/>
                                <line x1="27" y1="5" x2="27" y2="11" stroke="#D9A441" stroke-width="1.4" stroke-linecap="round"/>
                                <path d="M13.5 24l4 4L27 18.5" stroke="#F1ECE1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span class="od-eyebrow text-[11px]" style="color:#BFE0D6;">OpenDoctor · Pacientes</span>
                        </div>

                        <h1 class="od-display text-[2rem] lg:text-[2.4rem] leading-[1.18] font-medium mb-5">
                            Encuentra a tu médico.<br>Agenda en minutos.
                        </h1>
                        <p class="text-[15px] leading-relaxed max-w-sm mb-11" style="color:#CFE6DC;">
                            Crea tu cuenta para buscar especialistas, reservar tu cita y recibir recordatorios automáticos por Email y WhatsApp para que nunca se te pase la hora.
                        </p>

                        <ol class="space-y-5 mt-3">
                            <li class="flex gap-4">
                                <span class="od-eyebrow shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-[11px]" style="background:rgba(217,164,65,.16); color:#D9A441; border:1px solid rgba(217,164,65,.4);">1</span>
                                <span class="text-sm pt-0.5" style="color:#E6F1EC;">Creas tu cuenta</span>
                            </li>
                            <li class="flex gap-4">
                                <span class="od-eyebrow shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-[11px]" style="background:rgba(217,164,65,.16); color:#D9A441; border:1px solid rgba(217,164,65,.4);">2</span>
                                <span class="text-sm pt-0.5" style="color:#E6F1EC;">Buscas tu médico ideal</span>
                            </li>
                            <li class="flex gap-4">
                                <span class="od-eyebrow shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-[11px]" style="background:rgba(217,164,65,.16); color:#D9A441; border:1px solid rgba(217,164,65,.4);">3</span>
                                <span class="text-sm pt-0.5" style="color:#E6F1EC;">Agenda tu cita (Virtual por Zoom o presencial en consultorio)</span>
                            </li>
                        </ol>
                    </div>

                    <div class="hidden lg:flex gap-2 flex-wrap mt-12">
                        @foreach (['Recordatorios por WhatsApp', 'Todas las especialidades', 'Reserva sin llamadas'] as $badge)
                            <span class="od-eyebrow text-[10px] px-3 py-1.5 rounded-full" style="background:rgba(255,255,255,.06); color:#BFE0D6; border:1px solid rgba(255,255,255,.14);">
                                {{ $badge }}
                            </span>
                        @endforeach
                    </div>
                </div>

                {{-- PANEL DERECHO: formulario --}}
                <div class="bg-[#FBF8F3] px-6 py-10 lg:p-12">
                    <p class="od-eyebrow text-[11px] mb-2" style="color:#8FA39D;">Registro de pacientes</p>
                    <h2 class="od-display text-[1.7rem] mb-7" style="color:#12302E;">Crea tu cuenta</h2>

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

                        <div class="flex items-center justify-between mt-7">
                            <a class="text-sm text-slate-500 hover:text-[#12302E] underline-offset-2 hover:underline" href="{{ route('login') }}">
                                {{ __('¿Ya tienes cuenta?') }}
                            </a>

                            <button type="submit" ::disabled="loading"
                                class="inline-flex items-center px-6 py-2.5 rounded-xl text-sm font-semibold text-white shadow-sm transition disabled:opacity-70"
                                style="background:#E4633C;"
                                onmouseover="this.style.background='#C94F2C'" onmouseout="this.style.background='#E4633C'">
                                <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" x-cloak>
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="loading ? 'Validando...' : 'Crear cuenta'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>