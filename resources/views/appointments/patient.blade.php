<x-guest-layout>
    <div class="max-w-2xl mx-auto py-12 px-4">
        <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100">
            <h2 class="text-2xl font-black text-gray-800 mb-6">Completa tus datos</h2>

            {{-- Mensaje de Error Simple (Sesión) --}}
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
                        <!-- Campos para usuario NO logueado -->
			            <div class="bg-blue-50 p-4 rounded-2xl mb-6 text-sm text-blue-800">
                        	Parece que no tienes cuenta. La crearemos automáticamente para que gestiones tu cita.
                    	</div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nombre completo</label>
                            <input type="text" name="name" required class="w-full rounded-xl border-gray-200 focus:ring-blue-500">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                                <input type="email" name="email" placeholder="{{ old('name', '') }}" required class="w-full rounded-xl border-gray-200 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Identificación (Cédula/DNI)</label>
                                <input type="text" 
                                    name="identification" 
                                    pattern="[0-9]{7,12}" 
                                    title="Solo números (7 a 12 dígitos)"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                    value="{{ auth()->user()?->patient?->identification }}" 
                                    class="w-full rounded-xl border-gray-200 @error('identification') border-red-500 @enderror" 
                                    placeholder="{{ old('identification', '') }}" required>                                
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Teléfono móvil</label>
                            <input type="tel" 
                                name="phone" 
                                maxlength="10"
                                pattern="[0-9]{10}"
                                title="Debe tener 10 dígitos numéricos"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                value="{{ auth()->user()?->patient?->phone }}" 
                                class="w-full rounded-xl border-gray-200 @error('phone') border-red-500 @enderror" 
                                placeholder="{{ old('phone', '') }}" required>                            
                        </div>
                    @endguest

                    @auth
                        <!-- Info del usuario logueado (solo lectura o saludo) -->
                        <div class="bg-blue-50 p-4 rounded-2xl flex items-center gap-3">
                            <div class="bg-blue-600 text-white p-2 rounded-full font-bold">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <div>
                                <p class="text-sm text-blue-800 font-bold">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-blue-600">Continuarás con tu perfil actual</p>
                            </div>
                        </div>
                    @endauth

                    <!-- Siempre visible -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Motivo de la consulta</label>
                        <textarea name="notes" rows="4" required 
                            class="w-full rounded-xl border-gray-200 focus:ring-blue-500"
                            placeholder="Escribe aquí los síntomas o el motivo de tu visita...">{{ old('notes', '') }}</textarea>
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-between">
                    <a href="{{ url()->previous() }}" class="text-gray-500 font-bold hover:underline">Volver</a>
                    <button type="submit" class="bg-blue-600 text-white px-10 py-4 rounded-2xl font-black shadow-lg hover:bg-blue-700 transition">
                        Continuar al resumen
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
