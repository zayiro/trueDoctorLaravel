<x-guest-layout>
    <div class="max-w-4xl mx-auto py-12 px-4 mt-6">
        
        <!-- ALERTAS DE ERROR GENERALES -->
        @if(session('error'))
            <div class="flex items-center p-4 mb-4 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm" role="alert">
                <svg class="flex-shrink-0 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <div class="ms-3 text-sm font-bold">
                    {{ session('error') }}
                </div>
            </div>
        @endif
        
        <!-- CONTROL DE VALIDACIONES MASIVAS DE LARAVEL -->
        @if ($errors->any())
            <div class="flex p-4 mb-4 text-amber-800 rounded-2xl bg-amber-50 border border-amber-100 shadow-sm" role="alert">
                <svg class="flex-shrink-0 w-4 h-4 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <div class="ms-3">
                    <span class="text-sm font-black uppercase tracking-wide">Por favor corrige lo siguiente:</span>
                    <ul class="mt-1.5 list-disc list-inside text-xs font-medium">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
        
        <div class="bg-white rounded-3xl shadow-xl p-8 mt-5 mb-8 border border-gray-100">
            <h2 class="text-2xl font-black text-gray-800 mb-6">Completa tus datos</h2>
            
            @if (!$isSelfBooking)
                <!-- Inicializamos un estado de carga seguro para el control de los botones -->
                <form action="{{ route('appointments.process-patient') }}" method="POST" x-data="{ loading: false }" x-on:submit="loading = true">
                    @csrf
                    
                    <div class="space-y-5">
                        @guest
                            <!-- SELECTOR DE PESTAÑAS (NUEVO VS EXISTENTE) -->
                            <div class="flex p-1 bg-gray-100 rounded-2xl mb-6">
                                <button type="button" id="btn-new" onclick="toggleForm('new')" class="flex-1 py-2 text-sm font-bold rounded-xl transition bg-white shadow-sm text-blue-600">
                                    Soy nuevo
                                </button>
                                <button type="button" id="btn-exist" onclick="toggleForm('exist')" class="flex-1 py-2 text-sm font-bold rounded-xl transition text-gray-500">
                                    Ya tengo cuenta
                                </button>
                            </div>

                            <input type="hidden" name="has_account" id="has_account" value="{{ old('has_account', 'no') }}">
                            {{-- SECCIÓN: REGISTRO (NUEVO) — TOTALMENTE ENLAZADO --}}
                            <div id="section-new" class="space-y-5">                                
                                <div class="bg-emerald-50/60 border border-emerald-100 rounded-3xl p-5 flex gap-4 items-start mb-6">
                                    <div class="bg-emerald-600 text-white p-2.5 rounded-xl shadow-md shadow-emerald-100 flex-shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="font-black text-slate-800 text-sm uppercase tracking-wide">Tu seguridad médica es nuestra prioridad</h4>
                                        <p class="text-sm text-slate-500 mt-1 leading-relaxed font-medium">    
                                            Para proteger tu historial, generar las órdenes de consulta de forma legal y darte acceso seguro a 
                                            @if($isVirtualAddress)
                                                tu enlace de <strong class="text-blue-600 font-bold">Telemedicina</strong>,
                                            @else
                                                <strong class="text-blue-600 font-bold">tu cita presencial</strong>,
                                            @endif
                                            requerimos crear una clave de acceso única para ti. Tus datos médicos viajan totalmente encriptados.    
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <label for="name" class="block text-sm font-bold text-gray-700 mb-1">Nombre completo</label>
                                    <input type="text" name="name" id="name" value="{{ old('name') }}" autocomplete="name" required class="w-full rounded-xl border-gray-200 focus:ring-blue-500 input-new">
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="email" class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                                        <input type="email" name="email" id="email" value="{{ old('email') }}" autocomplete="email" required placeholder="correo@ejemplo.com" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 input-new">
                                    </div>
                                    <div>
                                        <label for="identification" class="block text-sm font-bold text-gray-700 mb-1">Identificación</label>
                                        <input type="text" name="identification" id="identification" value="{{ old('identification') }}" autocomplete="identification" required oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="16944752" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 input-new">
                                    </div>
                                </div>

                                <div>
                                    <label for="phone" class="block text-sm font-bold text-gray-700 mb-1">Teléfono móvil</label>
                                    <div class="flex mt-1 rounded-xl shadow-sm border border-slate-200 bg-white focus-within:ring-2 focus-within:ring-blue-500 overflow-hidden">
                                        <select name="country_code" id="country_code" required class="bg-slate-50 text-slate-700 text-sm border-0 border-r border-slate-200 rounded-l-xl focus:ring-0 px-5 cursor-pointer">
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
                                        </select>

                                        <!-- Convertido a input HTML5 nativo para eliminar fallos de inyección de clases -->
                                        <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" required maxlength="10" pattern="[0-9]{10}" placeholder="3026433874" class="block w-full border-0 focus:ring-0 p-2.5 text-sm text-slate-900 rounded-r-xl">
                                    </div>    
                                    <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wider mt-1.5 px-1 leading-relaxed">
                                        Se utilizará únicamente si el especialista requiere datos extra o para enviarte actualizaciones importantes de tu reserva.
                                    </p>
                                </div>
                            </div>
                            {{-- SECCIÓN: LOGIN (EXISTENTE) — CONTROLADO SILENCIOSAMENTE --}}
                            <div id="section-exist" class="space-y-5 hidden">
                                <div class="bg-amber-50 p-4 rounded-2xl text-sm text-amber-800 border border-amber-100 font-medium">
                                    Ingresa tus credenciales para continuar con tu cuenta de OpenDoctor.
                                </div>
                                <div>
                                    <label for="login_email" class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                                    <input type="email" name="login_email" id="login_email" value="{{ old('login_email') }}" autocomplete="email" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 input-exist">
                                    @error('login_email')
                                        <span class="text-xs text-red-600 font-bold mt-1 block">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div x-data="{ showPass: false }">
                                    <label for="login_password" class="block text-sm font-bold text-gray-700 mb-1">Contraseña</label>
                                    <div class="relative mt-1 rounded-md shadow-sm">
                                        <input id="login_password" name="login_password" class="block w-full pr-10 rounded-xl border-gray-200 focus:ring-blue-500 input-exist" :type="showPass ? 'text' : 'password'" autocomplete="current-password">
                                        <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none">
                                            <svg x-show="showPass" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <svg x-show="!showPass" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-cloak style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 014.132-5.411m0 0L4 3m1.37 1.37L21 21m-2.13-2.13l-1.37-1.37M9.88 9.88a3 3 0 104.24 4.24m-1.07-4.24L12 12"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endguest

                        @auth
                            <div class="bg-blue-50 p-4 rounded-2xl flex items-center gap-3 border border-blue-100">
                                <div class="bg-blue-600 text-white p-2 rounded-full font-bold w-10 h-10 flex items-center justify-center shadow-sm">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="text-sm text-blue-800 font-bold">{{ ucfirst(auth()->user()->name) }}</p>
                                    <p class="text-xs text-blue-600 font-semibold">Perfil activo para reservación</p>
                                </div>
                            </div>
                        @endauth

                        <!-- MOTIVO DE LA CONSULTA CON LIMITADOR TEXTAREA -->
                        <div x-data="{ notes: '{{ old('notes', '') }}', max: 150 }">
                            <label for="notes" class="block text-sm font-bold text-gray-700 mb-1">Motivo de la consulta</label>
                            <textarea name="notes" id="notes" autocomplete="notes" rows="4" required x-model="notes" :maxlength="max" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 text-sm text-gray-700 placeholder-gray-400 shadow-2xs" placeholder="Describe brevemente tus síntomas...">{{ old('notes', '') }}</textarea>
                            <div class="flex justify-between items-center mt-1.5 px-1">
                                <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wider">Máximo 150 caracteres permitidos.</p>
                                <p class="text-xs font-bold transition-colors duration-200" :class="notes.length >= max ? 'text-red-500' : 'text-slate-400'">
                                    <span x-text="notes.length">0</span> / <span x-text="max">150</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- BOTONERAS DE CONVERSIÓN CON CONTROL DE CARGA (FIXED HOVER Y COLOR) -->
                    <div class="mt-8 pt-4 border-t border-slate-100 flex items-center justify-between gap-4">
                        <a href="{{ url()->previous() }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-black uppercase tracking-wider text-slate-400 hover:text-slate-600 transition-colors focus:outline-none">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h16.5"/></svg>
                            Volver
                        </a>

                        <button type="submit" :disabled="loading" class="text-white bg-indigo-600 hover:bg-indigo-700 hover:-translate-y-0.5 active:translate-y-0 shadow-md shadow-indigo-100 font-bold text-xs uppercase tracking-wider py-3.5 px-8 rounded-xl transition-all duration-200 flex items-center justify-center gap-2 min-w-[150px] focus:outline-none focus:ring-2 focus:ring-indigo-500/20" :class="loading ? 'opacity-75 cursor-not-allowed shadow-none transform-none bg-indigo-500' : ''">
                            <svg x-show="loading" class="animate-spin h-4 w-4 text-white flex-shrink-0" fill="none" viewBox="0 0 24 24" style="display: none;"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="loading ? 'Validando...' : 'Continuar'" class="text-white font-black tracking-wide">Continuar</span>
                        </button>
                    </div>
                </form>
            @else
                <div class="p-6 bg-slate-50 border border-slate-200 text-slate-600 font-semibold text-center rounded-2xl">
                    No es posible agendar una cita contigo mismo. Si necesitas bloquear tu agenda, por favor usa el panel de gestión de horarios.
                </div>
            @endif
        </div>
    </div>
    <script>
        /**
         * Alterna de forma segura los contenedores y actualiza las obligaciones HTML5 del DOM.
         */
        function toggleForm(type) {
            const secNew = document.getElementById('section-new');
            const secExist = document.getElementById('section-exist');
            const btnNew = document.getElementById('btn-new');
            const btnExist = document.getElementById('btn-exist');
            const hasAcc = document.getElementById('has_account');

            // Capturar los nodos físicos para inyectar o remover la validación requerida
            const inputsNew = document.querySelectorAll('.input-new');
            const inputsExist = document.querySelectorAll('.input-exist');
            const phoneInput = document.getElementById('phone');

            if (type === 'new') {
                if(secNew) secNew.classList.remove('hidden');
                if(secExist) secExist.classList.add('hidden');
                
                if(btnNew) btnNew.className = "flex-1 py-2 text-sm font-bold rounded-xl transition bg-white shadow-sm text-blue-600";
                if(btnExist) btnExist.className = "flex-1 py-2 text-sm font-bold rounded-xl transition text-gray-500";
                if(hasAcc) hasAcc.value = 'no';

                // 🔒 BLINDAJE: Activamos obligación en Registro, apagamos en Login
                inputsNew.forEach(input => input.required = true);
                inputsExist.forEach(input => input.required = false);
                if (phoneInput) phoneInput.required = true;                
            } else {
                if(secNew) secNew.classList.add('hidden');
                if(secExist) secExist.classList.remove('hidden');
                
                if(btnExist) btnExist.className = "flex-1 py-2 text-sm font-bold rounded-xl transition bg-white shadow-sm text-blue-600";
                if(btnNew) btnNew.className = "flex-1 py-2 text-sm font-bold rounded-xl transition text-gray-500";
                if(hasAcc) hasAcc.value = 'yes';

                // 🔒 BLINDAJE: Apagamos obligación en Registro para que el Login no se trabe
                inputsNew.forEach(input => input.required = false);
                inputsExist.forEach(input => input.required = true);
                if (phoneInput) phoneInput.required = false;
            }
        }

        // Sincronizar el estado del formulario en caso de recarga por error de validación de Laravel
        document.addEventListener('DOMContentLoaded', function() {            
            const hasAcc = document.getElementById('has_account');            
            if (hasAcc && hasAcc.value === 'yes') {
                toggleForm('exist');
            } else {
                toggleForm('new');
            }
        });
    </script>
</x-guest-layout>
