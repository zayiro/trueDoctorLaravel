<x-guest-layout>
    <div class="max-w-2xl mx-auto py-12 mt-8">
        <div class="bg-white p-8 shadow sm:rounded-lg">
            <h2 class="text-2xl font-bold mb-6">Contáctanos</h2>

            <!-- Alerta de Éxito -->
            @if(session('success'))
                <div class="mb-4 font-medium text-sm text-green-600 bg-green-50 p-3 rounded border border-green-200">
                    {{ session('success') }}
                </div>
            @endif

            <!-- 💡 NUEVO: Alerta de Errores de Validación de Laravel -->
            @if ($errors->any())
                <div class="mb-4 font-medium text-sm text-red-600 bg-red-50 p-3 rounded border border-red-200">
                    <ul class="mb-0 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <form action="{{ route('contact.submit') }}" method="POST" x-data="{ loading: false, messageText: '{{ old('message', '') }}', maxLength: 150 }" x-on:submit="loading = true">
                    @csrf

                    @honeypot
                    <!-- Nombre -->
                    <div class="mt-4">
                        <x-label for="name" value="Nombre" />
                        <x-input id="name" name="name" type="text" class="block mt-1 w-full" 
                                required 
                                minlength="3" 
                                value="{{ old('name') }}"
                                placeholder="Ej. Juan Pérez"
                                autocomplete="name" />
                    </div>

                    <!-- Email -->
                    <div class="mt-4">
                        <x-label for="email" value="Correo Electrónico" />
                        <x-input id="email" name="email" type="email" class="block mt-1 w-full" 
                                required 
                                value="{{ old('email') }}"
                                placeholder="juan@ejemplo.com"
                                autocomplete="email" />
                    </div>

                    <!-- Mensaje -->
                    <div class="mt-4">
                        <x-label for="message" value="Mensaje" />
                        <textarea 
                            name="message" 
                            id="message" 
                            rows="5" 
                            x-model="messageText"
                            x-bind:maxlength="maxLength"
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full dark:bg-gray-700 dark:border-gray-600 dark:text-white" 
                            required 
                            minlength="10" 
                            placeholder="Escribe aquí tu mensaje..."
                        ></textarea>
                        
                        <!-- Contador Dinámico de Caracteres -->
                        <div class="flex justify-between items-center mt-1.5 text-xs">
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ __('Por favor, sé breve y conciso.') }}
                            </span>
                            
                            <!-- Contador apuntando a messageText -->
                            <span 
                                x-text="(maxLength - messageText.length) + ' {{ __('caracteres restantes') }}'"
                                x-bind:class="(maxLength - messageText.length) === 0 ? 'text-red-600 font-semibold dark:text-red-400' : 'text-gray-500 dark:text-gray-400'"
                            >
                            </span>
                        </div>
                    </div>

                    
                    <div class="flex items-center justify-end mt-6">
                        <button 
                            type="submit" 
                            id="submitBtn"
                            x-bind:disabled="loading"
                            x-bind:class="loading ? 'opacity-75 cursor-not-allowed inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg' : 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:ring-4 focus:ring-blue-300'"
                        >
                            <!-- Estado 1: Enviando (Se muestra solo cuando loading es TRUE) -->
                            <template x-if="loading">
                                <div class="flex items-center justify-center">
                                    <!-- Spinner Animado Nativo de Flowbite -->
                                    <svg class="w-5 h-5 me-3 text-white animate-spin" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>{{ __('Enviando mensaje...') }}</span>
                                </div>
                            </template>

                            <!-- Estado 2: Texto Normal (Se muestra solo cuando loading es FALSE) -->
                            <template x-if="!loading">
                                <div class="flex items-center justify-center">
                                    <!-- Heroicon: PaperAirplane (SVG Nativo) opcional para mejorar la UI -->
                                    <svg class="w-5 h-5 me-2 text-white" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                    </svg>
                                    <span>{{ __('Enviar Mensaje') }}</span>
                                </div>
                            </template>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
