@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Servicios',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    @if (session('success'))
        <div id="alert-success" class="flex items-center p-4 mb-4 text-green-800 rounded-2xl bg-green-50 border border-green-100 shadow-sm transition-opacity duration-500" role="alert">
            <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://w3.org" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L8 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
            </svg>
            <span class="sr-only">Éxito</span>
            <div class="ms-3 text-sm font-medium">
                {{ session('success') }}
            </div>
            <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8" onclick="document.getElementById('alert-success').remove()">
                <span class="sr-only">Cerrar</span>
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://w3.org" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                </svg>
            </button>
        </div>
    @endif

    <div class="max-w-6xl mx-auto py-10 px-4">
        {{-- Si el doctor puede agregar más, mostramos el botón --}}
        @if($doctor->canAddMoreServices())
        <div class="flex justify-between items-center mb-8">
            <a class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition flex items-center gap-2" href="{{ route('doctor.services.create') }}">
                <i class="fa-regular fa-map-location"></i>
                Nuevo servicio
            </a>
        </div>
        @else
            {{-- Si alcanzó el límite, mostramos un mensaje --}}    
            <div class="text-sm text-amber-600 font-medium italic mb-4">
                Has alcanzado el límite de <strong>{{ $doctor->plan->max_services }}</strong> servicios de tu {{ $doctor->plan->name }}.
                <a href="{{ route('doctor.profile.edit') }}" class="underline">Mejora tu plan aquí</a>.
            </div>
        @endif

        <div class="mb-3">
            <p>
                <strong>Uso del plan:</strong> 
                {{ $uniqueServicesCount }} de {{ $doctor->plan->max_services }} servicios creados.
            </p>
        </div>

        <div class="bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-100">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">Servicio</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">Modalidad</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">Duración</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">Precio</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">Sedes</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">Activo</th>                        
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($services as $service)
                    @php
                        // Es un excedente si su posición es >= al límite
                        $isOverLimit = ($loop->index >= $doctor->plan->max_services);
                    @endphp
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <span class="font-bold text-gray-800 block">{{ $service->name }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($service->type === 'virtual')
                                <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-bold uppercase">
                                    Telemedicina
                                </span>
                            @else
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-bold uppercase">
                                    Presencial
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-600 font-medium">
                            {{ $service->duration }} min
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-green-600 font-black">${{ number_format($service->price, 2) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($service->type === 'virtual')
                                <span class="text-gray-400 italic text-sm">Enlace digital</span>
                            @else
                                <div class="flex flex-wrap gap-1">
                                    @foreach($service->addresses as $address)
                                        <span class="bg-gray-100 text-gray-600 text-[10px] px-2 py-0.5 rounded border border-gray-200">
                                            {{ $address->name }} - <span class="font-bold">{{ $address->city->name }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($service->active)
                                <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full">Activo</span>
                            @else
                                <span class="bg-red-100 text-red-700 text-xs px-2 py-1 rounded-full">Inactivo</span>
                            @endif
                        </td>                        
                        <td class="px-6 py-4">
                            @if (!$isOverLimit)
                            <div class="flex items-center space-x-3">
                                <a href="{{ route('doctor.services.edit', $service) }}" class="text-gray-400 hover:text-blue-600 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                </a>
                                <form action="{{ route('doctor.services.destroy', $service) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este servicio?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-gray-400 hover:text-gray-700 transition">Eliminar</button>                            
                                </form>
                                <form action="{{ route('doctor.services.toggle', $service) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    
                                    @if($service->active)
                                        <button type="submit" class="flex items-center text-amber-600 hover:text-amber-800 transition text-xs font-bold">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                            Desactivar
                                        </button>
                                    @else
                                        <button type="submit" class="flex items-center text-green-600 hover:text-green-800 transition text-xs font-bold">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Reactivar
                                        </button>
                                    @endif
                                </form>
                            </div>
                            @else
                            <div class="text-center space-x-3">
                                <small class="text-danger">Fuera de cupo del plan</small>
                            </div>
                            @endif
                        </td>                        
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-400 italic">
                            No has creado servicios todavía.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
