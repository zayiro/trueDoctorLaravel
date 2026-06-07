<x-guest-layout>
    <div class="max-w-4xl mx-auto py-16 px-4">
        <!-- Contenedor Principal Premium -->
        <div class="bg-white dark:bg-gray-800 p-8 md:p-10 shadow-xl shadow-gray-100/50 dark:shadow-none rounded-3xl border border-gray-100 dark:border-gray-700">
            
            <!-- Cabecera de la Vista -->
            <div class="mb-8">
                <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Contáctanos</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">¿Tienes dudas o necesitas soporte técnico? Escríbenos y te responderemos lo antes posible.</p>
            </div>

            <!-- Alerta de Éxito (Estilo Flowbite) -->
            @if(session('success'))
                <div class="mb-6 flex items-start gap-3 text-sm text-emerald-800 bg-emerald-50 dark:bg-emerald-900/30 dark:text-emerald-400 p-4 rounded-2xl border border-emerald-100 dark:border-emerald-800/50">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-bold leading-relaxed">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Alerta de Errores de Validación (Estilo Flowbite) -->
            @if ($errors->any())
                <div class="mb-6 flex items-start gap-3 text-sm text-red-800 bg-red-50 dark:bg-red-900/30 dark:text-red-400 p-4 rounded-2xl border border-red-100 dark:border-red-800/50">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="flex-1">
                        <span class="font-black block mb-1">Por favor corrige los siguientes errores:</span>
                        <ul class="list-disc list-inside space-y-0.5 text-xs opacity-90">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Formulario -->
            <form action="{{ route('contact.submit') }}" method="POST" x-data="{ loading: false, messageText: '{{ old('message', '') }}', maxLength: 150 }" x-on:submit="loading = true">
                @csrf
                @honeypot

                <div class="space-y-5">
                    <!-- Nombre -->
                    <div>
                        <x-label for="name" value="Nombre Completo" class="text-xs font-black uppercase tracking-wider text-gray-700 dark:text-gray-300" />
                        <x-input id="name" name="name" type="text" 
                                class="block mt-1.5 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition duration-150 text-sm py-3" 
                                required 
                                minlength="3" 
                                value="{{ old('name') }}"
                                placeholder="Ej. Juan Pérez"
                                autocomplete="name" />
                    </div>

                    <!-- Email -->
                    <div>
                        <x-label for="email" value="Correo Electrónico" class="text-xs font-black uppercase tracking-wider text-gray-700 dark:text-gray-300" />
                        <x-input id="email" name="email" type="email" 
                                class="block mt-1.5 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition duration-150 text-sm py-3" 
                                required 
                                value="{{ old('email') }}"
                                placeholder="juan@ejemplo.com"
                                autocomplete="email" />
                    </div>

                    <!-- Mensaje -->
                    <div>
                        <x-label for="message" value="Tu Mensaje o Consulta" class="text-xs font-black uppercase tracking-wider text-gray-700 dark:text-gray-300" />
                        <textarea 
                            name="message" 
                            id="message" 
                            rows="5" 
                            x-model="messageText"
                            x-bind:maxlength="maxLength"
                            class="block mt-1.5 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition duration-150 text-sm py-3 px-4 resize-none shadow-sm" 
                            required 
                            minlength="10" 
                            placeholder="Escribe aquí tu requerimiento en detalle..."
                        ></textarea>
                        
                        <!-- Barra de Estado y Contador Dinámico -->
                        <div class="flex justify-between items-center mt-2 text-xs">
                            <span class="text-gray-400 dark:text-gray-500">
                                {{ __('Por favor, sé breve y conciso.') }}
                            </span>
                            
                            <span 
                                x-text="(maxLength - messageText.length) + ' {{ __('caracteres restantes') }}'"
                                x-bind:class="(maxLength - messageText.length) === 0 ? 'text-red-600 font-bold dark:text-red-400' : 'text-gray-400 dark:text-gray-500 font-medium'"
                            >
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Botón de Envío de Ancho Completo -->
                <div class="mt-8">
                    <button 
                        type="submit" 
                        id="submitBtn"
                        x-bind:disabled="loading"
                        class="w-full inline-flex items-center justify-center px-6 py-3.5 text-sm font-black text-white rounded-xl transition duration-200 shadow-md shadow-blue-500/10 focus:ring-4 focus:outline-none"
                        x-bind:class="loading ? 'bg-blue-700 opacity-80 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700 active:scale-[0.99] focus:ring-blue-100 dark:focus:ring-blue-900'"
                    >
                        <!-- Estado 1: Enviando -->
                        <template x-if="loading">
                            <div class="flex items-center justify-center">
                                <svg class="w-5 h-5 me-3 text-white animate-spin" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="tracking-wide">{{ __('PROCESANDO MENSAJE...') }}</span>
                            </div>
                        </template>

                        <!-- Estado 2: Texto Normal -->
                        <template x-if="!loading">
                            <div class="flex items-center justify-center">
                                <svg class="w-5 h-5 me-2.5 transition-transform group-hover:translate-x-1" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                </svg>
                                <span class="tracking-wide uppercase text-[13px] font-black">{{ __('Enviar Mensaje') }}</span>
                            </div>
                        </template>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
