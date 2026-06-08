@php
$user = auth()->user();
$patient = ($user && $user->patient()->exists()) ? $user->patient : null;

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
    ],
    [
        'name' => 'Mis Citas',
        'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z"/></svg>',
        'href' => route('patient.appointments.index'),
        'active' => request()->routeIs('patient.appointments.index'),
    ],
    [
        'name' => 'Pagos y Facturación',
        'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>',
        'href' => route('partner.appointments.index'),
        'active' => request()->routeIs('partner.appointments.index'),
    ],
    [
        'header' => 'Antecedentes Médicos',        
    ],
    [
        'name' => 'Ficha de Identificación',
        'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm-1.214 6.33a3.75 3.75 0 0 0-5.072 0 3.75 3.75 0 0 0 5.072 0Z"/></svg>',
        'href' => route('patient.patient-identification.index'),
        'active' => request()->routeIs('patient.patient-identification.index'),
    ],
    [
        'name' => 'Historial Clínico',
        'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>',
        'href' => route('patient.history.index'),
        'active' => request()->routeIs('patient.history.index'),
    ],
    [
        'name' => 'Alergias',
        'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>',
        'href' => route('patient.allergies.index'),
        'active' => request()->routeIs('patient.allergies.index'),
    ],
    [
        'name' => 'Reporte Quirúrgico',
        'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9.75L16.5 12l-2.25 2.25m-4.5 0L7.5 12l2.25-2.25M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z"/></svg>',
        'href' => route('patient.surgeries.index'),
        'active' => request()->routeIs('patient.surgeries.index'),
    ],
    [
        'name' => 'Historial Familiar',
        'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A4.49 4.49 0 0 1 8.323 14.5m8.034 4.22a9.09 9.09 0 0 0 1.94-2.22c.19-.344.257-.74.257-1.136a4.49 4.49 0 0 0-1.66-3.355m-.104 6.126-2.315-2.222M10.5 7.5a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm6.75 2.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm-12.75 9c0-1.336 1.054-2.424 2.38-2.44a11.958 11.958 0 0 1 9.74 0c1.326.015 2.38 1.104 2.38 2.441 0 .641-.12 1.253-.337 1.817H4.087C3.87 19.818 3.75 19.206 3.75 18.563Z"/></svg>',
        'href' => route('patient.family-history.index'),
        'active' => request()->routeIs('patient.family-history.index'),
    ],
    [
        'name' => 'Medicación',
        'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0 1 18 0Z"/></svg>',
        'href' => route('patient.medications.index'),
        'active' => request()->routeIs('patient.medications.index'),
    ],   
    [
        'name' => 'Descargar Historia Clínica',
        'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>',
        'href' => $patient ? route('patient.pdf.clinical-history', $patient) : '#',
        'active' => request()->routeIs('patient.pdf.clinical-history'),
    ],    
];

$userRole = match ($user->role) {
        'admin' => 'Administrador',
        'doctor' => 'Especialista',
        'clinic' => 'Clínica',
        'patient' => 'Paciente',
    };
@endphp

<aside id="top-bar-sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full border-r bg-slate-50 border-slate-200 sm:translate-x-0 dark:bg-slate-900 dark:border-slate-800" aria-label="Sidebar">
    <!-- SE REMOVIÓ 'overflow-y-auto' y 'overflow-x-auto' PARA CONGELAR LOS ENCABEZADOS -->
    <div class="h-full px-4 pb-12 overflow-hidden bg-slate-50 dark:bg-slate-900 block">
        
        <!-- Pie del Sidebar: Información de Cuenta paciente (Fijo arriba) -->
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
                        <p class="text-[10px] uppercase font-semibold text-slate-400 tracking-wider">
                            {{ $userRole }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Identidad del SaaS (Portal - Fijo arriba) -->
        <div class="flex items-center ps-2 mb-6 whitespace-nowrap">
            <span class="self-center text-xl font-bold tracking-tight text-slate-800 dark:text-white">
                open<span class="text-blue-600 dark:text-blue-400">doctor</span>
            </span>
            <span class="bg-blue-50 text-blue-700 text-[10px] font-bold px-2 py-0.5 rounded ml-2 border border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-900">
                PORTAL
            </span>
        </div>

        <!-- Listado de Enlaces Dinámicos: AQUÍ SE APLICA EL SCROLL INDEPENDIENTE -->
        <!-- Ocupa todo el alto restante de forma nativa con flex-1, overflow-y-auto y espaciado pr-1 -->
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
                            </a>
                        @endisset
                    @endisset
                </li>            
            @endforeach
        </ul>
        
    </div>
</aside>
