@php
    $user = auth()->user();

    $links = [
        [
            'header' => 'Administrar página',        
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
            'name' => 'Control de Citas',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A4.49 4.49 0 0 1 8.323 14.5m8.034 4.22a9.09 9.09 0 0 0 1.94-2.22c.19-.344.257-.74.257-1.136a4.49 4.49 0 0 0-1.66-3.355m-.104 6.126-2.315-2.222M10.5 7.5a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm6.75 2.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm-12.75 9c0-1.336 1.054-2.424 2.38-2.44a11.958 11.958 0 0 1 9.74 0c1.326.015 2.38 1.104 2.38 2.441 0 .641-.12 1.253-.337 1.817H4.087C3.87 19.818 3.75 19.206 3.75 18.563Z"/></svg>',
            'href' => route('partner.clinic.appointments.index'),
            'active' => request()->routeIs('partner.clinic.appointments.*'),
        ],
        [
            'name'    => 'Mensajes',
            'icon'    => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>',
            'href'    => route('chat.index'),
            'active'  => request()->routeIs('chat.*'),
            'visible' => true,
            'badge'   => \App\Models\Conversation::where(function($q) {
                                $user = auth()->user();
                                match($user?->role) {
                                    'doctor'  => $q->where('doctor_id', $user->doctor?->id),
                                    'clinic'  => $q->where('clinic_id', $user->clinic?->id),
                                    'patient' => $q->where('patient_id', $user->patient?->id),
                                    default   => $q->whereRaw('1=0'),
                                };
                            })
                            ->whereHas('messages', fn($q) => $q->where('sender_id', '!=', auth()->id())->where('is_read', false))
                            ->count() ?: null,
        ],
        [
            'name'    => 'Galería',
            'icon'    => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>',
            'href'    => route('partner.gallery.index'),
            'active'  => request()->routeIs('partner.gallery.*'),
            'visible' => in_array(Auth::user()->role, ['doctor', 'clinic']),
        ],
        [
            'name' => 'Datos del perfil',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path></svg>',
            'href' => route('partner.clinic.profile.edit'),
            'active' => request()->routeIs('partner.clinic.profile.*'),
        ],
        [
            'name' => 'Nómina de Especialistas',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A4.49 4.49 0 0 1 8.323 14.5m8.034 4.22a9.09 9.09 0 0 0 1.94-2.22c.19-.344.257-.74.257-1.136a4.49 4.49 0 0 0-1.66-3.355m-.104 6.126-2.315-2.222M10.5 7.5a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm6.75 2.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm-12.75 9c0-1.336 1.054-2.424 2.38-2.44a11.958 11.958 0 0 1 9.74 0c1.326.015 2.38 1.104 2.38 2.441 0 .641-.12 1.253-.337 1.817H4.087C3.87 19.818 3.75 19.206 3.75 18.563Z"/></svg>',
            'href' => route('partner.clinic.doctors.index'),
            'active' => request()->routeIs('partner.clinic.doctors.*'),
        ],        
        // 👇 INYECCIÓN PREMIUM: Módulo de Sedes Geográficas de la Clínica
        [
            'name' => 'Sedes Institucionales',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>',
            'href' => route('partner.clinic.addresses.index'),
            'active' => request()->routeIs('partner.addresses.*'),
        ], 
         // 👇 NUEVA INYECCIÓN: Enlace directo al Catálogo de Servicios y Tarifarios de OpenDoctor
        [
            'name' => 'Catálogo de Servicios',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581a2.25 2.25 0 0 0 3.182 0l4.318-4.318a2.25 2.25 0 0 0 0-3.182L11.16 3.659A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 7.5h.008v.008H6V7.5Z" /></svg>',
            'href' => route('partner.clinic.services.index'),
            'active' => request()->routeIs('partner.clinic.services.*'),
        ], 
        // 👇 NUEVA INYECCIÓN: Enlace directo al módulo de Grillas Horarias y Agendas de OpenDoctor
        [
            'name' => 'Horarios de Atención',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>',
            'href' => route('partner.clinic.schedules.index'),
            'active' => request()->routeIs('partner.clinic.schedules.*'),
        ],
    ];

    // 🔒 Tu corrección del bug usando operador seguro
    $validation = $user->clinic?->validation_status ?? 'missing';

    if ($validation != 'approved') {
        $links = [
            [
                'header' => 'Validando documentos...',        
            ],
        ];
    }

    $userRole = match ($user->role) {
        'admin' => 'Administrador',
        'doctor' => 'Especialista',
        'clinic' => 'Clínica',
        'patient' => 'Paciente',
    };
@endphp

<aside id="top-bar-sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full border-r bg-slate-50 border-slate-200 sm:translate-x-0 dark:bg-slate-900 dark:border-slate-800" aria-label="Sidebar">
    <!-- SE ELIMINÓ 'overflow-y-auto' DE AQUÍ: Mantiene fijos el usuario y la marca de la clínica -->
    <div class="h-full px-4 pb-12 overflow-hidden bg-slate-50 dark:bg-slate-900 block">
        
        <!-- Pie del Sidebar: Datos de la Clínica Activa (Fijo arriba) -->
        @if($user)
            <div class="pt-4 pb-4 mb-2 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center space-x-3 truncate">
                    <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-xs uppercase shadow-sm">
                        {{ substr($user->name, 0, 2) }}
                    </div>
                    <div class="truncate">
                        <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate max-w-[140px]">
                            {{ $user->name }}
                        </p>
                        <p class="text-[10px] uppercase font-bold text-indigo-500 tracking-wider">
                            {{ $userRole }}
                        </p>
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Identidad Institucional (Clínicas - Fijo arriba) -->
        <div class="flex items-center ps-2 mb-6">
            <span class="self-center text-xl font-bold tracking-tight text-slate-800 dark:text-white">
                open<span class="text-blue-600 dark:text-blue-400">doctor</span>
            </span>
            <span class="bg-indigo-600 text-white text-[10px] font-extrabold px-2 py-0.5 rounded ml-2 shadow-sm uppercase tracking-wider">
                Partner
            </span>
        </div>
        
        <!-- Listado de Enlaces Corporativos: AQUÍ SE ACTIVA EL SCROLL INDEPENDIENTE -->
        <!-- Se añaden 'overflow-y-auto', padding derecho 'pr-1' para espaciar la barra, y 'scrollbar-thin' -->
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
