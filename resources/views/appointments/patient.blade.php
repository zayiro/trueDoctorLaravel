<x-guest-layout>
    <div class="max-w-2xl mx-auto py-12 px-4">
        <div class="bg-white rounded-3xl shadow-xl p-8 mt-5 mb-8 border border-gray-100">
            <h2 class="text-2xl font-black text-gray-800 mb-6">Completa tus datos</h2>

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
                                <div class="bg-blue-50 p-4 rounded-2xl text-sm text-blue-800">
                                    Crea tu perfil para gestionar esta y futuras citas.
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
                                <div>
                                    <label for="login_password" class="block text-sm font-bold text-gray-700 mb-1">Contraseña</label>
                                    <input type="password" name="login_password" id="login_password" autocomplete="current-password" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 input-exist">
                                </div>
                            </div>
                        @endguest

                        @auth
                            <div class="bg-blue-50 p-4 rounded-2xl flex items-center gap-3">
                                <div class="bg-blue-600 text-white p-2 rounded-full font-bold w-10 h-10 flex items-center justify-center">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="text-sm text-blue-800 font-bold">{{ auth()->user()->name }}</p>
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
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
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
