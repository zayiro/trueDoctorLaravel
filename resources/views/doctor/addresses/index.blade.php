@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Consultorios',
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

    @if(Auth::user()->doctor->canAddMoreAddresses())
    <div class="flex justify-between items-center mb-8">            
        <!-- Botón para abrir formulario/modal -->
        <a class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition flex items-center gap-2" href="{{ route('doctor.addresses.create') }}">
            <i class="fa-regular fa-map-location"></i>
            Nuevo consultorio
        </a>        
    </div>
    @else
        <div class="text-sm text-amber-600 font-medium italic mb-4">
            Límite alcanzado. ¡Mejora a Plan Avanzado!
        </div>
    @endif

    <!-- Lista de Sedes -->
    <div class="grid grid-cols-1 gap-4">
        @forelse($addresses as $address)            
            <div class="bg-white border rounded-xl p-5 shadow-sm flex justify-between items-center">                
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-indigo-100 text-indigo-600 rounded-lg">
                        <svg xmlns="http://w3.org" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg">{{ $address->name }}</h3>
                        <p class="text-gray-500">{{ $address->address }} {{ $address->address === 'Plataforma Online' ? '' : ', ' . $address->city->name }}</p>
                        <p class="text-gray-500">{{ $address->phone }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                     <a href="{{ route('doctor.schedules.index', $address->id) }}" class="p-2 text-gray-400 hover:text-gray-700 transition">
                        <div>Configurar horarios y duración</div>
                        <div>
                            @if($address->schedules_count > 0)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-green-400" fill="currentColor" viewBox="0 0 8 8">
                                        <circle cx="4" cy="4" r="3" />
                                    </svg>
                                    Configurado
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-yellow-400" fill="currentColor" viewBox="0 0 8 8">
                                        <circle cx="4" cy="4" r="3" />
                                    </svg>
                                    Pendiente
                                </span>
                            @endif
                        </div>
                    </a>
                    
                    @if ($address->address !== 'Plataforma Online')
                        <a class="p-2 text-gray-400 hover:text-gray-700 transition" href="{{ route('doctor.addresses.edit', $address->id) }}">Editar</a>
                        <form action="{{ route('doctor.addresses.destroy', $address->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este consultorio?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-gray-400 hover:text-gray-700 transition">Eliminar</button>                            
                        </form>                    
                        <form action="{{ route('doctor.addresses.status.toggle', $address) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            
                            <button type="submit" 
                                class="inline-flex items-center px-3 py-1 mt-1 border text-sm font-medium rounded-full shadow-sm 
                                {{ $address->status 
                                    ? 'border-red-300 text-red-700 bg-white hover:bg-red-50' 
                                    : 'border-green-300 text-green-700 bg-white hover:bg-green-50' 
                                }}">
                                
                                @if($address->status)
                                    <svg class="mr-2 h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Desactivar
                                @else
                                    <svg class="mr-2 h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Activar
                                @endif
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-12 bg-white rounded-xl border-2 border-dashed">
                <p class="text-gray-500">Aún no has registrado ningún consultorio.</p>
            </div>
        @endforelse
    </div>

</x-admin-layout>