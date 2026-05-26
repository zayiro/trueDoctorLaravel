@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Gestión de Usuarios',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-7xl mx-auto py-8 space-y-6">
        
        <!-- Encabezado de Control -->
        <div class="border-b border-slate-200 pb-5">
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Directorio Operativo de Usuarios</h1>
            <p class="text-sm text-slate-500 mt-1">Busca, administra, activa o recupera accesos de usuarios ante cualquier reporte técnico urgente.</p>
            <!-- Botón de Registro Rápido de Staff -->
            <div class="my-3">
                <a href="{{ route('administrator.users.createAdmin') }}" class="py-2 px-3 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition">
                    + Nuevo Admin
                </a>
            </div>
        </div>

        <!-- Alertas de Sistema -->
        @if (session('success'))
            <div class="p-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 shadow-sm">
                <span class="font-semibold">Éxito:</span> {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200 shadow-sm">
                <span class="font-semibold">Error:</span> {{ session('error') }}
            </div>
        @endif

        <!-- Formulario Integrado de Búsqueda -->
        <form action="{{ route('administrator.users.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <div class="sm:col-span-2">
                <label for="search" class="sr-only">Buscar usuario</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Buscar por nombre o correo electrónico..." class="w-full border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl text-sm py-2.5">
            </div>
            <div>
                <label for="role" class="sr-only">Filtrar por Rol</label>
                <select name="role" id="role" onchange="this.form.submit()" class="w-full border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl text-sm py-2.5">
                    <option value="">Todos los Roles</option>
                    <option value="doctor" {{ request('role') === 'doctor' ? 'selected' : '' }}>Médicos</option>
                    <option value="clinic" {{ request('role') === 'clinic' ? 'selected' : '' }}>Clínicas</option>
                    <option value="patient" {{ request('role') === 'patient' ? 'selected' : '' }}>Pacientes</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Administradores</option>
                </select>
            </div>
        </form>
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-600 uppercase tracking-wider">
                            <th class="p-4">Usuario / Correo</th>
                            <th class="p-4">Rol del Ecosistema</th>
                            <th class="p-4">Estado Acceso</th>
                            <th class="p-4 text-right">Acciones de Soporte</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                        @forelse ($users as $item)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                
                                <!-- Información del Usuario -->
                                <td class="p-4">
                                    <div class="font-semibold text-slate-900">{{ $item->name }}</div>
                                    <div class="text-xs text-slate-400 mt-0.5">{{ $item->email }}</div>
                                    <div class="text-[10px] text-slate-400 mt-1">ID Cuenta: #{{ $item->id }}</div>
                                </td>
                                
                                <!-- Rol Visual -->
                                <td class="p-4">
                                    @php
                                        $roleBadge = [
                                            'doctor'  => 'bg-blue-50 text-blue-700 border-blue-100',
                                            'clinic'  => 'bg-purple-50 text-purple-700 border-purple-100',
                                            'patient' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                            'admin'   => 'bg-amber-50 text-amber-700 border-amber-100',
                                        ][$item->role] ?? 'bg-slate-50 text-slate-700 border-slate-100';
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $roleBadge }} uppercase tracking-wider">
                                        {{ $item->role }}
                                    </span>
                                </td>
                                <!-- Estado Operativo -->
                                <td class="p-4">
                                    @php
                                        // Resolver estado dinámico según el rol
                                        $profileActive = null;
                                        if ($item->role === 'doctor') {
                                            $profileActive = $item->doctor?->active;
                                        } elseif ($item->role === 'clinic') {
                                            $profileActive = $item->clinic?->active;
                                        } else {
                                            $profileActive = !is_null($item->email_verified_at);
                                        }
                                    @endphp
                                    
                                    @if($profileActive)
                                        <span class="inline-flex items-center text-xs font-semibold text-emerald-600 gap-1">
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> Activo / Visible
                                        </span>
                                    @else
                                        <span class="inline-flex items-center text-xs font-semibold text-red-600 gap-1">
                                            <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> Restringido / Oculto
                                        </span>
                                    @endif
                                </td>

                                <!-- Botones de Control Directo -->
                                <td class="p-4 text-right align-middle">
                                    <div class="inline-flex items-center justify-end gap-x-2">
                                        
                                        <!-- Switch de Estado (Activar/Desactivar) -->
                                        <form action="{{ route('administrator.users.toggle', $item) }}" method="POST" onsubmit="return confirm('¿Deseas alterar el estado de acceso de este usuario en producción?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="py-1.5 px-3 text-xs font-semibold rounded-lg {{ $profileActive ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-indigo-600 text-white hover:bg-indigo-700' }} transition">
                                                {{ $profileActive ? 'Suspender' : 'Activar' }}
                                            </button>
                                        </form>

                                        <!-- Envío de Enlace de Contraseña -->
                                        <form action="{{ route('administrator.users.reset', $item) }}" method="POST" onsubmit="return confirm('¿Enviar correo de recuperación de contraseña a este usuario?');">
                                            @csrf
                                            <button type="submit" class="p-1.5 text-xs font-semibold rounded-lg bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 transition" title="Restablecer Contraseña">
                                                🔑 Reestablecer
                                            </button>
                                        </form>

                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-12 text-center bg-slate-50/30">
                                    <div class="inline-flex items-center justify-center w-12 h-12 bg-slate-100 text-slate-400 rounded-full mb-3 text-xl">🔍</div>
                                    <div class="text-sm font-semibold text-slate-900">Sin coincidencias</div>
                                    <p class="text-xs text-slate-400 mt-1 max-w-xs mx-auto">No se encontraron usuarios activos que coincidan con los parámetros ingresados.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginación con soporte Query String -->
        @if($users->hasPages())
            <div class="mt-4">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
