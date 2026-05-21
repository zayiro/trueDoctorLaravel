<x-guest-layout>
    <div class="mt-5"></div>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div>
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
                
                <div class="mt-4 relative" x-data="specialtiesSelect()" @click.away="open = false">

                    <!-- SELECT INTERNO OCULTO PARA COMUNICARSE CON EL CONTROLADOR DE LARAVEL -->
                    <select name="specialties[]" multiple class="hidden">
                        <template x-for="id in selected" :key="id">
                            <option :value="id" selected></option>
                        </template>
                    </select>
                    
                    <x-label for="med-search" value="{{ __('Especialidades (Puedes elegir una o varias)') }}" />
                    <!-- CONTENEDOR VISUAL INTERACTIVO -->
                    <div class="w-full min-h-[50px] flex flex-wrap gap-2 items-center rounded-xl border border-slate-200 p-2 bg-white focus-within:ring-2 focus-within:ring-blue-500 cursor-text"
                        @click="document.getElementById('med-search').focus(); open = true">
                        
                        <!-- Muestra las especialidades elegidas en forma de etiquetas -->
                        <template x-for="item in selectedLabels()" :key="item.id">
                            <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 text-sm px-3 py-1 rounded-full font-medium border border-blue-100">
                                <span x-text="item.name"></span>
                                <button type="button" @click.stop="toggle(item.id)" class="text-blue-500 hover:text-blue-800 font-bold ml-1">&times;</button>
                            </span>
                        </template>

                        <!-- Buscador integrado -->
                        <input x-ref.searchInput
                            id="med-search"
                            type="text" 
                            x-model="search" 
                            @focus="open = true"
                            placeholder="Buscar especialidades..." 
                            class="flex-1 min-w-[150px] outline-none border-none p-1 text-sm text-slate-700 focus:ring-0">
                    </div>

                    <!-- DESPLEGABLE CON LAS OPCIONES DISPONIBLES FILTRADAS -->
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

                <div class="mt-4">
                    
                    <x-label for="phone" value="{{ __('Número celular (Ej.: 3026433874)') }}" />
                    <div class="relative mt-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                        </div>
                        <x-input id="phone" class="block mt-1 w-full pl-10" type="tel" name="phone" :value="old('phone')" required autofocus autocomplete="phone" />
                    </div>
                </div>

                <div>
                    <x-label for="password" value="{{ __('Password') }}" />
                    <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                </div>

                <div>
                    <x-label for="password_confirmation" value="{{ __('Confirm Password') }}" />
                    <x-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
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
    </x-authentication-card>
</x-guest-layout>
