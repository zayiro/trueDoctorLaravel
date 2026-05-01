<x-guest-layout>    
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <x-validation-errors class="mb-4" />

        <div>
            <h2 class="text-2xl font-black text-gray-800 mb-6">Registro de Clínica</h2>            
            <form action="{{ route('clinic.register.store') }}" method="POST" class="space-y-4">
                @csrf
                
                <div>
                    <label class="block font-medium text-sm text-slate-500 ml-1">Nombre de la Clínica</label>
                    <x-input id="clinic_name" class="block mt-1 w-full" type="text" name="clinic_name" :value="old('clinic_name')" required autofocus autocomplete="clinic_name" />
                </div>

                <div>
                    <label class="block font-medium text-sm text-slate-500 ml-1 mb-1">NIT / Identificación Fiscal</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <x-input id="nit" class="block mt-1 w-full pl-10" type="text" name="nit" :value="old('nit')" required autofocus autocomplete="nit" />    
                    </div>                    
                </div>

                <div>
                    <label class="block font-medium text-sm text-slate-500 ml-1">Nombre del Gerente</label>                    
                    <x-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                </div>

                <div>
                    <label class="block font-medium text-sm text-slate-500 ml-1">Email Corporativo</label>                    
                    <x-input id="email" class="block mt-1 w-full" type="text" name="email" :value="old('email')" required autofocus autocomplete="email" />
                </div>
                
                <div>
                    <label class="block font-medium text-sm text-slate-500 ml-1">Teléfono de Contacto</label>
                    <div class="relative mt-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                        </div>
                        <x-input id="phone" class="block mt-1 w-full pl-10" type="tel" name="phone" :value="old('phone')" required autofocus autocomplete="phone" />
                    </div>
                </div>
                
                <div>
                    <x-label for="password" value="{{ __('Password') }}" class="font-bold text-slate-500 ml-1" />
                    <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                </div>

                <div>
                    <x-label for="password_confirmation" value="{{ __('Confirm Password') }}" class="font-bold text-slate-500 ml-1" />
                    <x-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />            
                </div>

                <div class="flex items-center justify-end mt-4 mb-4">
                    <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                        {{ __('Already registered?') }}
                    </a>

                    <x-button class="ms-4">
                        {{ __('Register') }}
                    </x-button>
                </div>
            </form>
        </div>

    </x-authentication-card>
</x-guest-layout>
