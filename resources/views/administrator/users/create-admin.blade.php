@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Gestión de Usuarios',
        'href' => route('administrator.users.index'),
    ],
    [
        'name' => 'Nuevo Administrador',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-7xl mx-auto py-8">
        
        <!-- Botón Volver -->
        <div class="mb-6">
            <a href="{{ route('administrator.users.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                ← Volver al directorio
            </a>
        </div>

        <!-- Encabezado del Formulario -->
        <div class="border-b border-slate-200 pb-5 mb-6">
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Alta de Personal Administrativo</h1>
            <p class="text-sm text-slate-500 mt-1">Registra una nueva cuenta con rol de administrador global. Tendrá acceso completo a métricas y validaciones.</p>
        </div>

        @if ($errors->any())
            <div class="p-4 mb-6 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200">
                <span class="font-bold">Por favor corrige los siguientes errores:</span>
                <ul class="list-disc list-inside mt-2 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form action="{{ route('administrator.users.storeAdmin') }}" method="POST" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-5">
            @csrf

            <!-- Nombre -->
            <div>
                <label prejudices for="name" class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-1">Nombre Completo</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl text-sm py-2.5">
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-1">Correo Electrónico Corp.</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required class="w-full border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl text-sm py-2.5">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Contraseña -->
                <div>
                    <label for="password" class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-1">Contraseña</label>
                    <input type="password" name="password" id="password" required class="w-full border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl text-sm py-2.5">
                </div>

                <!-- Confirmación de Contraseña -->
                <div>
                    <label for="password_confirmation" class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-1">Confirmar Contraseña</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required class="w-full border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl text-sm py-2.5">
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
                <a href="{{ route('administrator.users.index') }}" class="py-2.5 px-4 text-xs font-bold uppercase tracking-wider rounded-xl bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100 transition">
                    Cancelar
                </a>
                <button type="submit" class="py-2.5 px-5 text-xs font-black uppercase tracking-wider rounded-xl bg-indigo-600 text-white hover:bg-indigo-600 shadow-md transition shadow-lg">
                    Guardar Administrador
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
