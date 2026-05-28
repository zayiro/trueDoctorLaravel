<x-guest-layout>
    <div class="mt-5"></div>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>
        
        <div x-data="{ showPass: false, showConfirm: false }">
            <h2 class="text-2xl font-black text-gray-800 mb-6">Registro de Doctores</h2>
            <x-validation-errors class="mb-4" />

            @if (session('error'))
                <div class="mb-4 font-medium text-sm text-red-600">
                    {{ session('error') }}
                </div>
            @endif
            <form action="{{ route('partner.register.store') }}" method="POST" class="space-y-4">
                @csrf
                
                <div>
                    <x-label for="name" value="{{ __('Name') }}" />
                    <x-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="nombre del especialista" />
                </div>

                <div class="mt-4">
                    <x-label for="email" value="{{ __('Email') }}" />
                    <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="doctor@example.com" />
                </div>

                <div class="mt-4">
                    <x-label for="identification" value="{{ __('Número de identificación') }}" />
                    <x-input id="identification" class="block mt-1 w-full" type="text" name="identification" :value="old('identification')" required autocomplete="169447522" />
                </div>

                <div class="mt-4">
                    <x-label for="medical_license" value="{{ __('Tarjeta profesional') }}" />
                    <x-input id="medical_license" class="block mt-1 w-full" type="text" name="medical_license" :value="old('medical_license')" required autocomplete="Licencia medica" />
                </div>
                
                <!-- SELECTOR DE ESPECIALIDADES CON ALPINE -->
                <div class="mt-4 relative" x-data="specialtiesSelect()" @click.away="open = false">
                    <select name="specialties[]" multiple class="hidden">
                        <template x-for="id in selected" :key="id">
                            <option :value="id" selected></option>
                        </template>
                    </select>
                    
                    <x-label for="med-search" value="{{ __('Especialidades (Puedes elegir una o varias)') }}" />
                    <div class="w-full min-h-[50px] flex flex-wrap gap-2 items-center rounded-xl border border-slate-200 p-2 bg-white focus-within:ring-2 focus-within:ring-blue-500 cursor-text"
                        @click="document.getElementById('med-search').focus(); open = true">
                        
                        <template x-for="item in selectedLabels()" :key="item.id">
                            <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 text-sm px-3 py-1 rounded-full font-medium border border-blue-100">
                                <span x-text="item.name"></span>
                                <button type="button" @click.stop="toggle(item.id)" class="text-blue-500 hover:text-blue-800 font-bold ml-1">&times;</button>
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
                                class="px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer transition-colors"
                                x-text="option.name">
                            </div>
                        </template>
                    </div>
                </div>                

                <!-- CAMPO CELULAR BLINDADO A 10 NÚMEROS -->
                <div class="mt-4">
                    <x-label for="phone" value="{{ __('Número celular') }}" />
                    <div class="flex mt-1 rounded-xl shadow-sm border border-slate-200 bg-white focus-within:ring-2 focus-within:ring-blue-500 overflow-hidden">
                        <!-- Selector de Indicativo de País (Comienza por Colombia y Suramérica) -->
                        <select name="country_code" id="country_code" required 
                                class="bg-slate-50 text-slate-700 text-sm border-0 border-r border-slate-200 rounded-l-xl focus:ring-0 px-5 cursor-pointer">
                            <option value="+57" {{ old('country_code') == '+57' ? 'selected' : '' }}>🇨🇴 +57</option>
                            <option value="+54" {{ old('country_code') == '+54' ? 'selected' : '' }}>🇦🇷 +54</option>
                            <option value="+591" {{ old('country_code') == '+591' ? 'selected' : '' }}>🇧🇴 +591</option>
                            <option value="+55" {{ old('country_code') == '+55' ? 'selected' : '' }}>🇧🇷 +55</option>
                            <option value="+56" {{ old('country_code') == '+56' ? 'selected' : '' }}>🇨🇱 +56</option>
                            <option value="+593" {{ old('country_code') == '+593' ? 'selected' : '' }}>🇪🇨 +593</option>
                            <option value="+595" {{ old('country_code') == '+595' ? 'selected' : '' }}>🇵🇾 +595</option>
                            <option value="+51" {{ old('country_code') == '+51' ? 'selected' : '' }}>🇵🇪 +51</option>
                            <option value="+598" {{ old('country_code') == '+598' ? 'selected' : '' }}>🇺🇾 +598</option>
                            <option value="+58" {{ old('country_code') == '+58' ? 'selected' : '' }}>🇻🇪 +58</option>
                            <option value="+592" {{ old('country_code') == '+592' ? 'selected' : '' }}>🇬🇾 +592</option>
                            <option value="+597" {{ old('country_code') == '+597' ? 'selected' : '' }}>🇸🇷 +597</option>
                        </select>

                        <!-- Input del Teléfono Blindado sin bordes nativos -->
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

                <!-- CAMPO PASSWORD CON OJO INTERACTIVO -->
                <div class="mt-4">
                    <x-label for="password" value="{{ __('Password') }}" />
                    <div class="relative mt-1 rounded-md shadow-sm">
                        <x-input 
                            id="password" 
                            class="block w-full pr-10" 
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

                <!-- CAMPO CONFIRM PASSWORD CON OJO INTERACTIVO -->
                <div class="mt-4">
                    <x-label for="password_confirmation" value="{{ __('Confirm Password') }}" />
                    <div class="relative mt-1 rounded-md shadow-sm">
                        <x-input 
                            id="password_confirmation" 
                            class="block w-full pr-10" 
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

                <div class="flex items-center justify-end mt-4">
                    <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                        {{ __('Already registered?') }}
                    </a>

                    <x-button class="ms-4">
                        {{ __('Register') }}
                    </x-button>
                </div>
            </form>
        </div>
    </x-authentication-card>
</x-guest-layout>

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
