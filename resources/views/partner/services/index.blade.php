@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Portafolio de Servicios',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <!-- ALERTA DE ÉXITO DE TRANSACCIÓN CONTROLADA CON ALPINE.JS -->
    @if (session('success'))
        <div x-data="{ show: true }" x-show="show" x-transition id="alert-success" class="flex items-center p-4 mb-4 text-green-800 rounded-2xl bg-green-50 border border-green-100 shadow-sm" role="alert">
            <svg class="flex-shrink-0 w-4 h-4" aria-true xmlns="http://w3.org" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L8 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
            </svg>
            <span class="sr-only">Éxito</span>
            <div class="ms-3 text-sm font-bold">
                {{ session('success') }}
            </div>
            <button type="button" @click="show = false" class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8">
                <span class="sr-only">Cerrar</span>
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://w3.org" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                </svg>
            </button>
        </div>
    @endif

    <div class="max-w-6xl mx-auto py-10 px-4">
        {{-- Si el propietario (doctor o clínica) puede agregar más, mostramos el botón --}}
        @if($owner->canAddMoreServices())
        <div class="flex justify-between items-center mb-8">
            <a class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-bold transition flex items-center gap-2 text-sm shadow-md shadow-indigo-100 uppercase tracking-wider" href="{{ route('partner.services.create') }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Nuevo servicio
            </a>
        </div>
        @else
            {{-- Si alcanzó el límite del plan, mostramos un mensaje corporativo --}}    
            <div class="text-sm text-amber-600 font-medium italic mb-4 p-4 bg-amber-50 rounded-xl border border-amber-100 shadow-sm flex items-center gap-2">
                <span>⚠️ Has alcanzado el límite de <strong>{{ $owner->plan->max_services ?? 3 }}</strong> servicios de tu plan {{ $owner->plan->name ?? 'Básico' }}.</span>
                <a href="{{ route('partner.profile.edit') }}" class="underline font-black ml-1 hover:text-amber-800 uppercase tracking-wider text-xs">Mejora tu plan aquí</a>.
            </div>
        @endif

        <div class="mb-5 bg-slate-50 p-4 rounded-xl border border-slate-100 inline-block shadow-inner">
            <p class="text-sm text-slate-600">
                <strong>Uso del plan actual:</strong> 
                <span class="font-bold text-slate-900">{{ $uniqueServicesCount }}</span> de <span class="font-bold text-slate-900">{{ $owner->plan->max_services ?? 3 }}</span> servicios creados.
            </p>
        </div>

        <div class="bg-white shadow-xl rounded-3xl border border-gray-100 overflow-hidden w-full">
            <div class="overflow-x-auto min-w-full inline-block align-middle">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider">Servicio</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider">Modalidad</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider">Duración</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider">Precio</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider">Sedes</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider">Activo</th>                        
                            <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($services as $service)
                        @php
                            // Evaluamos el excedente sobre el servicio unificado cruzando el plan del dueño
                            $isOverLimit = ($loop->index >= ($owner->plan->max_services ?? 3));
                            // Tomamos la primera dirección para mostrar una duración de referencia en la tabla
                            $firstAddress = $service->addresses->first();
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition {{ $isOverLimit ? 'opacity-60 bg-gray-50' : '' }}">
                            <td class="px-6 py-4">
                                <span class="font-black text-slate-800 block tracking-tight">{{ $service->name }}</span>
                            </td>
                            <td class="px-6 py-4">
                                @if($service->type === 'virtual')
                                    <span class="px-2.5 py-1 bg-purple-50 text-purple-700 border border-purple-100 rounded-full text-[10px] font-black uppercase tracking-wide">
                                        Telemedicina
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-100 rounded-full text-[10px] font-black uppercase tracking-wide">
                                        Presencial
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-600 font-bold text-sm">
                                <!-- Duración extraída correctamente de la tabla pivote de la primera sede -->
                                {{ $firstAddress?->pivot->duration ?? 0 }} min
                            </td>
                            <td class="px-6 py-4">
                                @if($service->type === 'virtual')
                                    <!-- Precio virtual unificado desde el pivot -->
                                    <span class="text-green-600 font-black text-sm">${{ number_format($firstAddress?->pivot->price ?? 0, 2) }}</span>
                                @else
                                    <!-- Listado dinámico de tarifas corporativas mapeadas por sede -->
                                    <div class="flex flex-col gap-1.5">
                                        @foreach($service->addresses as $address)
                                            <span class="text-xs text-slate-600 font-medium">
                                                <strong class="text-green-600 font-black">${{ number_format($address->pivot->price, 2) }}</strong> 
                                                <span class="text-slate-400 font-normal">({{ $address->name }})</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($service->type === 'virtual')
                                    <span class="text-slate-400 italic text-xs font-medium">Enlace digital / Plataforma</span>
                                @else
                                    <div class="flex flex-wrap gap-1 max-w-xs">
                                        @foreach($service->addresses as $address)
                                            <span class="bg-slate-50 text-slate-600 text-[10px] font-bold px-2 py-0.5 rounded-lg border border-slate-200/60">
                                                {{ $address->name }} - <span class="text-indigo-600 font-black">{{ $address->city->name ?? 'N/A' }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($service->active)
                                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-100 text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full">Activo</span>
                                @else
                                    <span class="bg-rose-50 text-rose-700 border border-rose-100 text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full">Inactivo</span>
                                @endif
                            </td>                        
                            <td class="px-6 py-4">
                                @if (!$isOverLimit)
                                    <div class="flex items-center">
                                        <!-- Enlace limpio para la edición del servicio en base a su ID -->
                                        <a href="{{ route('partner.services.edit', $service->id) }}" class="text-indigo-600 hover:text-indigo-900 text-xs font-black uppercase tracking-wider transition-colors">
                                            Editar
                                        </a>
                                    </div>
                                @else
                                    <span class="text-[10px] text-amber-700 font-black uppercase tracking-wider bg-amber-50 px-2.5 py-1 rounded-full border border-amber-100">Excede plan</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center bg-white">
                                <div class="mx-auto w-12 h-12 text-slate-400 mb-3 flex items-center justify-center bg-slate-50 rounded-full border border-slate-100 shadow-inner">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-6 h-6 text-slate-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                    </svg>
                                </div>
                                <h4 class="text-base font-black text-slate-800 tracking-tight">No hay servicios configurados</h4>
                                <p class="text-slate-400 text-xs mt-1 max-w-sm mx-auto leading-relaxed">Comienza agregando los servicios de salud que ofreces para asignarle sus respectivos precios y duraciones por sede.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
