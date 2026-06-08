<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <x-validation-errors class="mb-4" />

        @session('status')
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ $value }}
            </div>
        @endsession

        @if (session('success'))
            <div id="alert-success" class="mb-6 flex items-center p-4 text-emerald-800 bg-emerald-50 border border-emerald-100 rounded-2xl shadow-sm animate-fade-in-down" role="alert">
                <svg class="flex-shrink-0 w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <div class="ms-3 text-sm font-bold">
                    {{ session('success') }}
                </div>
                <button type="button" class="ms-auto text-emerald-500 hover:bg-emerald-100 rounded-lg p-1.5 transition" onclick="document.getElementById('alert-success').remove()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        @endif

        <!-- Ubicación Ideal: Mensaje de invitación Multiperfil -->
        <div class="text-center mb-6 border-b border-gray-100 pb-4">
            <h2 class="text-lg font-bold text-gray-800">Conectando salud en tiempo real</h2>
            <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto leading-relaxed">
                Inicia sesión para gestionar y administrar la información en <span class="font-semibold">opendoctor.online</span>
            </p>
            <h2 class="text-lg font-bold text-gray-800">
                <span class="bg-success-soft text-fg-success-strong text-xs font-medium px-1.5 py-0.5 rounded">Pacientes</span>
                <span class="bg-success-soft text-fg-success-strong text-xs font-medium px-1.5 py-0.5 rounded">Especialistas</span>
                <span class="bg-success-soft text-fg-success-strong text-xs font-medium px-1.5 py-0.5 rounded">Clínicas</span>
            </h2>
        </div>

        <form method="POST" action="{{ route('login') }}" x-data="{ showPass: false, loading: false }" @submit="loading = true">
            @csrf

            <div>
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="off" />
            </div>

            <!-- CAMPO PASSWORD CON OJO INTERACTIVO -->
            <div class="mt-4">
                <x-label for="password" value="{{ __('Password') }}" />
                <div class="relative mt-1 rounded-md shadow-sm">
                    <x-input 
                        id="password" 
                        class="block mt-1 w-full pr-10" 
                        ::type="showPass ? 'text' : 'password'" 
                        name="password" 
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

            <div class="block mt-4">
                <label for="remember_me" class="flex items-center">
                    <x-checkbox id="remember_me" name="remember" />
                    <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                @if (Route::has('password.request'))
                    <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif

                <x-button class="ms-4 inline-flex items-center" ::disabled="loading">
                    <!-- Spinner Animado de Tailwind CSS (se muestra solo al cargar) -->
                    <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" x-cloak>
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>

                    <!-- Texto que cambia dinámicamente -->
                    <span x-text="loading ? 'Validando...' : '{{ __('Log in') }}'"></span>
                </x-button>

            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>
