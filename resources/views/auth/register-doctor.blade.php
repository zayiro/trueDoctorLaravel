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
                                <circle cx="20" cy="20" r="17.5" stroke="#D9A441" stroke-width="1.4"/>
                                <g stroke="#D9A441" stroke-width="1.4" stroke-linecap="round">
                                    <line x1="20" y1="4" x2="20" y2="7.2"/>
                                    <line x1="20" y1="32.8" x2="20" y2="36"/>
                                    <line x1="4" y1="20" x2="7.2" y2="20"/>
                                    <line x1="32.8" y1="20" x2="36" y2="20"/>
                                    <line x1="9.1" y1="9.1" x2="11.3" y2="11.3"/>
                                    <line x1="28.7" y1="28.7" x2="30.9" y2="30.9"/>
                                    <line x1="9.1" y1="30.9" x2="11.3" y2="28.7"/>
                                    <line x1="28.7" y1="11.3" x2="30.9" y2="9.1"/>
                                </g>
                                <path d="M13 20.5l4.5 4.5L27.5 14.5" stroke="#F1ECE1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span class="od-eyebrow text-[11px]" style="color:#BFE0D6;">OpenDoctor · Especialistas</span>
                        </div>

                        <h1 class="od-display text-[2rem] lg:text-[2.4rem] leading-[1.18] font-medium mb-5">
                            Cada cita, confirmada.<br>Cada paciente, recordado.
                        </h1>
                        <p class="text-[15px] leading-relaxed max-w-sm mb-11" style="color:#CFE6DC;">
                            Crea tu perfil de especialista y deja que la agenda se ordene sola: confirmaciones y recordatorios automáticos por Email y WhatsApp, y una ficha que tus pacientes encuentran sin esfuerzo.
                        </p>

                        <ol class="space-y-5 mt-3">
                            <li class="flex gap-4">
                                <span class="od-eyebrow shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-[11px]" style="background:rgba(217,164,65,.16); color:#D9A441; border:1px solid rgba(217,164,65,.4);">1</span>
                                <span class="text-sm pt-0.5" style="color:#E6F1EC;">Creas tu perfil profesional</span>
                            </li>
                            <li class="flex gap-4">
                                <span class="od-eyebrow shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-[11px]" style="background:rgba(217,164,65,.16); color:#D9A441; border:1px solid rgba(217,164,65,.4);">2</span>
                                <span class="text-sm pt-0.5" style="color:#E6F1EC;">Verificamos tu tarjeta profesional</span>
                            </li>
                            <li class="flex gap-4">
                                <span class="od-eyebrow shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-[11px]" style="background:rgba(217,164,65,.16); color:#D9A441; border:1px solid rgba(217,164,65,.4);">3</span>
                                <span class="text-sm pt-0.5" style="color:#E6F1EC;">Empiezas a recibir pacientes</span>
                            </li>
                        </ol>
                    </div>

                    <div class="hidden lg:flex gap-2 flex-wrap mt-12">
                        @foreach (['Recordatorios por WhatsApp', 'Agenda centralizada', 'Todas las especialidades'] as $badge)
                            <span class="od-eyebrow text-[10px] px-3 py-1.5 rounded-full" style="background:rgba(255,255,255,.06); color:#BFE0D6; border:1px solid rgba(255,255,255,.14);">
                                {{ $badge }}
                            </span>
                        @endforeach
                    </div>
                </div>

                {{-- PANEL DERECHO: formulario --}}
                <div class="bg-[#FBF8F3] px-6 py-10 lg:p-12">
                    <p class="od-eyebrow text-[11px] mb-2" style="color:#8FA39D;">Registro de especialistas</p>
                    <h2 class="od-display text-[1.7rem] mb-7" style="color:#12302E;">Crea tu cuenta</h2>

                    <x-validation-errors class="mb-4" />

                    @if (session('error'))
                        <div class="mb-4 font-medium text-sm text-red-600">
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
                                <x-label for="identification" value="{{ __('N° de identificación') }}" />
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
                            <x-label for="phone" value="{{ __('Número celular') }}" />
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