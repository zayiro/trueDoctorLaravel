@php
    $user = auth()->user();

    $links = [
        [
            'header' => 'Administración Global',        
        ],
        [
            'name' => 'Dashboard',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z"/></svg>',
            'href' => route('admin.dashboard'),
            'active' => request()->routeIs('admin.dashboard'),
        ],
        [
            'name' => 'Notificaciones',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>',
            'href' => route('notifications.index'),
            'active' => request()->routeIs('notifications.index'),                        
            'badge' => auth()->user()?->unreadNotifications()->count() ?: null,
            'visible' => true,
        ],
        [
            'name' => 'Directorio de Usuarios',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766A4.125 4.125 0 0 1 8.624 16.5c1.472 0 2.812.493 3.884 1.32M15 11.25a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6.5 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>',
            'href' => route('administrator.users.index'),
            'active' => request()->routeIs('administrator.users.*'),
        ],
        [
            'name' => 'Aprobaciones',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"/></svg>',
            'href' => route('administrator.validation.index'),
            'active' => request()->routeIs('administrator.validation.*'),
        ],
        [
            'name' => 'Limpiar caché',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0 0 21 17.25l-5.83-5.83m-3.75 3.75c-.139-.139-.21-.318-.21-.497v-2.25c0-.179.071-.358.21-.497l3.75-3.75c.139-.139.318-.21.497-.21h2.25c.179 0 .358.071.497.21l3.75 3.75c.139.139.21.318.21.497v2.25c0 .179-.071.358-.21.497l-3.75 3.75a.749.749 0 0 1-1.06 0l-3.75-3.75ZM3 16.5V19.5A1.5 1.5 0 0 0 4.5 21h3m-6-4.5V4.5A1.5 1.5 0 0 1 3 3h18a1.5 1.5 0 0 1 1.5 1.5V9M3 16.5h4.5m4.5-12h3M4.5 7.5h15"/></svg>',
            'href' => route('administrator.clearcache.index'),
            'active' => request()->routeIs('administrator.clearcache.*'),
        ],
        [
            'name' => 'Generar sitemap',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 0 0 4.5 4.5H18a3.75 3.75 0 0 0 1.332-7.257 3 3 0 0 0-3.758-3.848 5.25 5.25 0 0 0-10.233 2.33A4.502 4.502 0 0 0 2.25 15Z"/></svg>',
            'href' => route('seo.sitemap'),
            'active' => request()->routeIs('seo.sitemap'),
        ],
        [
            'name' => 'Examenes Médicos',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 0 0 4.5 4.5H18a3.75 3.75 0 0 0 1.332-7.257 3 3 0 0 0-3.758-3.848 5.25 5.25 0 0 0-10.233 2.33A4.502 4.502 0 0 0 2.25 15Z"/></svg>',
            'href' => route('administrator.exams.index'),
            'active' => request()->routeIs('administrator.exams.index'),
        ],
    ];

    $userRole = match ($user->role) {
        'admin' => 'Administrador',
        'doctor' => 'Especialista',
        'clinic' => 'Clínica',
        'patient' => 'Paciente',
    };
@endphp

