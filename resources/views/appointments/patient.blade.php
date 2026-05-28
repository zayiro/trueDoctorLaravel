<x-guest-layout>
    <div class="max-w-4xl mx-auto py-12 px-4 mt-6">
        @if(session('error'))
            <div class="flex items-center p-4 mb-4 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm" role="alert">
                <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://w3.org" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <div class="ms-3 text-sm font-bold">
                    {{ session('error') }}
                </div>
            </div>
        @endif
        
        @if ($errors->any())
            <div class="flex p-4 mb-4 text-amber-800 rounded-2xl bg-amber-50 border border-amber-100 shadow-sm" role="alert">
                <svg class="flex-shrink-0 w-4 h-4 mt-0.5" aria-hidden="true" xmlns="http://w3.org" fill="currentColor" viewBox="0 0 20 20">
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
                <form action="{{ route('appointments.process-patient') }}" method="POST">
                    @csrf
                    
                    <div class="space-y-5">
                        @guest
                            {{-- Selector de tipo de usuario --}}
                            <div class="flex p-1 bg-gray-100 rounded-2xl mb-6">
                                <button type="button" id="btn-new" onclick="toggleForm('new')" class="flex-1 py-2 text-sm font-bold rounded-xl transition bg-white shadow-sm text-blue-600">
                                    Soy nuevo
                                </button>
                                <button type="button" id="btn-exist" onclick="toggleForm('exist')" class="flex-1 py-2 text-sm font-bold rounded-xl transition text-gray-500">
                                    Ya tengo cuenta
                                </button>
                            </div>

                            <input type="hidden" name="has_account" id="has_account" value="{{ old('has_account', 'no') }}">

                            {{-- SECCIÓN: REGISTRO (NUEVO) --}}
                            <div id="section-new" class="space-y-5">                                
                                <div class="bg-emerald-50/60 border border-emerald-100 rounded-3xl p-5 flex gap-4 items-start mb-6">
                                    <div class="bg-emerald-600 text-white p-2.5 rounded-xl shadow-md shadow-emerald-100 flex-shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <!-- 💡 MENSAJE BASADO EN CONFIDENCIALIDAD -->
                                        <h4 class="font-black text-slate-800 text-sm uppercase tracking-wide">Tu seguridad médica es nuestra prioridad</h4>
                                        <p class="text-sm text-slate-500 mt-1 leading-relaxed font-medium">
                                            Para proteger tu historial, generar las órdenes de consulta de forma legal y darte acceso seguro a tu enlace de <strong class="text-emerald-600">Telemedicina</strong>, requerimos crear una clave de acceso única para ti. Tus datos médicos viajan totalmente encriptados.
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <label for="name" class="block text-sm font-bold text-gray-700 mb-1">Nombre completo</label>
                                    <input type="text" name="name" id="name" value="{{ old('name') }}" autocomplete="name" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 input-new">
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="email" class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                                        <input type="email" name="email" id="email" value="{{ old('email') }}" autocomplete="email" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 input-new">
                                    </div>
                                    <div>
                                        <label for="identification" class="block text-sm font-bold text-gray-700 mb-1">Identificación</label>
                                        <input type="text" name="identification" id="identification" value="{{ old('identification') }}" autocomplete="identification" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="w-full rounded-xl border-gray-200 input-new">                                
                                    </div>
                                </div>
                                <div>
                                    <label for="phone" class="block text-sm font-bold text-gray-700 mb-1">Teléfono móvil</label>
                                    <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" autocomplete="tel" maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="w-full rounded-xl border-gray-200 input-new">                            
                                </div>
                            </div>

                            {{-- SECCIÓN: LOGIN (EXISTENTE) --}}
                            <div id="section-exist" class="space-y-5 hidden">
                                <div class="bg-amber-50 p-4 rounded-2xl text-sm text-amber-800">
                                    Ingresa tus credenciales para continuar.
                                </div>
                                <div>
                                    <label for="login_email" class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                                    <input type="email" name="login_email" id="login_email" value="{{ old('login_email') }}" autocomplete="email" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 input-exist">
                                </div>
                                <div x-data="{ showPass: false }">
                                    <label for="login_password" class="block text-sm font-bold text-gray-700 mb-1">Contraseña</label>
                                    <div class="relative mt-1 rounded-md shadow-sm">
                                        <input 
                                            id="login_password" 
                                            name="login_password" 
                                            class="block w-full pr-10 rounded-xl border-gray-200 focus:ring-blue-500 input-exist" 
                                            :type="showPass ? 'text' : 'password'"                                             
                                            required 
                                            autocomplete="current-password" 
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
                            </div>
                        @endguest

                        @auth
                            <div class="bg-blue-50 p-4 rounded-2xl flex items-center gap-3">
                                <div class="bg-blue-600 text-white p-2 rounded-full font-bold w-10 h-10 flex items-center justify-center">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="text-sm text-blue-800 font-bold">{{ ucfirst(auth()->user()->name) }}</p>
                                    <p class="text-xs text-blue-600">Perfil activo para reservación</p>
                                </div>
                            </div>
                        @endauth

                        <div>
                            <label for="notes" class="block text-sm font-bold text-gray-700 mb-1">Motivo de la consulta</label>
                            <textarea name="notes" id="notes" autocomplete="notes" rows="4" required 
                                class="w-full rounded-xl border-gray-200 focus:ring-blue-500"
                                placeholder="Describe brevemente tus síntomas...">{{ old('notes', '') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-8 flex items-center justify-between">
                        <a href="{{ url()->previous() }}" class="text-gray-500 font-bold hover:underline">Volver</a>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white py-2 px-4 rounded shadow-lg">
                            Continuar
                        </button>
                    </div>
                </form>
            @else
                <div>No es posible agendar una cita contigo mismo. Si necesitas bloquear tu agenda, por favor usa el panel de gestión de horarios</div>
            @endif
        </div>
    </div>

    <script>
        function toggleForm(type) {
            const secNew = document.getElementById('section-new');
            const secExist = document.getElementById('section-exist');
            const btnNew = document.getElementById('btn-new');
            const btnExist = document.getElementById('btn-exist');
            const hasAcc = document.getElementById('has_account');

            if (type === 'new') {
                secNew.classList.remove('hidden');
                secExist.classList.add('hidden');
                btnNew.classList.add('bg-white', 'shadow-sm', 'text-blue-600');
                btnNew.classList.remove('text-gray-500');
                btnExist.classList.remove('bg-white', 'shadow-sm', 'text-blue-600');
                btnExist.classList.add('text-gray-500');
                hasAcc.value = 'no';
            } else {
                secNew.classList.add('hidden');
                secExist.classList.remove('hidden');
                btnExist.classList.add('bg-white', 'shadow-sm', 'text-blue-600');
                btnExist.classList.remove('text-gray-500');
                btnNew.classList.remove('bg-white', 'shadow-sm', 'text-blue-600');
                btnNew.classList.add('text-gray-500');
                hasAcc.value = 'yes';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {            
            const hasAccountOld = {{ old('has_account') ? 'true' : 'false' }};
            if (hasAccountOld) {
                const hasAcc = document.getElementById('has_account');            
                toggleForm(hasAcc == 'yes' ? 'new' : 'exist');
            }
        });
    </script>
</x-guest-layout>
