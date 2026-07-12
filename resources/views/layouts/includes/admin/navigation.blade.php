<nav class="fixed top-0 z-50 w-full bg-white border-b border-default">
    <div class="px-3 py-3 lg:px-5 lg:pl-3">
        <div class="flex items-center justify-between">
        <div class="flex items-center justify-start rtl:justify-end">
            <button data-drawer-target="top-bar-sidebar" data-drawer-toggle="top-bar-sidebar" aria-controls="top-bar-sidebar" type="button" class="sm:hidden text-heading bg-transparent box-border border border-transparent hover:bg-neutral-secondary-medium focus:ring-4 focus:ring-neutral-tertiary font-medium leading-5 rounded-base text-sm p-2 focus:outline-none">
                <span class="sr-only">Open sidebar</span>
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h10"/>
                </svg>
            </button>
            <a href="https://opendoctor.online" class="flex ms-2 md:me-24" alt="OpenDoctorOnline" title="OpenDoctorOnline">
                <img src="{{ asset('images/logoOpenDoctor.jpg') }}" class="size-8 rounded-full object-cover" alt="OpenDoctor Reservaciones Médicas" />
                <span class="self-center text-xl text-heading font-semibold whitespace-nowrap">
                    <div class="flex items-center gap-2 ml-2">
                        <span class="text-lg font-black text-slate-900 tracking-tight">
                            Open<span class="text-indigo-600">Doctor</span><span class="text-emerald-500">Online</span></span>
                        </span>
                    </div>
                </span>
            </a>
        </div>
        <div class="hidden w-full md:block md:w-auto" id="navbar-default">
            <ul class="font-medium flex flex-col p-4 md:p-0 mt-4 border border-default rounded-base bg-neutral-secondary-soft md:flex-row md:space-x-8 rtl:space-x-reverse md:mt-0 md:border-0 md:bg-neutral-primary">
                <li>
                    @if(auth()->user()->role === 'doctor' && $showContextSelector)
                    <!-- SELECTOR DE CONTEXTO CON ICONOS SVG NATIVOS -->
                    <div x-data="{ open: false }" class="relative inline-block text-left">
                        <div>
                            <button @click="open = !open" 
                                    type="button" 
                                    class="inline-flex items-center justify-between w-full rounded-md border border-gray-300 shadow-sm px-3 py-1.5 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition ease-merge duration-150 gap-2"
                                    id="context-menu-button">
                                
                                <!-- AVATAR / ICONO DEL ENTORNO ACTIVO BLINDADO -->
                                <div class="flex-shrink-0">
                                    {{-- Se agregó ?? null para prevenir el error de llave indefinida --}}
                                    @if($currentContext['photo'] ?? null)
                                        <!-- Foto Real (Médico o Clínica) -->
                                        <img class="h-8 w-8 rounded-full object-cover ring-2 ring-indigo-100" 
                                            src="{{ Storage::url($currentContext['photo']) }}" 
                                            alt="">
                                    @else
                                        @if(($currentContext['type'] ?? 'particular') === 'particular')
                                            <!-- SVG Icon: Usuario (Consultorio Particular) -->
                                            <div class="h-8 w-8 rounded-full flex items-center justify-center bg-indigo-50 text-indigo-600 ring-2 ring-indigo-100">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                                </svg>
                                            </div>
                                        @else
                                            <!-- SVG Icon: Edificio (Clínica) -->
                                            <div class="h-8 w-8 rounded-full flex items-center justify-center bg-emerald-50 text-emerald-600 ring-2 ring-emerald-100">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" />
                                                </svg>
                                            </div>
                                        @endif
                                    @endif
                                </div>


                                <div class="text-left hidden sm:block">
                                    <span class="block text-[10px] uppercase font-bold text-gray-400 tracking-wider leading-none">Espacio Activo</span>
                                    <span class="block text-xs font-semibold text-gray-900 mt-0.5 truncate max-w-[140px]">{{ $currentContext['name'] }}</span>
                                </div>

                                <!-- Flecha Indicadora -->
                                <svg class="h-4 w-4 text-gray-400 transform transition-transform duration-200" 
                                    :class="open ? 'rotate-180' : 'rotate-0'" 
                                    xmlns="http://w3.org" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <!-- Dropdown Flotante -->
                        <div x-show="open" 
                            @click.away="open = false" 
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-64 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50 origin-top-right divide-y divide-gray-100"
                            style="display: none;">
                            
                            <div class="px-4 py-2">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Seleccionar Entorno</p>
                            </div>

                            <form action="{{ route('doctor.context.switch') }}" method="POST" class="p-1 m-0">
                                @csrf
                                
                                <!-- Opción: Consultorio Particular -->
                                <button type="submit" name="context_id" value="particular" 
                                    class="w-full text-left px-3 py-2 text-sm rounded-md flex items-center justify-between transition-colors duration-150 {{ $currentContext['type'] === 'particular' ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <span class="flex items-center truncate">
                                        @if(auth()->user()->profile_photo_path)
                                            <img class="h-6 w-6 rounded-full object-cover mr-2" src="{{ Storage::url(auth()->user()->profile_photo_path) }}" alt="">
                                        @else
                                            <!-- Icono SVG Usuario Pequeño -->
                                            <svg class="h-5 w-5 text-indigo-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                            </svg>
                                        @endif
                                        <span class="truncate">Consultorio Particular</span>
                                    </span>
                                    @if($currentContext['type'] === 'particular') 
                                        <svg class="h-4 w-4 text-indigo-600" xmlns="http://w3.org" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </button>

                                <!-- Listado de Clínicas Vinculadas -->
                                <div class="px-3 py-1.5 mt-1">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Clínicas Vinculadas</p>
                                </div>

                                @foreach($myClinicsList as $cl)
                                    <button type="submit" name="context_id" value="{{ $cl->id }}" 
                                        class="w-full text-left px-3 py-2 text-sm rounded-md flex items-center justify-between transition-colors duration-150 {{ $currentContext['id'] == $cl->id ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                                        <span class="flex items-center truncate">
                                            @if($cl->clinic_photo)
                                                <img class="h-6 w-6 rounded-full object-cover mr-2" src="{{ Storage::url($cl->clinic_photo) }}" alt="">
                                            @else
                                                <!-- Icono SVG Edificio Pequeño -->
                                                <svg class="h-5 w-5 text-emerald-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" />
                                                </svg>
                                            @endif
                                            <span class="truncate">{{ $cl->name }}</span>
                                        </span>
                                        @if($currentContext['id'] == $cl->id) 
                                            <svg class="h-4 w-4 text-indigo-600" xmlns="http://w3.org" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                            </svg>
                                        @endif
                                    </button>
                                @endforeach
                            </form>
                        </div>
                    </div>
                    @endif
                </li>
            </ul>
        </div>
        <div class="flex items-center">
            <div class="ms-3 relative">                
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                            <button class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                                <img class="size-8 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                            </button>
                        @else
                            <span class="inline-flex rounded-md">
                                <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 transition ease-in-out duration-150">
                                    {{ Auth::user()->name }}

                                    <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>
                            </span>
                        @endif
                    </x-slot>

                    <x-slot name="content">
                        <!-- Account Management -->
                        <div class="block px-4 py-2 text-xs text-gray-400">
                            {{ __('Manage Account') }}
                        </div>

                        <x-dropdown-link href="{{ route('administrator.dashboard') }}">
                            {{ __('Dashboard') }}
                        </x-dropdown-link>

                        <x-dropdown-link href="{{ route('profile.show') }}">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                            <x-dropdown-link href="{{ route('api-tokens.index') }}">
                                {{ __('API Tokens') }}
                            </x-dropdown-link>
                        @endif

                        <div class="border-t border-gray-200"></div>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}" x-data>
                            @csrf
                            <x-dropdown-link href="{{ route('logout') }}"
                                        @click.prevent="$root.submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </div>
</nav>