<aside id="top-bar-sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen pt-20 transition-transform -translate-x-full bg-slate-50 border-r border-slate-200 sm:translate-x-0 dark:bg-slate-900 dark:border-slate-800" aria-label="Sidebar">
    <!-- CORRECCIÓN CRÍTICA: Contenedor en bloque puro con scroll vertical aislado e inalterable -->
    <div class="h-full px-4 pb-12 overflow-hidden bg-slate-50 dark:bg-slate-900 block">
        
        <!-- Identificación del Socio Comercial (Doctor/Clínica) --> 
        @if($user)
            <div class="pb-4 mb-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center space-x-3 truncate">
                    <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-xs uppercase shadow-sm shrink-0">
                        {{ substr($user->name, 0, 2) }}
                    </div>
                    <div class="truncate">
                        <p class="text-xs font-black text-slate-800 dark:text-slate-200 truncate max-w-[145px]">
                            {{ $user->name }}
                        </p>
                        <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider mt-0.5">
                            {{ $userRole }}
                        </p>
                        <p class="text-[10px] font-bold text-indigo-400 tracking-wider mt-0.5">
                            {{ $user->email }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Identidad del Sistema Central (Se mantiene arriba fijo) -->
        <div class="flex items-center ps-2 mb-6 survival-header-2">
            <span class="self-center text-xl font-bold tracking-tight text-slate-800 dark:text-white">
                open<span class="text-blue-600 dark:text-blue-400">doctor</span>
            </span>
            <span class="bg-indigo-600 text-white text-[10px] font-extrabold px-2 py-0.5 rounded ml-2 shadow-sm uppercase tracking-wider">
                ADMIN
            </span>
        </div>

        <!-- Listado de Enlaces Core: AQUÍ SE APLICA EL SCROLL INDEPENDIENTE -->
        <!-- Se añade 'flex-1', 'overflow-y-auto' y clases para ocultar o estilizar la barra si lo deseas -->
        <ul class="space-y-1.5 font-medium flex-1 overflow-y-auto pr-1 scrollbar-thin" style="height: 60dvh; overflow-y: auto;">
            @foreach ($links as $link)
                <li>
                    @isset($link['header'])
                        <div class="px-2 pt-4 pb-1 text-[11px] font-bold tracking-wider text-slate-400 uppercase dark:text-slate-500">
                            {{ $link['header'] }}
                        </div>
                    @else
                        @isset($link['submenu'])
                            <div x-data="{ open: {{ $link['active'] ? 'true' : 'false' }} }">
                                <button @click="open = !open" type="button" class="flex items-center justify-between w-full p-2.5 text-sm font-semibold rounded-lg transition-all duration-150 group {{ $link['active'] ? 'bg-blue-50 text-blue-700 border-l-4 border-blue-600 dark:bg-slate-800 dark:text-blue-400' : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                                    <div class="flex items-center">
                                        <span class="{{ $link['active'] ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 group-hover:text-blue-600' }} transition-colors duration-150">
                                            {!! $link['icon'] !!}
                                        </span>
                                        <span class="ms-3 text-left">{{ $link['name'] }}</span>
                                    </div>
                                    <svg :class="open ? 'rotate-180 text-blue-600' : ''" class="w-4 h-4 text-slate-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <ul x-show="open" x-collapse class="py-1.5 space-y-1 pl-6">
                                    @foreach ($link['submenu'] as $item)
                                        <li>
                                            <a href="{{ $item['href'] }}" class="block p-2 text-sm font-medium rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition-colors duration-150">
                                                {{ $item['name'] }}
                                            </a>
                                        </li>                            
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <a href="{{ $link['href'] }}" class="flex items-center p-2.5 text-sm font-medium rounded-lg transition-all duration-150 group {{ $link['active'] ? 'bg-blue-50 text-blue-700 font-semibold border-l-4 border-blue-600 dark:bg-slate-800 dark:text-blue-400 dark:border-blue-500' : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                                <span class="{{ $link['active'] ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 group-hover:text-blue-600' }} transition-colors duration-150">
                                    {!! $link['icon'] !!}
                                </span>
                                <span class="ms-3">{{ $link['name'] }}</span>

                                <!-- Burbuja de notificación condicional -->
                                @if(isset($link['badge']) && $link['badge'] > 0)
                                    <span class="inline-flex items-center justify-center h-5 min-w-[20px] px-1.5 text-[11px] font-bold leading-none text-white bg-red-500 rounded-full">
                                        {{ $link['badge'] }}
                                    </span>
                                @endif
                            </a>
                        @endisset
                    @endisset
                </li>            
            @endforeach
        </ul>        
    </div>
</aside>
