@php
    // Calcular invitaciones pendientes del doctor de forma limpia
    $pendingInvitationsCount = 0;
    if (auth()->check() && auth()->user()->role === 'doctor') {
        $pendingInvitationsCount = auth()->user()->doctor->clinics()
            ->where('clinic_doctor.status', 'pending')
            ->count();
    }

    $links = [
        [
            'header' => 'Administrar página',        
        ],
        [
            'name' => 'Dashboard',
            'icon' => 'fa-solid fa-gauge',
            'href' => route('admin.dashboard'),
            'active' => false,
        ],
        [
            'name' => 'Datos del perfil ',
            'icon' => 'fa-solid fa-gauge',
            'href' => route('partner.profile.edit'),
            'active' => false,
        ], 
        [
            'name' => 'Sedes',
            'icon' => 'fa-solid fa-gauge',
            'href' => route('partner.addresses.index'),
            'active' => false,
        ],
        [
            'name' => 'Servicios',
            'icon' => 'fa-solid fa-gauge',
            'href' => route('partner.services.index'),
            'active' => false,
        ],
        [
            'name' => 'Indisponibilidades',
            'icon' => 'fa-solid fa-gauge',
            'href' => '#',
            'active' => false,
        ],
        [
            'name' => 'Notificaciones',
            'icon' => 'fa-solid fa-gauge',
            'href' => route('notifications.index'),
            'active' => false,
        ],
        [
            'name' => 'Agenda',
            'icon' => 'fa-solid fa-gauge',
            'href' => route('partner.appointments.index'),
            'active' => false,
        ],
        [
            'name' => 'Directorio de Pacientes',
            'icon' => 'fa-solid fa-gauge',
            'href' => route('partner.patients.index'),
            'active' => false,
        ],
        [
            'name' => 'Vinculaciones',
            'icon' => 'fa-solid fa-gauge',
            'href' => route('partner.doctor_clinics.index'),
            'active' => request()->routeIs('partner.doctor_clinics.*'),
            'badge' => $pendingInvitationsCount > 0 ? $pendingInvitationsCount : null,
        ],
        [
            'name' => 'Conocimientos Médicos',
            'icon' => 'fa-solid fa-gauge',
            'href' => route('partner.expertises.index'),
            'active' => false,
        ],
        [
            'name' => 'Configuración',
            'icon' => 'fa-solid fa-gauge',
            'href' => route('partner.settings.edit'),
            'active' => false,
        ],
    ];

    $validation = auth()->user()->doctor->validation_status;

    if ($validation != 'approved')
    {
        $links = [
            [
                'header' => 'Validando documentos...',        
            ],
        ];
    }
@endphp

<aside id="top-bar-sidebar" class="fixed top-0 left-0 z-40 w-64 h-full transition-transform -translate-x-full sm:translate-x-0" aria-label="Sidebar">
    <div class="h-full px-3 py-4 overflow-y-auto bg-white border-e border-default">
        <a href="https://flowbite.com/" class="flex items-center ps-2.5 mb-5">
            <img src="https://flowbite.com/docs/images/logo.svg" class="h-6 me-3" alt="Flowbite Logo" />
            <span class="self-center text-lg text-heading font-semibold whitespace-nowrap">Flowbite</span>
        </a>
        <ul class="pt-5 space-y-2 font-medium">
            @foreach ($links as $link)
                <li>
                    @isset($link['header'])
                        <div class="px-2 py-2 text-xs font-semibold text-gray-500 uppercase">
                            {{  $link['header'] }}
                        </div>
                    @else
                        @isset($link['submenu'])
                            <button type="button" class="flex items-center w-full justify-between px-2 py-1.5 text-body rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group" aria-controls="dropdown-example" data-collapse-toggle="dropdown-example">
                                <span class="w-6 h-6 inline-flex justify-center items-center text-gray-500">
                                    <i class="{{  $link['icon'] }}"></i>
                                </span>
                                <span class="ms-3">{{ $link['name'] }}</span>
                                <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
                            </button>
                            <ul id="dropdown-example" class="hidden py-2 space-y-2">
                                @foreach ($link['submenu'] as $item)
                                    <li>
                                        <a href="{{  $item['href'] }}" class="pl-10 flex items-center px-2 py-1.5 text-body rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group">{{  $item['name'] }}</a>
                                    </li>                            
                                @endforeach
                            </ul>
                        @else
                            <a href="{{  $link['href'] }}" class="flex items-center justify-between p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ $link['active'] ? 'bg-gray-100' : '' }}">
                                <div class="flex items-center">
                                    <span class="w-6 h-6 inline-flex justify-center items-center text-gray-500">
                                        <i class="{{  $link['icon'] }}"></i>
                                    </span>
                                    <span class="ms-3">{{  $link['name'] }}</span>
                                </div>
                                {{-- Renderizado condicional de la píldora flotante animada --}}
                                @isset($link['badge'])
                                    <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-semibold text-white bg-red-500 rounded-full animate-bounce">
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
</aside>