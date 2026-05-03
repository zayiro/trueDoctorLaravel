<x-guest-layout>
    <div class="max-w-2xl mx-auto py-12 mt-8">
        <div class="bg-white p-8 shadow sm:rounded-lg">
            <h2 class="text-2xl font-bold mb-6">Contáctanos</h2>

            @if(session('success'))
                <div class="mb-4 font-medium text-sm text-green-600">
                    {{ session('success') }}
                </div>
            @endif

            <div x-data="{ name: '', email: '', message: '' }">
                <form action="{{ route('contact.submit') }}" method="POST" id="contactForm">
                    @csrf

                    <!-- Nombre -->
                    <div class="mt-4">
                        <x-label for="name" value="Nombre" />
                        <x-input id="name" name="name" type="text" class="block mt-1 w-full" 
                                required 
                                minlength="3" 
                                placeholder="Ej. Juan Pérez" />
                    </div>

                    <!-- Email -->
                    <div class="mt-4">
                        <x-label for="email" value="Correo Electrónico" />
                        <x-input id="email" name="email" type="email" class="block mt-1 w-full" 
                                required 
                                placeholder="juan@ejemplo.com" />
                    </div>

                    <!-- Mensaje -->
                    <div class="mt-4">
                        <x-label for="message" value="Mensaje" />
                        <textarea name="message" id="message" rows="5" 
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" 
                                required 
                                minlength="10"></textarea>
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
