@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Validacion de documentos',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Encabezado del Panel -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 pb-4 border-b border-slate-200">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Validación de Profesionales</h1>
                <p class="text-sm text-slate-500 mt-1">Revisa las credenciales de los médicos postulados para activar sus cuentas en OpenDoctor.</p>
            </div>
            <!-- Contador rápido en base a la variable de paginación -->
            <div class="mt-4 md:mt-0 bg-blue-50 text-blue-700 text-xs font-semibold px-3 py-1.5 rounded-full border border-blue-100 self-start">
                {{ $doctors->total() }} Médicos pendientes
            </div>
        </div>

        <!-- Alertas de Éxito / Errores globales -->
        @if (session('success'))
            <div class="p-4 mb-6 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 shadow-sm animate-fade-in">
                <span class="font-semibold">Éxito:</span> {{ session('success') }}
            </div>
        @endif

        <!-- Tabla Principal de Registros -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-600 uppercase tracking-wider">
                            <th class="p-4">Médico / Contacto</th>
                            <th class="p-4">Identificación y Cédula</th>
                            <th class="p-4">Documentación Soportada</th>
                            <th class="p-4 text-right">Acciones de Control</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                        @forelse ($doctors as $doctor)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                
                                <!-- Columna 1: Datos del Médico -->
                                <td class="p-4">
                                    <div class="font-semibold text-slate-900">{{ $doctor->user->name ?? 'Médico sin nombre' }}</div>
                                    <div class="text-xs text-slate-400 mt-0.5">{{ $doctor->user->email ?? 'Sin correo' }}</div>
                                    @if($doctor->phone)
                                        <div class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                            📞 {{ $doctor->phone }}
                                        </div>
                                    @endif
                                </td>
                                
                                <!-- Columna 2: Identificación Legal -->
                                <td class="p-4">
                                    <div class="inline-flex items-center text-xs font-mono bg-slate-100 text-slate-700 px-2 py-1 rounded mb-1.5 border border-slate-200">
                                        ID: {{ $doctor->identification }}
                                    </div>
                                    @if($doctor->medical_license)
                                        <span class="text-xs text-slate-500 block">Reg. Médico: <strong class="text-slate-700">{{ $doctor->medical_license }}</strong></span>
                                    @else
                                        <span class="text-xs text-amber-600 block italic">Licencia no registrada</span>
                                    @endif
                                </td>

                                <!-- Columna 3: Descarga Segura de Archivos -->
                                <td class="p-4">
                                    <div class="flex flex-col gap-y-2">
                                        <!-- Cédula -->
                                        <a href="{{ route('administrator.document.view', ['doctor' => $doctor, 'type' => 'cedula']) }}" 
                                        target="_blank" 
                                        class="inline-flex items-center text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline group w-fit">
                                            <span class="mr-1.5">🪪</span> Ver Cédula de Identidad
                                            <svg class="w-3 h-3 ml-1 opacity-0 group-hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                        </a>
                                        
                                        <!-- Tarjeta Profesional -->
                                        <a href="{{ route('administrator.document.view', ['doctor' => $doctor, 'type' => 'tarjeta']) }}" 
                                        target="_blank" 
                                        class="inline-flex items-center text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline group w-fit">
                                            <span class="mr-1.5">📜</span> Ver Tarjeta Profesional
                                            <svg class="w-3 h-3 ml-1 opacity-0 group-hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                        </a>
                                    </div>
                                </td>

                                <!-- Columna 4: Botones de Aprobación / Rechazo -->
                                <td class="p-4 text-right align-middle">
                                    <div class="inline-flex items-center justify-end gap-x-2">
                                        
                                        <!-- Formulario para Aprobar Médico -->
                                        <form action="{{ route('administrator.validation.update', $doctor) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" class="py-1.5 px-3 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 shadow-sm transition">
                                                Aprobar
                                            </button>
                                        </form>

                                        <!-- Formulario para Rechazar Médico -->
                                        <form action="{{ route('administrator.validation.update', $doctor) }}" method="POST" 
                                            onsubmit="return confirm('¿Estás seguro de que deseas rechazar este profesional? Se eliminarán los documentos actuales para que pueda cargarlos nuevamente.')">
                                            @csrf
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" class="py-1.5 px-3 text-xs font-semibold rounded-lg bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition">
                                                Rechazar
                                            </button>
                                        </form>

                                    </div>
                                </td>

                            </tr>
                        @empty
                            <!-- Estado vacío si no hay registros pendientes -->
                            <tr>
                                <td colspan="4" class="p-12 text-center">
                                    <div class="inline-flex items-center justify-center w-12 h-12 bg-slate-50 text-slate-400 rounded-full mb-3">
                                        ✅
                                    </div>
                                    <div class="text-sm font-semibold text-slate-900">Al día con las revisiones</div>
                                    <p class="text-xs text-slate-400 mt-1 max-w-xs mx-auto">No hay médicos o clínicas esperando validación de documentos en este momento.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginador nativo de Laravel Tailwind -->
        @if($doctors->hasPages())
            <div class="mt-6">
                {{ $doctors->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
