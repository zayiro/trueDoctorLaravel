@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Sedes fisicas (Consultorios)',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <!-- Alerta de Éxito -->
    @if (session('success'))
        <div id="alert-success" class="flex items-center p-4 mb-4 text-green-800 rounded-2xl bg-green-50 border border-green-100 shadow-sm" role="alert">
            <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://w3.org" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L8 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
            </svg>
            <div class="ms-3 text-sm font-medium">
                {{ session('success') }}
            </div>
            <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8" onclick="document.getElementById('alert-success').remove()">
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://w3.org" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                </svg>
            </button>
        </div>
    @endif

    <!-- Alerta de Error -->
    @if (session('error'))
        <div class="flex items-center p-4 mb-4 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm" role="alert">
            <div class="text-sm font-medium">{{ session('error') }}</div>
        </div>
    @endif

    <!-- 🔒 GESTIÓN CONDICIONAL SEGÚN EL ENTORNO ACTIVO DEL MÉDICO -->
    @if(auth()->user()->role === 'doctor' && ($currentContext['type'] ?? 'particular') === 'clinic')
        <!-- Banner Informativo: Espacio Institucional Activo -->
        <div class="flex items-center p-4 mb-6 text-sm text-blue-800 border border-blue-200 rounded-2xl bg-blue-50/50 shadow-sm" role="alert">
            <svg class="flex-shrink-0 inline w-5 h-5 me-3 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 111.063.852l-.708 2.836a.75.75 0 001.063.852l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            <div>
                <span class="font-bold">Modo Lectura Institucional:</span> Estás visualizando las sedes autorizadas de la clínica corporativa <strong class="text-blue-900">{{ $currentContext['name'] }}</strong>. Las operaciones estructurales (alta, edición o bajas de sedes) se reservan exclusivamente para la administración del centro médico.
            </div>
        </div>
    @else
        <!-- Control de Botón / Límite del Plan SaaS (Tu lógica original de producción para consultorio particular o clínica pura) -->
        @if ($canAddAddress)
            @if($owner->canAddMoreAddresses())
                <div class="flex justify-between items-center mb-6">            
                    <a class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition flex items-center gap-2" href="{{ route('partner.addresses.create') }}">
                        <i class="fa-regular fa-map-location"></i>
                        Nueva sede
                    </a>        
                </div>
            @else
                <div class="text-sm text-amber-600 font-medium italic mb-4 p-4 bg-amber-50 rounded-xl border border-amber-100">
                    Has alcanzado el límite de <strong>{{ $owner->plan->max_addresses ?? 2 }}</strong> sedes de tu plan {{ $owner->plan->name ?? 'Esencial' }}.
                    <a href="{{ route('partner.profile.edit') }}" class="underline font-bold ml-1 hover:text-amber-800">Mejora tu plan aquí</a>.
                </div>
            @endif
        @else
        <div class="text-sm text-amber-600 font-medium italic mb-4 p-4 bg-amber-50 rounded-xl border border-amber-100">
            Actualiza tu plan para poder crear sedes fisicas (Consultorios)
            <a href="{{ route('partner.profile.edit') }}" class="underline font-bold ml-1 hover:text-amber-800">Mejora tu plan aquí</a>.
        </div>
        @endif

        <!-- Indicador Estadístico de Uso (Solo visible en entornos comerciales propios con planes asignados) -->
        <div class="mb-5 bg-slate-50 p-4 rounded-xl border border-slate-100 inline-block">
            <p class="text-sm text-slate-600">
                <strong>Uso del plan actual:</strong> 
                <span class="font-bold text-slate-900">{{ $owner->addresses->count() }}</span> de <span class="font-bold text-slate-900">{{ $owner->plan->max_addresses ?? 2 }}</span> sedes creadas.
            </p>
        </div>
    @endif
    <!-- Lista de Sedes -->
    <div class="grid grid-cols-1 gap-4">
        @forelse($addresses as $address)            
            <div class="bg-white border rounded-xl p-5 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">                
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-indigo-100 text-indigo-600 rounded-lg">
                        <svg xmlns="http://w3.org" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg flex items-center gap-2">
                            {{ $address->name }}
                            @if($address->type === 'virtual')
                                <span class="text-xs font-semibold bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">Telemedicina</span>
                            @endif
                        </h3>
                        <p class="text-gray-500 text-sm">
                            {{ $address->address }}{{ $address->type === 'virtual' ? '' : ', ' . ($address->city->name ?? '') }}
                        </p>
                        @if($address->phone)
                            <p class="text-gray-400 text-xs mt-0.5 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                {{ $address->phone }}
                            </p>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto justify-end border-t md:border-t-0 pt-3 md:pt-0">
                    <!-- ENLACE DE HORARIOS ADAPTADO AL CONTEXTO -->
                    <a href="{{ route('partner.schedules.index', $address->id) }}" class="inline-flex flex-col items-start md:items-end text-right hover:bg-slate-50 p-2 rounded-lg transition group">
                        @if(auth()->user()->role === 'doctor' && ($currentContext['type'] ?? 'particular') === 'clinic')
                            <!-- Semántica de consulta para el personal en sedes corporativas -->
                            <div class="text-sm font-semibold text-slate-700 group-hover:text-emerald-600">Ver mis horarios asignados ({{ $address->schedules_count }})</div>
                        @else
                            <!-- Semántica original de administración (Particular o Clínica Pura) -->
                            <div class="text-sm font-semibold text-slate-700 group-hover:text-indigo-600">Configurar horarios ({{ $address->schedules_count }})</div>
                        @endif
                        
                        <div class="mt-1">
                            @if($address->schedules_count > 0)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-green-400" fill="currentColor" viewBox="0 0 8 8">
                                        <circle cx="4" cy="4" r="3" />
                                    </svg>
                                    Configurado
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-yellow-400" fill="currentColor" viewBox="0 0 8 8">
                                        <circle cx="4" cy="4" r="3" />
                                    </svg>
                                    Pendiente
                                </span>
                            @endif
                        </div>
                    </a>
                    
                    <!-- CONTROL DE PERMISOS CRUD SOBRE LA SEDE -->
                    @if ($address->type !== 'virtual')
                        @if(auth()->user()->role === 'doctor' && ($currentContext['type'] ?? 'particular') === 'clinic')
                            <!-- Bloqueo visual estricto para médicos en entornos institucionales -->
                            <div class="text-xs font-medium text-gray-400 italic px-3 py-2 bg-gray-50 rounded-lg border border-gray-100">
                                Sede administrada por la clínica
                            </div>
                        @else
                            <!-- Tus controles originales de producción intactos para consultorios propios o clínicas puras -->
                            <div class="flex items-center gap-2 ml-2">
                                <a class="px-3 py-1.5 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition" href="{{ route('partner.addresses.edit', $address->id) }}">
                                    Editar
                                </a>
                                
                                <form action="{{ route('partner.addresses.destroy', $address) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este consultorio? Se borrarán de forma lógica sus configuraciones locales.');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition">
                                        Eliminar
                                    </button>                            
                                </form>                    
                                
                                <form action="{{ route('partner.addresses.status.toggle', $address) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    
                                    <button type="submit" 
                                        class="inline-flex items-center px-3 py-1.5 border text-sm font-medium rounded-lg shadow-sm transition
                                        {{ $address->status 
                                            ? 'border-red-200 text-red-700 bg-white hover:bg-red-50' 
                                            : 'border-green-200 text-green-700 bg-white hover:bg-green-50' 
                                        }}">
                                        
                                        @if($address->status)
                                            <svg class="mr-1.5 h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Desactivar
                                        @else
                                            <svg class="mr-1.5 h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Activar
                                        @endif
                                    </button>
                                </form>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-16 bg-white rounded-xl border-2 border-dashed border-slate-200 p-6 w-full">
                <div class="mx-auto w-12 h-12 text-slate-400 mb-3">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-12 h-12 mx-auto text-slate-300"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <h4 class="text-base font-bold text-slate-800">
                    {{ (auth()->user()->role === 'doctor' && ($currentContext['type'] ?? 'particular') === 'clinic') ? 'No tienes sedes asignadas en esta clínica' : 'No hay consultorios registrados' }}
                </h4>
                <p class="text-gray-500 text-sm mt-1 max-w-sm mx-auto">
                    {{ (auth()->user()->role === 'doctor' && ($currentContext['type'] ?? 'particular') === 'clinic') ? 'Comunícate con la administración de la clínica para que habiliten consultorios en tus especialidades y compartan la disponibilidad física.' : 'Comienza agregando tu primera sede física para configurar tus agendas, servicios de salud y recibir reservas online.' }}
                </p>
            </div>
        @endforelse
    </div>
</x-admin-layout>
