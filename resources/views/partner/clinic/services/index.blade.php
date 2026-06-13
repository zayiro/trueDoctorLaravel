@php
// 🔒 AISLAMIENTO CLÍNICO: Navegación del panel institucional del SaaS
$breadcrumbs = [
    [
        'name' => 'Panel Institucional',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Catálogo de Servicios Médicos',
    ]
];

// Cálculo estricto de límites para el Tenant corporativo
$maxServices = $owner->plan?->max_services ?? 0;
$canAddMoreServices = $uniqueServicesCount < $maxServices;
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <!-- ALERTA DE ÉXITO DE TRANSACCIÓN CONTROLADA CON ALPINE.JS -->
    @if (session('success'))
        <div x-data="{ show: true }" x-show="show" x-transition id="alert-success" class="flex items-center p-4 mb-4 text-green-800 rounded-2xl bg-green-50 border border-green-100 shadow-sm" role="alert">
            <!-- 🎯 HEROICONS: check-circle (outline) -->
            <svg class="flex-shrink-0 w-5 h-5 text-green-600" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="sr-only">Éxito</span>
            <div class="ms-3 text-sm font-bold">
                {{ session('success') }}
            </div>
            <button type="button" @click="show = false" class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8">
                <span class="sr-only">Cerrar</span>
                <!-- 🎯 HEROICONS: x-mark (outline) -->
                <svg class="w-4 h-4" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif
    <div class="max-w-6xl mx-auto py-10 px-4">
        {{-- Validación de límites dinámica para la Clínica --}}
        @if($canAddMoreServices)
        <div class="flex justify-between items-center mb-8">
            <a class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-bold transition flex items-center gap-2 text-sm shadow-md shadow-indigo-100 uppercase tracking-wider" href="{{ route('partner.clinic.services.create') }}">
                <!-- 🎯 HEROICONS: plus (outline) -->
                <svg class="w-4 h-4" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo Servicio Institucional
            </a>
        </div>
        @else
            <div class="text-sm text-amber-600 font-medium italic mb-4 p-4 bg-amber-50 rounded-xl border border-amber-100 shadow-sm flex items-center gap-2">
                <span>⚠️ El centro médico ha alcanzado el límite de <strong>{{ $maxServices }}</strong> servicios de su plan corporativo {{ $owner->plan?->name ?? 'Clínica' }}.</span>
                <a href="{{ route('partner.profile.edit') }}" class="underline font-black ml-1 hover:text-amber-800 uppercase tracking-wider text-xs">Gestionar suscripción aquí</a>.
            </div>
        @endif

        {{-- Contadores de consumo de infraestructura actual --}}
        <div class="mb-5 bg-slate-50 p-4 rounded-xl border border-slate-100 inline-block shadow-inner">
            <p class="text-sm text-slate-600">
                <strong>Capacidad del Catálogo:</strong> 
                <span class="font-bold text-slate-900">{{ $uniqueServicesCount }}</span> de <span class="font-bold text-slate-900">{{ $owner->plan?->max_services ?? 'Ilimitado' }}</span> servicios registrados.
            </p>
        </div>
        <div class="bg-white shadow-xl rounded-3xl border border-gray-100 overflow-hidden w-full mt-6">
            <!-- 🚀 CONTENEDOR RESPONSIVO: Forzamos bloque completo con scroll horizontal elástico -->
            <div class="overflow-x-auto w-full block">
                <!-- 🎯 TABLA MODERADA: min-w-full asegura el llenado proporcional -->
                <table class="w-full min-w-full text-left border-collapse whitespace-nowrap table-auto">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider">Servicio Institucional</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider">Especialidad Clínica</th>                            
                            <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider">Duración</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider">Tarifas</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider">Sedes Habilitadas</th>                                            
                            <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($services as $service)
                        @php
                            $maxServicesLimit = $owner->plan?->max_services ?? 999;
                            $isOverLimit = ($loop->index >= $maxServicesLimit);
                            $firstAddress = $service->addresses->first();
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition {{ $isOverLimit ? 'opacity-60 bg-gray-50' : '' }}">
                            <!-- Nombre del Servicio -->
                            <td class="px-6 py-4">
                                <span class="font-black text-slate-800 block tracking-tight">{{ $service->name }}</span>
                                <div>
                                    @if($service->type === 'virtual')
                                        <span class="px-2.5 py-1 bg-purple-50 text-purple-700 border border-purple-100 rounded-full text-[10px] font-black uppercase tracking-wide">
                                            Telemedicina
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-100 rounded-full text-[10px] font-black uppercase tracking-wide">
                                            Presencial
                                        </span>
                                    @endif
                                </div>
                                <div class="mt-2">
                                    @if($service->active)
                                        <span class="bg-emerald-50 text-emerald-700 border border-emerald-100 text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full">Activo</span>
                                    @else
                                        <span class="bg-rose-50 text-rose-700 border border-rose-100 text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full">Inactivo</span>
                                    @endif
                                </div>
                            </td>
                            
                            <!-- Especialidades Clínicas -->
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    {{-- 🎯 FIX DE DUPLICADOS: Aplicamos ->unique('id') para filtrar las especialidades repetidas --}}
                                    @forelse($service->specialties->unique('id') as $specialty)
                                        <span class="bg-indigo-50 text-indigo-700 text-[10px] font-black px-2 py-0.5 rounded-lg border border-indigo-100 uppercase tracking-wide">
                                            {{ $specialty->name }}
                                        </span>
                                    @empty
                                        <span class="text-amber-600 text-xs italic font-medium bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-100">
                                            Sin especialidad asignada
                                        </span>
                                    @endforelse
                                </div>
                            </td>
                            <!-- Duración del Turno -->
                            <td class="px-6 py-4 text-slate-600 font-bold text-sm">
                                @if($firstAddress)
                                    {{ $firstAddress->pivot->duration }} min
                                @else
                                    <span class="text-gray-400 italic text-xs font-normal">No definida</span>
                                @endif
                            </td>
                            <!-- Tarifas por Sede -->
                            <td class="px-6 py-4">
                                @if(!$firstAddress)
                                    <span class="text-amber-600 text-xs font-bold bg-amber-50 px-2 py-1 rounded-xl border border-amber-100">Configuración pendiente</span>
                                @elseif($service->type === 'virtual')
                                    <span class="text-green-600 font-black text-sm">${{ number_format($firstAddress->pivot->price, 2) }}</span>
                                @else
                                    <div class="flex flex-col gap-1.5">
                                        @foreach($service->addresses as $address)
                                            <span class="text-xs text-slate-600 font-medium">
                                                <strong class="text-green-600 font-black">${{ number_format($address->pivot->price, 2) }}</strong> 
                                                <div class="text-slate-400 font-normal">({{ $address->name }})</div>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>

                            <!-- Sedes e Infraestructura Habilitada -->
                            <td class="px-6 py-4">
                                @if($service->type === 'virtual')
                                    <span class="text-slate-400 italic text-xs font-medium">Consultorio Digital Clínico</span>
                                @else
                                    <div class="flex flex-wrap gap-1 max-w-xs">
                                        @forelse($service->addresses as $address)
                                            <span class="bg-slate-50 text-slate-600 text-[10px] font-bold px-2 py-0.5 rounded-lg border border-slate-200/60">
                                                {{ $address->name }} - <span class="text-indigo-600 font-black">{{ $address->city->name ?? 'N/A' }}</span>
                                            </span>
                                        @empty
                                            <span class="text-amber-600 text-xs italic font-medium bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-100">
                                                Sin infraestructura asociada
                                            </span>
                                        @endforelse
                                    </div>
                                @endif
                            </td>                

                            <!-- Acciones de Fila -->
                            <td class="px-6 py-4">
                                @if (!$isOverLimit)
                                    <div class="flex items-center">
                                        {{-- 🎯 FIX DE PARÁMETROS: Pasamos las llaves relacionales en el orden exacto de la ruta --}}
                                        <a href="{{ route('partner.clinic.services.edit', ['address' => $firstAddress->id, 'service' => $service->id]) }}" 
                                        class="text-indigo-600 hover:text-indigo-900 text-xs font-black uppercase tracking-wider transition-colors">
                                            Editar
                                        </a>
                                    </div>
                                @else
                                    <span class="text-[10px] text-amber-700 font-black uppercase tracking-wider bg-amber-50 px-2.5 py-1 rounded-full border border-amber-100">Excede Límite</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <!-- 🔒 Blindaje relacional con colspan exacto de 8 columnas -->
                            <td colspan="8" class="px-6 py-16 text-center bg-white">
                                <div class="mx-auto w-12 h-12 text-slate-400 mb-3 flex items-center justify-center bg-slate-50 rounded-full border border-slate-100 shadow-inner">
                                    <!-- 🎯 HEROICONS: clipboard-document-list (outline) -->
                                    <svg class="w-6 h-6 text-slate-400" xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.03 0 1.9.693 2.166 1.638m-7.377 0A48.536 48.536 0 0 1 12 3.75c.38 0 .758.004 1.136.01m-3.385 0c-.18.016-.359.033-.538.051m4.462-.051c.179.016.358.033.537.051M5.25 6.108V18.25A2.25 2.25 0 0 0 7.5 20.5h3.385m-6.635-14.39a2.25 2.25 0 0 1 1.976-2.192c.404-.034.81-.062 1.217-.083m-3.193 2.275a48.406 48.406 0 0 0-.115 5.233m-.034 1.786a48.294 48.294 0 0 0 .066 4.16" />
                                    </svg>
                                </div>
                                <h4 class="text-base font-black text-slate-800 tracking-tight">Catálogo institucional vacío</h4>
                                <p class="text-slate-400 text-xs mt-1 max-w-sm mx-auto leading-relaxed">Comienza registrando los servicios clínicos que presta la organización para asignarlos a sus respectivas especialidades y sedes habilitadas.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
