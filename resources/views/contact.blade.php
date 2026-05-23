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
                <form action="{{ route('contact.submit') }}" method="POST" id="contactForm">
                    @csrf

                    <!-- Nombre -->
                    <div class="mt-4">
                        <x-label for="name" value="Nombre" />
                        <x-input id="name" name="name" type="text" class="block mt-1 w-full" 
                                required 
                                minlength="3" 
                                value="{{ old('name') }}"
                                placeholder="Ej. Juan Pérez" />
                    </div>

                    <!-- Email -->
                    <div class="mt-4">
                        <x-label for="email" value="Correo Electrónico" />
                        <x-input id="email" name="email" type="email" class="block mt-1 w-full" 
                                required 
                                value="{{ old('email') }}"
                                placeholder="juan@ejemplo.com" />
                    </div>

                    <!-- Mensaje -->
                    <div class="mt-4">
                        <x-label for="message" value="Mensaje" />
                        <textarea name="message" id="message" rows="5" 
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" 
                                required 
                                minlength="10" 
                                placeholder="Escribe aquí tu mensaje...">{{ old('message') }}</textarea>
                    </div>

                    <div class="flex items-center justify-end mt-6">
                        <x-button id="submitBtn">
                            Enviar Mensaje
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
