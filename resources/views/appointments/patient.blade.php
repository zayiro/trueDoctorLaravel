<x-guest-layout>
    <div class="max-w-2xl mx-auto py-12 px-4">
        <div class="bg-white rounded-3xl shadow-xl p-8 mt-5 border border-gray-100">
            <h2 class="text-2xl font-black text-gray-800 mb-6">Completa tus datos</h2>

            @if (session('error'))
                <div class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-6 rounded-r-2xl shadow-sm flex items-center">
                    <svg class="w-5 h-5 text-amber-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 102 0V6a1 1 0 00-1-1z"/>
                    </svg>
                    <span class="text-sm font-bold text-amber-800">{{ session('error') }}</span>
                </div>
            @endif

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

                        <input type="hidden" name="has_account" id="has_account" value="no">

                        {{-- SECCIÓN: REGISTRO (NUEVO) --}}
                        <div id="section-new" class="space-y-5">
                            <div class="bg-blue-50 p-4 rounded-2xl text-sm text-blue-800">
                                Crea tu perfil para gestionar esta y futuras citas.
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Nombre completo</label>
                                <input type="text" name="name" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 input-new">
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="email" class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                                    <input type="email" name="email" id="email" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 input-new">
                                </div>
                                <div>
                                    <label for="identification" class="block text-sm font-bold text-gray-700 mb-1">Identificación</label>
                                    <input type="text" name="identification" id="identification" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="w-full rounded-xl border-gray-200 input-new">                                
                                </div>
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-bold text-gray-700 mb-1">Teléfono móvil</label>
                                <input type="tel" name="phone" id="phone" maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="w-full rounded-xl border-gray-200 input-new">                            
                            </div>
                        </div>

                        {{-- SECCIÓN: LOGIN (EXISTENTE) --}}
                        <div id="section-exist" class="space-y-5 hidden">
                            <div class="bg-amber-50 p-4 rounded-2xl text-sm text-amber-800">
                                Ingresa tus credenciales para continuar.
                            </div>
                            <div>
                                <label for="login_email" class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                                <input type="email" name="login_email" id="login_email" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 input-exist">
                            </div>
                            <div>
                                <label for="login_password" class="block text-sm font-bold text-gray-700 mb-1">Contraseña</label>
                                <input type="password" name="login_password" id="login_password" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 input-exist">
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
                        <textarea name="notes" id="notes" rows="4" required 
                            class="w-full rounded-xl border-gray-200 focus:ring-blue-500"
                            placeholder="Describe brevemente tus síntomas...">{{ old('notes', '') }}</textarea>
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-between">
                    <a href="{{ url()->previous() }}" class="text-gray-500 font-bold hover:underline">Volver</a>
                    <button type="submit" class="bg-blue-600 text-white px-10 py-4 rounded-2xl font-black shadow-lg hover:bg-blue-700 transition">
                        Continuar
                    </button>
                </div>
            </form>
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
    </script>
</x-guest-layout>
