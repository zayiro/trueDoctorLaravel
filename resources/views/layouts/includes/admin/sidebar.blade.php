@php
    $user = auth()->user();
    $pendingInvitationsCount = 0;

    // Calcular invitaciones pendientes del doctor de forma limpia
    if ($user && $user->role === 'doctor' && $user->doctor) {
        $pendingInvitationsCount = $user->doctor->clinics()
            ->where('clinic_doctor.status', 'pending')
            ->count();
    }

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
            'name' => 'Datos del perfil',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>',
            'href' => route('partner.profile.edit'),
            'active' => request()->routeIs('partner.profile.edit'),
        ], 
        [
            'name' => 'Sedes',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25s-7.5-4.108-7.5-11.25a7.5 7.5 0 1 1 15 0Z"/></svg>',
            'href' => route('partner.addresses.index'),
            'active' => request()->routeIs('partner.addresses.index'),
        ],
        [
            'name' => 'Servicios',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.03 0 1.9.693 2.166 1.638m-7.377 0A48.536 48.536 0 0 1 12 3m0 0c-2.917 0-5.747.294-8.5.862m21.196.006A48.474 48.474 0 0 0 12 3c-.228 0-.454.004-.68.012M4.5 7.5v11.25A2.25 2.25 0 0 0 6.75 21h6.375c.621 0 1.125-.504 1.125-1.125V16.5M4.5 7.5C4.805 7.5 5.12 7.46 5.437 7.382M4.5 7.5c-.257 0-.515-.015-.772-.045A2.187 2.187 0 0 1 1.815 5.25a2.186 2.186 0 0 1 .494-1.745 2.25 2.25 0 0 1 1.418-.124c.257.03.515.045.772.045m0 4.125a2.187 2.187 0 0 0 2.188-2.188V3.375"/></svg>',
            'href' => route('partner.services.index'),
            'active' => request()->routeIs('partner.services.index'),
        ],
        [
            'name' => 'Indisponibilidades',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286Zm0 13.036h.008v.008H12v-.008Z"/></svg>',
            'href' => route('partner.unavailabilities.index'),
            'active' => request()->routeIs('partner.unavailabilities.index'),
        ],
        [
            'name' => 'Notificaciones',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>',
            'href' => route('notifications.index'),
            'active' => request()->routeIs('notifications.index'),
        ],
        [
            'name' => 'Agenda',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z"/></svg>',
            'href' => route('partner.appointments.index'),
            'active' => request()->routeIs('partner.appointments.index'),
        ],
        [
            'name' => 'Directorio de Pacientes',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm-1.214 6.33a3.75 3.75 0 0 0-5.072 0 3.75 3.75 0 0 0 5.072 0Z"/></svg>',
            'href' => route('partner.patients.index'),
            'active' => request()->routeIs('partner.patients.index'),
        ],
        [
            'name' => 'Vinculaciones',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/></svg>',
            'href' => route('partner.doctor_clinics.index'),
            'active' => request()->routeIs('partner.doctor_clinics.*'),
            'badge' => $pendingInvitationsCount > 0 ? $pendingInvitationsCount : null,
        ],
        [
            'name' => 'Conocimientos Médicos',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.174L4.3 12m-.04-1.826a5.25 5.25 0 0 1 10.48 0M4.26 10.174a5.25 5.25 0 0 0 10.48 0m0 1.826a5.25 5.25 0 0 1-10.48 0M14.74 12l.04-1.826m0 1.826a5.25 5.25 0 0 0-10.48 0M19.5 12h-4.5m4.5 0a3 3 0 0 1-3 3h-3m6-3a3 3 0 0 0-3-3h-3m0 6h-3m3 0a3 3 0 0 1-3-3V9m3 6a3 3 0 0 0-3-3V9m0 0V5.25A2.25 2.25 0 0 0 8.25 3h-1.5A2.25 2.25 0 0 0 4.5 5.25V9"/></svg>',
            'href' => route('partner.expertises.index'),
            'active' => request()->routeIs('partner.expertises.index'),
        ],
        [
            'name' => 'Configuración',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.43l-1.003.767a1.123 1.123 0 0 0-.417 1.03c.004.074.006.148.006.222 0 .074-.002.148-.006.222a1.123 1.123 0 0 0 .417 1.03l1.003.767a1.125 1.125 0 0 1 .26 1.43l-1.296 2.247a1.125 1.125 0 0 1-1.37.49l-1.216-.456a1.125 1.125 0 0 0-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281a1.125 1.125 0 0 0-.646-.87a6.57 6.57 0 0 1-.22-.127a1.125 1.125 0 0 0-1.075-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.43l1.004-.767a1.122 1.122 0 0 0 .416-1.03c-.004-.074-.006-.148-.006-.222 0-.074.002-.148.006-.222a1.122 1.122 0 0 0-.416-1.03l-1.004-.767a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.49l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128c.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>',
            'href' => route('partner.settings.edit'),
            'active' => request()->routeIs('partner.settings.edit'),
        ],
    ];

    // Detectar dinámicamente el estatus de validación del comercial autenticado
    $commercialProfile = $user->role === 'clinic' ? $user->clinic : $user->doctor;
    $validation = $commercialProfile ? $commercialProfile->validation_status : 'missing';

    if ($validation !== 'approved') {
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
    <div class="flex flex-col h-full px-4 py-5 overflow-y-auto bg-slate-50 dark:bg-slate-900">
            <div class="flex flex-col h-full min-h-full px-4 py-5 overflow-y-auto overflow-x-auto bg-slate-50 dark:bg-slate-900">
                <div class="min-w-full flex-1 flex flex-col justify-between">
                    <!-- Identificación del Socio Comercial (Doctor/Clínica) -->
                    @if($user)
                        <div class="pt-4 pb-4 mt-8 mb-2 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <div class="flex items-center space-x-3 truncate">
                                <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-xs uppercase shadow-sm">
                                    {{ substr($user->name, 0, 2) }}
                                </div>
                                <div class="truncate">
                                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate max-w-[140px]">
                                        {{ $user->name }}
                                    </p>
                                    <p class="text-[10px] uppercase font-semibold text-slate-400 tracking-wider">
                                        {{ $userRole }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Marca Corporativa -->
                    <div class="flex items-center ps-2 mb-6">
                        <span class="self-center text-xl font-bold tracking-tight text-slate-800 dark:text-white">
                            open<span class="text-blue-600 dark:text-blue-400">doctor</span>
                        </span>
                        <span class="bg-blue-600 text-white text-[10px] font-extrabold px-2 py-0.5 rounded ml-2 shadow-sm uppercase tracking-wider">
                            Partner
                        </span>
                    </div>
                    <!-- Listado de Enlaces Administrativos -->
                    <ul class="space-y-1.5 font-medium flex-1">
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
                                        <a href="{{ $link['href'] }}" class="flex items-center justify-between p-2.5 text-sm font-medium rounded-lg transition-all duration-150 group {{ $link['active'] ? 'bg-blue-50 text-blue-700 font-semibold border-l-4 border-blue-600 dark:bg-slate-800 dark:text-blue-400 dark:border-blue-500' : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                                            <div class="flex items-center">
                                                <span class="{{ $link['active'] ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 group-hover:text-blue-600' }} transition-colors duration-150">
                                                    {!! $link['icon'] !!}
                                                </span>
                                                <span class="ms-3">{{ $link['name'] }}</span>
                                            </div>
                                            
                                            @isset($link['badge'])
                                                <span class="inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-red-500 rounded-full animate-bounce shadow-sm">
                                                    {{ $link['badge'] }}
                                                </span>
                                            @endisset
                                        </a>
                                    @endisset
                                @endisset
                            </li>            
                        @endforeach
                    </ul>   
                </div>     
            </div>
    </div>
</aside>
